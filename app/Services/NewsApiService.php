<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use DateTime;

/**
 * Service for interacting with the NewsAPI to fetch workplace-related articles.
 */
class NewsApiService
{
    /**
     * @var string The NewsAPI key
     */
    private string $apiKey;

    /**
     * @var string The base URL for NewsAPI
     */
    private string $baseUrl = 'https://newsapi.org/v2';

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key');
    }

    /**
     * Fetch workplace news articles within an optional date range.
     *
     * @param DateTime|null $fromDate Start date for article search
     * @param DateTime|null $toDate End date for article search
     * @return array Array of news articles
     * @throws \Exception If the API request fails
     */
    public function fetchWorkplaceNews(?DateTime $fromDate = null, ?DateTime $toDate = null): array
    {
        $keywords = explode(',', env('NEWS_KEYWORDS'));

        $params = [
            'q' => implode(' OR ', $keywords),
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => 100,
            'apiKey' => $this->apiKey
        ];

        // Add date filters if provided
        if ($fromDate) {
            $params['from'] = $fromDate->format('Y-m-d');
        }

        if ($toDate) {
            $params['to'] = $toDate->format('Y-m-d');
        }

        $response = Http::get("{$this->baseUrl}/everything", $params);

        if (!$response->successful()) {
            $error = $response->json();
            throw new \Exception(
                'NewsAPI request failed: ' . 
                ($error['message'] ?? $response->body())
            );
        }

        $data = $response->json();

        if (!isset($data['articles'])) {
            throw new \Exception('Invalid response from NewsAPI: articles not found');
        }

        return $data['articles'];
    }

    /**
     * Fetch workplace news articles for a specific date range.
     *
     * @param string $fromDate Start date in YYYY-MM-DD format
     * @param string $toDate End date in YYYY-MM-DD format
     * @return array Array of news articles
     * @throws \Exception If date validation fails or API request fails
     */
    public function fetchWorkplaceNewsForDateRange(string $fromDate, string $toDate): array
    {
        try {
            $from = new DateTime($fromDate);
            $to = new DateTime($toDate);
            $now = new DateTime();

            // Validate dates
            if ($from > $to) {
                throw new \Exception('Start date must be before or equal to end date');
            }

            if ($from > $now || $to > $now) {
                throw new \Exception('Cannot fetch articles from future dates. Please use dates up to today.');
            }

            return $this->fetchWorkplaceNews($from, $to);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            
            // Pass through our custom error messages
            if (str_contains($message, 'Start date must be') ||
                str_contains($message, 'Cannot fetch articles from future') ||
                str_contains($message, 'NewsAPI free tier')) {
                throw $e;
            }
            
            // For other date parsing errors
            throw new \Exception(
                'Invalid date format. Please use YYYY-MM-DD format. Error: ' . $message
            );
        }
    }
} 