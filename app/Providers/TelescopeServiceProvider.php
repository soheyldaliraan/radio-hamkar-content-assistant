<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Don't register telescope in production
        if ($this->app->environment('production')) {
            return;
        }

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry) {
            return true;
        });

        // Add custom tag for news-related events
        Telescope::tag(function (IncomingEntry $entry) {
            if ($this->isNewsRelatedEntry($entry)) {
                return ['news'];
            }

            return [];
        });
    }

    /**
     * Check if the entry is related to news functionality.
     */
    private function isNewsRelatedEntry(IncomingEntry $entry): bool
    {
        $newsPatterns = [
            'news:fetch',
            'news:regenerate',
            'NewsApiService',
            'NewsArticle',
            '/news/',
        ];

        // Check command, job and exception entries
        if (in_array($entry->type, ['command', 'job', 'exception'])) {
            $fields = [
                'command' => 'command',
                'job' => 'name', 
                'exception' => 'class'
            ];
            return str_contains($entry->content[$fields[$entry->type]] ?? '', $entry->type === 'exception' ? 'News' : 'news');
        }
        
        // Check log entries
        if ($entry->type === 'log') {
            $message = $entry->content['message'] ?? '';
            return collect($newsPatterns)->some(fn($pattern) => str_contains($message, $pattern));
        }

        return false;
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user = null) {
            return $this->app->environment('local');
        });
    }
}
