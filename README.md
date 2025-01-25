# Radio Hamkar Content Curation

> [!IMPORTANT]
> 🚧 **Active Development Notice** 🚧
> 
> This project is under active development. Features and documentation may change frequently.
> We welcome feedback and contributions to help improve the project.
> Check the [Current Limitations](#current-limitations) and [Planned Improvements](#-planned-improvements) sections to see what's coming next.

An automated content curation system for Radio Hamkar that fetches, analyzes, and processes workplace-related news articles using AI.

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat-square&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)
![Docker](https://img.shields.io/badge/Docker-20.10.x-2496ED?style=flat-square&logo=docker)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat-square&logo=tailwind-css)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=flat-square&logo=alpine.js)
![OpenAI](https://img.shields.io/badge/OpenAI-API-412991?style=flat-square&logo=openai)

## 🚀 Features

- Automated news fetching from various sources
- AI-powered content analysis and relevance scoring
- Automatic LinkedIn post generation
- DALL·E image generation for each article
- Content approval workflow
- Scheduled content updates

## 🛠 Tech Stack

- **Backend:** Laravel 10.x, PHP 8.2
- **Database:** MySQL 8.0
- **Frontend:** Tailwind CSS, Alpine.js, Laravel Blade
- **AI Services:** OpenAI GPT-4, DALL·E
- **Infrastructure:** Docker, Nginx, PHP-FPM
- **Scheduling:** Laravel Task Scheduler
- **Authentication:** Laravel Breeze

## 📋 Prerequisites

- Docker and Docker Compose
- Git
- NewsAPI API Key
- OpenAI API Key

## 🔧 Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/radio-hamkar-content.git
   cd radio-hamkar-content
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Configure your `.env` file with the following required variables:
   ```env
   APP_NAME="Radio Hamkar Content"
   APP_ENV=local
   APP_KEY=
   APP_DEBUG=true
   APP_URL=http://localhost:6006

   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password

   OPENAI_API_KEY=your_openai_api_key
   NEWS_API_KEY=your_newsapi_key

   RADIO_HAMKAR_CONTEXT="Radio Hamkar is a website that addresses workplace issues and provides solutions for personal growth, making it easier for people to find solutions to their workplace challenges. The content should be:
   1. Practical and actionable for workplace improvement
   2. Focused on professional development and growth
   3. Relevant to modern workplace challenges
   4. Applicable to both employees and managers
   5. Based on real-world examples or research
   6. Clear and solution-oriented"
   ```

4. Build and start the Docker containers:
   ```bash
   docker-compose up -d --build
   ```

5. Generate application key:
   ```bash
   docker-compose exec php-fpm php artisan key:generate
   ```

6. Run migrations:
   ```bash
   docker-compose exec php-fpm php artisan migrate
   ```

## 🔄 Scheduled Jobs

The application uses Laravel's Task Scheduler to automate content curation. Here's a detailed breakdown of each job:

### 1. News Fetching (`news:fetch`)
**Schedule**: Runs every 4 hours
**Command**: `php artisan news:fetch`
**Location**: `app/Console/Commands/FetchNewsCommand.php`

This command performs the following steps:
1. **Article Fetching**
   - Connects to NewsAPI using the configured API key
   - Searches for workplace-related articles using predefined keywords
   - Filters out duplicate articles based on URL

2. **Content Analysis**
   - For each article:
     - Sends the article content to OpenAI for analysis
     - Evaluates relevance to workplace topics
     - Assigns a score (1-10) based on alignment with Radio Hamkar's goals
     - If score < 8, skips further processing

3. **Content Generation**
   - For articles with score ≥ 8:
     - Generates a LinkedIn post in Farsi using GPT-4
     - Creates a relevant image using DALL·E
     - Saves the image locally in the storage directory

4. **Database Storage**
   - Saves article details including:
     - Title, description, source, URL
     - Generated LinkedIn post
     - Image path
     - Relevance score
     - Analysis results

**Logging**: All activities are logged to `storage/logs/schedule-news-fetch.log`

### 2. News Regeneration (`news:regenerate`)
**Schedule**: Runs daily at midnight
**Command**: `php artisan news:regenerate`
**Location**: `app/Console/Commands/RegenerateNewsCommand.php`

This command handles content refreshing:
1. **Article Selection**
   - Retrieves articles from the database that:
     - Have high relevance scores (≥ 8)
     - Haven't been approved or rejected yet
     - Are less than 7 days old

2. **Content Refresh**
   - For each selected article:
     - Regenerates the LinkedIn post with fresh perspective
     - Creates a new DALL·E image
     - Updates the database with new content

3. **Cleanup**
   - Removes old generated images
   - Updates article timestamps
   - Maintains storage efficiency

**Logging**: All regeneration activities are logged to `storage/logs/schedule-news-regenerate.log`

### Job Configuration
Both jobs are configured in `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('news:fetch')
        ->everyFourHours()
        ->appendOutputTo(storage_path('logs/schedule-news-fetch.log'));

    $schedule->command('news:regenerate')
        ->daily()
        ->appendOutputTo(storage_path('logs/schedule-news-regenerate.log'));
}
```

### Monitoring Jobs
You can monitor job execution in several ways:
1. Check the log files in `storage/logs/`
2. Use Laravel's built-in scheduling commands:
   ```bash
   # List all scheduled tasks
   docker-compose exec php-fpm php artisan schedule:list
   
   # Test run a specific command
   docker-compose exec php-fpm php artisan news:fetch
   docker-compose exec php-fpm php artisan news:regenerate
   ```

3. View the supervisor process logs:
   ```bash
   docker-compose logs php-fpm
   ```

### Current Limitations

1. **News Fetching Limitations**
   - NewsAPI free tier only allows fetching articles from the last month
   - Limited to 100 requests per day
   - Only fetches English articles
   - No sentiment analysis of article content
   - Fixed scoring threshold (8) might miss some relevant articles
   - No customization of search keywords without code changes

2. **Content Generation Limitations**
   - DALL·E image generation can be inconsistent
   - LinkedIn posts are generated in a fixed format
   - No A/B testing of different post formats
   - No tracking of post performance
   - Limited error recovery for failed API calls
   - No automatic retries for failed generations

3. **Technical Limitations**
   - No queue system for handling large article batches
   - No caching of API responses
   - No parallel processing of articles
   - Limited error reporting and monitoring
   - No automatic backup of generated content
   - Storage space not automatically managed

### 🚀 Planned Improvements

1. **Content Enhancement**
   - [ ] Implement multi-language support for article fetching
   - [ ] Add sentiment analysis for better content filtering
   - [ ] Create dynamic scoring system based on user feedback
   - [ ] Implement A/B testing for LinkedIn post formats
   - [ ] Add support for more news sources beyond NewsAPI
   - [ ] Create customizable search keywords through admin interface

2. **Performance Optimization**
   - [ ] Implement Redis for caching API responses
   - [ ] Add queue system using Laravel Horizon
   - [ ] Enable parallel processing of articles
   - [ ] Implement automatic retries for failed API calls
   - [ ] Add rate limiting for API requests
   - [ ] Optimize database queries and indexing

3. **Monitoring and Reliability**
   - [ ] Implement comprehensive error tracking
   - [ ] Add Slack/Discord notifications for job failures
   - [ ] Create dashboard for job performance metrics
   - [ ] Add automatic backup system for generated content
   - [ ] Implement storage cleanup automation
   - [ ] Add health checks for all external services

4. **User Experience**
   - [ ] Create admin interface for job configuration
   - [ ] Add preview capability for generated content
   - [ ] Implement batch approval/rejection
   - [ ] Add export functionality for approved content
   - [ ] Create API endpoints for external integration
   - [ ] Add user roles and permissions

5. **Content Quality**
   - [ ] Implement machine learning for better relevance scoring
   - [ ] Add plagiarism detection
   - [ ] Create content diversity metrics
   - [ ] Add source credibility scoring
   - [ ] Implement content categorization improvements
   - [ ] Add keyword extraction and trending topic detection

### Contributing to Improvements

We welcome contributions to any of the planned improvements. If you'd like to work on one of these features:

1. Check if there's an existing issue for the improvement
2. Create a new issue if none exists, describing your approach
3. Fork the repository and create a feature branch
4. Implement the improvement following our coding standards
5. Submit a pull request with comprehensive documentation

For major changes, please open an issue first to discuss what you would like to change.

## 🏗 Architecture

### Services
- **php-fpm**: Handles PHP processing and runs the Laravel scheduler
- **nginx**: Web server
- **mysql**: Database server

### Key Components
- **NewsApiService**: Fetches articles from NewsAPI
- **OpenAiService**: Handles content analysis, scoring, and image generation
- **Supervisor**: Manages PHP-FPM and Laravel scheduler processes

## 👥 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Support

For support, please open an issue in the GitHub repository or contact the maintainers.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com)
- [OpenAI](https://openai.com)
- [NewsAPI](https://newsapi.org)
- All contributors who participate in this project

## 🎨 Customizing for Your Company

To adapt this application for your company's needs:

1. **Branding Customization**
   - Replace the logo in `public/images/logo.png`
   - Update the favicon in `public/favicon.ico`
   - Modify the color scheme in `tailwind.config.js`:
     ```js
     theme: {
       extend: {
         colors: {
           primary: {
             // Your brand colors
             50: '#...',
             500: '#...',
             900: '#...',
           }
         }
       }
     }
     ```

2. **Content Configuration**
   Update your `.env` file with your company's context:
   ```env
   APP_NAME="Your Company Name"
   APP_URL=your-domain.com
   
   # Define your company's content focus
   COMPANY_CONTEXT="Your company description and content requirements:
   1. Your industry focus
   2. Target audience
   3. Content preferences
   4. Key topics
   5. Style guidelines
   6. Any specific requirements"

   # Configure content filters
   MIN_SCORE_THRESHOLD=8  # Minimum relevance score (1-10)
   CONTENT_LANGUAGES="en,es"  # Supported languages
   KEYWORDS="keyword1,keyword2,keyword3"  # Industry-specific keywords
   ```

3. **LinkedIn Post Template**
   Modify the LinkedIn post template in `app/Services/OpenAiService.php`:
   ```php
   // Customize the prompt for your brand voice
   $prompt = "Create a LinkedIn post that reflects {$company_name}'s expertise in...";
   ```

4. **User Interface**
   - Update welcome message in `resources/views/welcome.blade.php`
   - Modify navigation menu in `resources/views/layouts/navigation.blade.php`
   - Customize email templates in `resources/views/emails/`

5. **Content Policies**
   Update the following files to match your content requirements:
   - `app/Services/NewsApiService.php`: Modify search criteria
   - `app/Services/OpenAiService.php`: Adjust scoring parameters
   - `config/content.php`: Update content configuration

6. **Deployment**
   - Update Docker configurations if needed
   - Configure your CI/CD pipeline
   - Set up monitoring for your specific needs

Remember to:
- Test thoroughly after customization
- Update documentation to reflect your changes
- Maintain security best practices
- Keep track of upstream updates
