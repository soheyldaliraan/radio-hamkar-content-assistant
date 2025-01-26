<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\NewsArticle;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for interacting with OpenAI's APIs to generate and analyze content.
 */
class OpenAiService
{
    /**
     * Generate a DALL-E prompt based on article title and summary.
     *
     * @param string $title The article title
     * @param string $summary The article summary
     * @return string The generated DALL-E prompt
     */
    public function generateDallEPrompt(string $title, string $summary): string
    {
        $prompt = <<<EOT
        Create a detailed DALL-E prompt for a professional business image based on this article:
        Title: {$title}
        Summary: {$summary}

        The prompt should:
        1. Be detailed and specific
        2. Focus on professional and business-related imagery
        3. Include style specifications (e.g., professional, modern, corporate)
        4. Specify composition and mood
        5. Be optimized for DALL-E image generation
        6. Avoid any text or words in the image
        7. Create an abstract or metaphorical representation of the concept

        Return only the prompt text, without any explanations or quotation marks.
        EOT;

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return trim($response->choices[0]->message->content);
    }

    /**
     * Generate and save an image using DALL-E based on article content.
     *
     * @param string $title The article title
     * @param string $summary The article summary
     * @return string The path to the saved image
     * @throws \Exception If image generation or saving fails
     */
    public function generateImage(string $title, string $summary): string
    {
        $imagePrompt = $this->generateDallEPrompt($title, $summary);
        
        $response = OpenAI::images()->create([
            'model' => 'dall-e-3',
            'prompt' => $imagePrompt,
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1,
        ]);

        $imageUrl = $response->data[0]->url;
        
        // Generate a unique filename
        $filename = 'articles/' . date('Y/m/') . Str::random(40) . '.png';
        
        // Create directory if it doesn't exist
        $directory = public_path('storage/' . dirname($filename));
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        
        // Download and save the image directly to public/storage
        $imageContents = file_get_contents($imageUrl);
        file_put_contents(public_path('storage/' . $filename), $imageContents);
        
        return $filename;
    }

    /**
     * Generate a LinkedIn post in Farsi based on article content.
     *
     * @param string $title The article title
     * @param string $summary The article summary
     * @param string $category The article category
     * @return string The generated LinkedIn post content
     */
    public function generateLinkedInPost(string $title, string $summary, string $category): string
    {
        $prompt = <<<EOT
        Create a professional LinkedIn post in Farsi language about this article:
        Title: {$title}
        Summary: {$summary}
        Category: {$category}

        The post should:
        1. Be engaging and professional
        2. Highlight key takeaways from each major section of the article, explaining each point thoroughly
        3. Include relevant hashtags at the end
        4. Maintain proper Farsi grammar and writing style
        5. Be formatted for LinkedIn with proper line breaks
        6. Include a call to action
        7. Use line breaks between paragraphs (use actual newlines)
        8. Format the text with:
           - Empty lines between paragraphs
           - Separate hashtags with spaces
           - Clear visual hierarchy
        9. Keep technical terms, product names, or specific concepts that don't have meaningful Farsi translations in English
        10. Provide clear explanations for any technical concepts or industry-specific terms

        Return the post with proper line breaks preserved, ready to be copied directly to LinkedIn.
        EOT;

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return trim($response->choices[0]->message->content);
    }

    /**
     * Analyze and categorize article content using OpenAI.
     *
     * @param string $title The article title
     * @param string $content The article content
     * @return array{
     *     category: string,
     *     summary: string,
     *     relevance_score: int,
     *     score_explanation: string,
     *     similarity_to_approved: string
     * } Analysis results
     */
    public function categorizeAndAnalyze(string $title, string $content): array
    {
        // Get examples of approved articles
        $approvedArticles = NewsArticle::where('approval_status', 'approved')
            ->orderBy('relevance_score', 'desc')
            ->take(5)
            ->get()
            ->map(function ($article) {
                return [
                    'title' => $article->title,
                    'content' => $article->original_content,
                    'score' => $article->relevance_score,
                    'category' => $article->category,
                ];
            });

        $examplesContext = '';
        if ($approvedArticles->isNotEmpty()) {
            $examplesContext = "Here are some examples of previously approved articles and their scores:\n\n";
            foreach ($approvedArticles as $example) {
                $examplesContext .= "Title: {$example['title']}\n";
                $examplesContext .= "Content: {$example['content']}\n";
                $examplesContext .= "Score: {$example['score']}/10\n";
                $examplesContext .= "Category: {$example['category']}\n\n";
            }
        }

        $prompt = <<<EOT
        Based on the following article and context, please analyze and categorize the content:

        CONTEXT:
        {$this->getContentContext()}

        {$examplesContext}

        ARTICLE TO ANALYZE:
        Title: {$title}
        Content: {$content}

        Please analyze this article considering:
        1. How well it matches Radio Hamkar's goals and focus
        2. Its similarity to previously approved high-scoring articles
        3. The practicality and actionability of its content
        4. The relevance to workplace improvement
        5. The quality and depth of insights provided

        Respond in JSON format:
        {
            "category": "category_here (choose from: tips, case studies, or insights)",
            "summary": "concise_2_3_sentence_summary_here",
            "relevance_score": number_between_1_and_10,
            "score_explanation": "detailed_explanation_of_score_considering_all_factors",
            "similarity_to_approved": "explanation_of_how_it_compares_to_approved_articles"
        }
        EOT;

        $response = OpenAI::chat()->create([
            'model' => 'o1',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    /**
     * Get the content context from environment variables.
     *
     * @return string The content context
     */
    private function getContentContext(): string
    {
        return env('CONTENT_CONTEXT');
    }
} 