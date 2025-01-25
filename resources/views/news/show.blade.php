@php
use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $article->title }}
            </h2>
            <a href="{{ route('news.index') }}" class="text-blue-600 hover:underline">
                &larr; Back to News
            </a>
        </div>
    </x-slot>

    <!-- Add font import in the head -->
    <x-slot name="styles">
        <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            .vazirmatn {
                font-family: 'Vazirmatn', system-ui, -apple-system, sans-serif;
            }
        </style>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Article Meta Information -->
                    <div class="mb-12">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Article Information</h3>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <span>{{ $article->source_name }}</span>
                            <span>&bull;</span>
                            <span>{{ $article->published_at->format('M d, Y') }}</span>
                            <span>&bull;</span>
                            <span class="bg-gray-50 px-2 py-1 rounded">
                                {{ ucfirst($article->category) }}
                            </span>
                            <span>&bull;</span>
                            <a href="{{ $article->source_url }}" target="_blank" class="text-blue-600 hover:underline">
                                Read Original
                            </a>
                        </div>
                    </div>

                    <div class="h-px bg-gray-200 mb-12"></div>

                    <!-- Article Summary -->
                    <div class="mb-12">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                        <div class="prose max-w-none">
                            <p class="text-gray-600 text-lg leading-relaxed">{{ $article->summary }}</p>
                        </div>
                    </div>

                    <div class="h-px bg-gray-200 mb-12"></div>

                    <!-- Generated Content Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <!-- Left Column: Generated Image and Status -->
                        <div class="flex flex-col items-center">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Generated Image</h3>
                            <div class="space-y-6">
                                <!-- Image -->
                                @if($article->generated_image_url)
                                    <div class="w-80 h-80">
                                        <img src="{{ Storage::disk('public')->url($article->generated_image_url) }}" 
                                             alt="Generated illustration" 
                                             class="w-full h-full rounded-lg object-cover shadow-lg">
                                    </div>
                                @else
                                    <div class="w-64 h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                @endif
                                
                                <!-- Status Badges -->
                                <div class="flex items-center gap-4">
                                    @php
                                        $scoreClasses = match(true) {
                                            $article->relevance_score >= 9 => 'bg-green-100 text-green-700',
                                            $article->relevance_score >= 5 => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-red-100 text-red-700'
                                        };
                                    @endphp
                                    <!-- Score Badge -->
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium {{ $scoreClasses }}">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="font-semibold">{{ $article->relevance_score }}/10</span>
                                    </span>

                                    <!-- Approval Status -->
                                    @if($article->approval_status === 'approved')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium bg-green-100 text-green-700">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M7.75 12.75L10 15.25L16.25 8.75" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                            Approved
                                        </span>
                                    @elseif($article->approval_status === 'rejected')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium bg-red-100 text-red-700">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                                                <path d="M15 9L9 15M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                            Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium bg-gray-100 text-gray-700">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                                                <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: LinkedIn Post Preview -->
                        <div class="flex flex-col">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">LinkedIn Post Preview</h3>
                            <div class="relative">
                                <div class="bg-white border border-gray-200 rounded-lg p-6">
                                    @if($article->linkedin_post)
                                        <div class="vazirmatn text-right leading-relaxed whitespace-pre-wrap break-words text-gray-800 rtl" 
                                             style="direction: rtl; line-height: 2; font-size: 1.1rem;">
                                            {!! nl2br(e($article->linkedin_post)) !!}
                                        </div>
                                    @else
                                        <p class="text-gray-500 italic">
                                            LinkedIn post is being generated...
                                        </p>
                                    @endif
                                </div>

                                @if($article->linkedin_post)
                                    <button onclick="copyToClipboard()"
                                            class="absolute top-4 right-4 bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h8m-4-4v8m-4 4h8a2 2 0 002-2V8a2 2 0 00-2-2h-3.5a1.5 1.5 0 01-1.5-1.5V3a2 2 0 00-2-2H8a2 2 0 00-2 2v16a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function copyToClipboard() {
            const text = @json($article->linkedin_post);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Post copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                    alert('Failed to copy text. Please try again.');
                });
            } else {
                alert('Clipboard API not supported or not available in this context.');
            }
        }
    </script>
    @endpush
</x-app-layout> 