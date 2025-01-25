<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\NewsApiService;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Artisan command to fetch and process workplace news articles.
 *
 * This command fetches news articles from NewsAPI, analyzes them using OpenAI,
 * generates LinkedIn posts and images, and stores them in the database.
 */
class FetchNewsCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'news:fetch
                          {--from= : Start date (YYYY-MM-DD)}
                          {--to= : End date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and process workplace news with optional date range';

    /**
     * Execute the console command.
     *
     * @param NewsApiService $newsApi Service for fetching news articles
     * @param OpenAiService $openAi Service for AI-powered content generation
     * @return int Command exit code
     */
    public function handle(NewsApiService $newsApi, OpenAiService $openAi): int
    {
        try {
            // Log command start
            Log::info('Starting news fetch command', [
                'from' => $this->option('from'),
                'to' => $this->option('to')
            ]);

            $fromDate = $this->option('from');
            $toDate = $this->option('to');
            
            $this->info('Fetching articles from NewsAPI...');
            try {
                if ($fromDate && $toDate) {
                    $this->info("Date range: from {$fromDate} to {$toDate}");
                    $articles = $newsApi->fetchWorkplaceNewsForDateRange($fromDate, $toDate);
                } else {
                    $articles = $newsApi->fetchWorkplaceNews();
                }
                $this->info(sprintf('Found %d articles from NewsAPI', count($articles)));
            } catch (Exception $e) {
                $this->error('Error fetching articles: ' . $e->getMessage());
                return 1;
            }
            
            foreach ($articles as $index => $article) {
                $this->info("\n" . str_repeat('=', 50));
                $this->info(sprintf('Processing article %d/%d: %s (ID: %s)', $index + 1, count($articles), $article['title'], $article['url']));

                try {
                    // Log each article processing
                    Log::info('Processing article', [
                        'title' => $article['title'],
                        'source' => $article['source']['name']
                    ]);

                    // Check for existing article
                    if (NewsArticle::where('source_url', $article['url'])->exists()) {
                        $this->warn(sprintf('Article already exists (ID: %s, Title: %s), skipping...', $article['url'], $article['title']));
                        continue;
                    }

                    // Analyze content
                    $this->info(sprintf('Analyzing content with OpenAI for article (ID: %s, Title: %s)...', $article['url'], $article['title']));
                    $analysis = $openAi->categorizeAndAnalyze(
                        $article['title'],
                        $article['description'] . "\n" . ($article['content'] ?? '')
                    );
                    
                    // Skip if relevance score is less than 8
                    if ($analysis['relevance_score'] < 6) {
                        $this->warn(sprintf('Relevance score is less than 8 for article (ID: %s, Title: %s), skipping...', $article['url'], $article['title']));
                        continue;
                    }
                    
                    $this->info(sprintf('Analysis complete - Category: %s, Score: %d/10', 
                        $analysis['category'], 
                        $analysis['relevance_score']
                    ));

                    // Generate LinkedIn post
                    $this->info('Generating LinkedIn post...');
                    $linkedinPost = $openAi->generateLinkedInPost(
                        $article['title'],
                        $article['description'],
                        $analysis['category']
                    );
                    $this->info('LinkedIn post generated successfully');

                    // Generate DALL-E image
                    $this->info('Generating DALL-E image...');
                    try {
                        $imageUrl = $openAi->generateImage(
                            $article['title'],
                            $analysis['summary']
                        );
                        $this->info('Image generated successfully');
                    } catch (Exception $e) {
                        $this->error('Failed to generate image: ' . $e->getMessage());
                        $imageUrl = null;
                    }

                    // Save to database
                    $this->info('Saving article to database...');
                    NewsArticle::create([
                        'title' => $article['title'],
                        'original_content' => $article['description'] . "\n" . ($article['content'] ?? ''),
                        'summary' => $analysis['summary'],
                        'source_name' => $article['source']['name'],
                        'source_url' => $article['url'],
                        'category' => $analysis['category'],
                        'published_at' => $article['publishedAt'],
                        'relevance_score' => $analysis['relevance_score'],
                        'linkedin_post' => $linkedinPost,
                        'generated_image_url' => $imageUrl
                    ]);

                    $this->info('Article processed and saved successfully');

                    Log::info('Successfully processed article', [
                        'title' => $article['title']
                    ]);

                } catch (Exception $e) {
                    Log::error('Failed to process article', [
                        'title' => $article['title'],
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->error(sprintf('Failed processing article: %s', $article['title']));
                    $this->error('Error: ' . $e->getMessage());
                    $this->error('Stack trace: ' . $e->getTraceAsString());
                    continue; // Continue with next article even if this one fails
                }
            }

            $this->info("\n" . str_repeat('=', 50));
            $this->info('News fetch process completed');

            Log::info('News fetch command completed', [
                'total_processed' => count($articles)
            ]);

            return Command::SUCCESS;

        } catch (Exception $e) {
            Log::error('News fetch command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Fatal error in news fetch process');
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
} 