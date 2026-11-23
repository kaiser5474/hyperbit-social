<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $stats = [
            'connected_accounts' => $user->activeSocialAccounts()->count(),
            'scheduled_posts' => $user->posts()->scheduled()->count(),
            'published_posts' => $user->posts()->where('status', Post::STATUS_PUBLISHED)->count(),
            'failed_posts' => $user->posts()->where('status', Post::STATUS_FAILED)->count(),
        ];

        $upcomingPosts = $user->posts()
            ->scheduled()
            ->with('channels')
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();

        $recentPosts = $user->posts()
            ->whereIn('status', [Post::STATUS_PUBLISHED, Post::STATUS_FAILED])
            ->with('channels')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        $accounts = $user->activeSocialAccounts()->get();

        return view('dashboard.index', compact('stats', 'upcomingPosts', 'recentPosts', 'accounts'));
    }
}
