<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;
use App\Services\OpenAiImageService;

class NewsArticleController extends Controller
{
    public function index(Request $request)
    {
        $articles = NewsArticle::query()
            ->when($request->search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('summary', 'like', "%{$search}%")
                      ->orWhere('original_content', 'like', "%{$search}%");
                });
            })
            ->when($request->category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->when($request->min_score, function ($query, $minScore) {
                return $query->where('relevance_score', '>=', $minScore);
            })
            ->when($request->max_score, function ($query, $maxScore) {
                return $query->where('relevance_score', '<=', $maxScore);
            })
            ->when($request->approval_status, function ($query, $status) {
                return $query->where('approval_status', $status);
            })
            ->orderBy('relevance_score', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('news.index', compact('articles'));
    }

    public function updateApprovalStatus(Request $request, NewsArticle $article)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'in:approved,rejected'],
            ]);

            $article->update([
                'approval_status' => $validated['status'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($validated['status']) . ' successfully',
                'new_status' => $validated['status']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    public function show(NewsArticle $article)
    {
        return view('news.show', compact('article'));
    }
} 