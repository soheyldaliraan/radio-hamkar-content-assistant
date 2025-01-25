<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to regenerate specific aspects of news articles.
 *
 * This command can regenerate analysis, LinkedIn posts, and images for
 * existing articles, either individually or in batch.
 */
class RegenerateArticleCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'news:regenerate 
                          {article_id? : The ID of the article to regenerate (optional)}
                          {--analyze : Regenerate category, summary, and relevance score}
                          {--linkedin : Regenerate LinkedIn post}
                          {--image : Regenerate DALL-E image}
                          {--all : Regenerate everything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate specific aspects of one or all news articles';

    /**
     * Execute the console command.
     *
     * @param OpenAiService $openAi Service for AI-powered content generation
     * @return int Command exit code
     */
    public function handle(OpenAiService $openAi): int
    {
        try {
            Log::info('Starting article regeneration', [
                'article_id' => $this->argument('article_id'),
                'options' => [
                    'analyze' => $this->option('analyze'),
                    'linkedin' => $this->option('linkedin'),
                    'image' => $this->option('image'),
                    'all' => $this->option('all')
                ]
            ]);

            $query = NewsArticle::query();
            
            if ($articleId = $this->argument('article_id')) {
                $query->where('id', $articleId);
            }

            $articles = $query->get();
            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($articles as $article) {
                try {
                    Log::info('Processing article regeneration', [
                        'article_id' => $article->id,
                        'title' => $article->title
                    ]);

                    $this->info("\n\nProcessing article: {$article->title}");
                    $this->info(str_repeat('-', 50));

                    // Regenerate analysis (category, summary, score)
                    if ($this->option('analyze') || $this->option('all')) {
                        $this->info('Regenerating analysis...');
                        $analysis = $openAi->categorizeAndAnalyze(
                            $article->title,
                            $article->original_content
                        );
                        
                        $article->update([
                            'category' => $analysis['category'],
                            'summary' => $analysis['summary'],
                            'relevance_score' => $analysis['relevance_score']
                        ]);
                        
                        $this->info('✓ Analysis updated:');
                        $this->info("  ID: {$article->id}");
                        $this->info("  Title: {$article->title}");
                        $this->info("  Category: {$analysis['category']}");
                        $this->info("  Score: {$analysis['relevance_score']}/10");
                        $this->info("  Summary: {$analysis['summary']}");
                        $this->info("\nScore Explanation:");
                        $this->info("  " . str_replace("\n", "\n  ", $analysis['score_explanation']));
                        $this->info("\nSimilarity Analysis:");
                        $this->info("  " . str_replace("\n", "\n  ", $analysis['similarity_to_approved']));
                    }
                    // Skip remaining processing if score is below threshold
                    if ($analysis['relevance_score'] < 7) {
                        $this->info('Skipping remaining processing due to low relevance score');
                        continue;
                    }

                    // Regenerate LinkedIn post
                    if ($this->option('linkedin') || $this->option('all')) {
                        $this->info('Regenerating LinkedIn post...');
                        $linkedinPost = $openAi->generateLinkedInPost(
                            $article->title,
                            $article->summary,
                            $article->category
                        );
                        
                        $article->update(['linkedin_post' => $linkedinPost]);
                        $this->info('✓ LinkedIn post updated');
                    }

                    // Regenerate image
                    if ($this->option('image') || $this->option('all')) {
                        $this->info('Regenerating DALL-E image...');
                        try {
                            $imageUrl = $openAi->generateImage(
                                $article->title,
                                $article->summary
                            );
                            
                            $article->update(['generated_image_url' => $imageUrl]);
                            $this->info('✓ Image updated');
                        } catch (\Exception $e) {
                            $this->error('Failed to generate image: ' . $e->getMessage());
                            throw $e;
                        }
                    }

                    Log::info('Successfully regenerated article content', [
                        'article_id' => $article->id,
                        'regenerated_fields' => [
                            'analysis' => $this->option('analyze') || $this->option('all'),
                            'linkedin' => $this->option('linkedin') || $this->option('all'),
                            'image' => $this->option('image') || $this->option('all')
                        ]
                    ]);

                    $successCount++;

                } catch (Exception $e) {
                    Log::error('Failed to regenerate article', [
                        'article_id' => $article->id,
                        'title' => $article->title,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    $failureCount++;
                    $errors[] = [
                        'article_id' => $article->id,
                        'title' => $article->title,
                        'error' => $e->getMessage()
                    ];
                    $this->error('Failed processing article: ' . $e->getMessage());
                }
            }

            Log::info('Article regeneration completed', [
                'total' => $articles->count(),
                'success' => $successCount,
                'failures' => $failureCount,
                'errors' => $errors
            ]);

            $this->info("\n\n" . str_repeat('=', 50));
            $this->info("Processing Complete!");
            $this->info("Successful: {$successCount}");
            $this->info("Failed: {$failureCount}");

            if ($failureCount > 0) {
                $this->info("\nErrors encountered:");
                foreach ($errors as $error) {
                    $this->error("Article #{$error['article_id']} - {$error['title']}");
                    $this->error("Error: {$error['error']}");
                    $this->info(str_repeat('-', 30));
                }
            }
            
            if (!$this->option('analyze') && !$this->option('linkedin') && !$this->option('image') && !$this->option('all')) {
                $this->warn('No regeneration options were selected. Use --analyze, --linkedin, --image, or --all to specify what to regenerate.');
            }

            return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (Exception $e) {
            Log::error('Article regeneration command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
