@php
use Illuminate\Support\Facades\Storage;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Workplace News') }}
            </h2>
            
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <!-- Search Input -->
                <div class="w-full sm:w-auto">
                    <form method="GET" action="{{ route('news.index') }}" class="flex items-center gap-2">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="min_score" value="{{ request('min_score') }}">
                        <input type="hidden" name="approval_status" value="{{ request('approval_status') }}">
                        
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Search news..."
                                   class="w-full sm:w-64 rounded-lg border-gray-200 text-sm text-gray-900 pr-8 focus:ring-gray-200 focus:border-gray-300">
                            @if(request('search'))
                                <a href="{{ route('news.index', request()->except('search')) }}" 
                                   class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-3 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-gray-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search
                        </button>
                    </form>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-4">
                    <!-- Category Filter -->
                    <div class="flex gap-2">
                        <select name="category" 
                                onchange="window.location.href=this.value"
                                class="rounded-lg border-gray-200 text-sm text-gray-900 focus:ring-gray-200 focus:border-gray-300">
                            <option value="{{ route('news.index', array_merge(request()->except('category'), ['category' => ''])) }}">
                                All Categories
                            </option>
                            @foreach(['tips', 'case studies', 'insights'] as $category)
                                <option value="{{ route('news.index', array_merge(request()->except('category'), ['category' => $category])) }}"
                                        {{ request('category') == $category ? 'selected' : '' }}>
                                    {{ ucfirst($category) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Score Filter -->
                    <select name="min_score" 
                            onchange="window.location.href=this.value"
                            class="rounded-lg border-gray-200 text-sm text-gray-900 focus:ring-gray-200 focus:border-gray-300">
                        <option value="{{ route('news.index', array_merge(request()->except('min_score'), ['min_score' => ''])) }}">
                            All Scores
                        </option>
                        @foreach(range(1, 10) as $score)
                            <option value="{{ route('news.index', array_merge(request()->except('min_score'), ['min_score' => $score])) }}"
                                    {{ request('min_score') == $score ? 'selected' : '' }}>
                                {{ $score }}+ Score
                            </option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select name="approval_status" 
                            onchange="window.location.href=this.value"
                            class="rounded-lg border-gray-200 text-sm text-gray-900 focus:ring-gray-200 focus:border-gray-300">
                        <option value="{{ route('news.index', array_merge(request()->except('approval_status'), ['approval_status' => ''])) }}">
                            All Status
                        </option>
                        @foreach(['pending', 'approved', 'rejected'] as $status)
                            <option value="{{ route('news.index', array_merge(request()->except('approval_status'), ['approval_status' => $status])) }}"
                                    {{ request('approval_status') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>

                    @if(request()->hasAny(['category', 'min_score', 'approval_status', 'search']))
                        <button onclick="window.location.href='{{ route('news.index') }}'"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 border border-gray-200 hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Reset Filters
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="space-y-8">
                        @foreach($articles as $article)
                            <article class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300"
                                     x-data="{
                                        async updateStatus(status) {
                                            try {
                                                const token = document.querySelector('meta[name=csrf-token]').content;
                                                const formData = new FormData();
                                                formData.append('status', status);
                                                formData.append('_token', token);

                                                const response = await fetch(`/news/${this.$root.dataset.articleId}/approval`, {
                                                    method: 'POST',
                                                    headers: {
                                                        'X-CSRF-TOKEN': token,
                                                        'Accept': 'application/json'
                                                    },
                                                    body: formData
                                                });

                                                const responseText = await response.text();
                                                console.log('Raw response:', responseText);

                                                // Try to extract JSON from the response
                                                const jsonMatch = responseText.match(/\{.*\}/);
                                                if (jsonMatch) {
                                                    const jsonStr = jsonMatch[0];
                                                    const data = JSON.parse(jsonStr);
                                                    console.log('Parsed response:', data);
                                                    
                                                    if (data.status === 'success') {
                                                        window.location.reload();
                                                        return;
                                                    }
                                                }
                                                
                                                throw new Error('Invalid response format');
                                            } catch (error) {
                                                console.error('Error:', error);
                                                alert('Failed to update status. Please try again.');
                                            }
                                        }
                                     }"
                                     data-article-id="{{ $article->id }}">
                                <div class="p-6">
                                    <div class="flex gap-6">
                                        <!-- Article Thumbnail -->
                                        <div class="flex-shrink-0">
                                            @if($article->generated_image_url)
                                                <img src="{{ url(Storage::disk('public')->url($article->generated_image_url)) }}" 
                                                     alt="Article thumbnail" 
                                                     class="w-40 h-40 object-cover rounded-lg">
                                            @else
                                                <div class="w-40 h-40 bg-gray-50 rounded-lg flex items-center justify-center">
                                                    <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Article Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col h-full">
                                                <div class="flex items-start justify-between gap-4 mb-2">
                                                    <h3 class="text-base font-semibold leading-6 text-gray-900 hover:text-gray-600">
                                                        <a href="{{ route('news.show', $article) }}" class="hover:underline">
                                                            {{ $article->title }}
                                                        </a>
                                                    </h3>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        @php
                                                            $scoreClasses = match(true) {
                                                                $article->relevance_score >= 9 => 'bg-green-100 text-green-700',
                                                                $article->relevance_score >= 5 => 'bg-yellow-100 text-yellow-700',
                                                                default => 'bg-red-100 text-red-700'
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium {{ $scoreClasses }}">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                            </svg>
                                                            <span class="font-semibold">{{ $article->relevance_score }}/10</span>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                                    <span class="font-medium text-gray-900">{{ $article->source_name }}</span>
                                                    <span class="text-gray-300">•</span>
                                                    <time datetime="{{ $article->published_at }}">{{ $article->published_at->diffForHumans() }}</time>
                                                    <span class="text-gray-300">•</span>
                                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600">
                                                        {{ ucfirst($article->category) }}
                                                    </span>
                                                </div>
                                                <p class="mt-3 text-sm text-gray-600 line-clamp-2">{{ $article->summary }}</p>
                                                <div class="mt-4 flex items-center gap-2">
                                                    <button type="button"
                                                            @click="updateStatus('approved')"
                                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors
                                                            {{ $article->approval_status === 'approved' 
                                                                ? 'bg-green-100 text-green-700' 
                                                                : 'bg-white text-green-600 hover:bg-green-50 border border-green-600/20' }}"
                                                            {{ $article->approval_status === 'approved' ? 'disabled' : '' }}
                                                            title="{{ $article->approval_status === 'approved' ? 'Article is approved' : 'Approve this article' }}">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M7.75 12.75L10 15.25L16.25 8.75" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                        {{ $article->approval_status === 'approved' ? 'Approved' : 'Approve' }}
                                                    </button>
                                                    <button type="button"
                                                            @click="updateStatus('rejected')"
                                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-colors
                                                            {{ $article->approval_status === 'rejected' 
                                                                ? 'bg-red-100 text-red-700' 
                                                                : 'bg-white text-red-600 hover:bg-red-50 border border-red-600/20' }}"
                                                            {{ $article->approval_status === 'rejected' ? 'disabled' : '' }}
                                                            title="{{ $article->approval_status === 'rejected' ? 'Article is rejected' : 'Reject this article' }}">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2"/>
                                                            <path d="M15 9L9 15M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                        {{ $article->approval_status === 'rejected' ? 'Rejected' : 'Reject' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $articles->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 