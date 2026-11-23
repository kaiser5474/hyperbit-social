<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\Publishers\PublisherManager;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        protected PublisherManager $publisherManager
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $query = $user->posts()->with('channels');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->orderByDesc('created_at')->paginate(20);

        return view('posts.index', compact('posts'));
    }

    public function create(): View
    {
        $accounts = auth()->user()->activeSocialAccounts()->get();

        if ($accounts->isEmpty()) {
            return view('posts.no-accounts');
        }

        return view('posts.create', compact('accounts'));
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        $mediaPath = null;
        $mediaType = null;
        $mediaUrl = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaPath = $file->store('posts', 'public');
            $mediaType = $file->getMimeType();
            $mediaUrl = url('storage/'.$mediaPath);
        }

        $scheduledAt = null;
        $status = Post::STATUS_DRAFT;

        if ($data['action'] === 'schedule' && ! empty($data['scheduled_at'])) {
            $timezone = $data['timezone'] ?? $user->timezone;
            $scheduledAt = Carbon::parse($data['scheduled_at'], $timezone)->utc();
            $status = Post::STATUS_SCHEDULED;
        } elseif ($data['action'] === 'publish_now') {
            $status = Post::STATUS_PUBLISHING;
        }

        $post = $user->posts()->create([
            'content' => $data['content'],
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'scheduled_at' => $scheduledAt,
            'timezone' => $data['timezone'] ?? $user->timezone,
            'status' => $status,
        ]);

        foreach ($data['channels'] as $channelId) {
            $post->channels()->attach($channelId, ['status' => 'pending']);
        }

        if ($data['action'] === 'publish_now') {
            $this->publishPost($post);

            return $this->redirectAfterPublish($post);
        }

        return redirect()->route('posts.index')
            ->with('success', __('Post scheduled successfully.'));
    }

    public function show(Post $post): View
    {
        $this->authorize('view', $post);

        $post->load('channels', 'postChannels.socialAccount');

        return view('posts.show', compact('post'));
    }

    public function edit(Post $post): View|RedirectResponse
    {
        $this->authorize('update', $post);

        if (! $post->canBeEdited()) {
            return redirect()->route('posts.show', $post)
                ->with('error', __('This post cannot be edited.'));
        }

        $accounts = auth()->user()->activeSocialAccounts()->get();
        $selectedChannels = $post->channels->pluck('id')->toArray();

        return view('posts.edit', compact('post', 'accounts', 'selectedChannels'));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        if (! $post->canBeEdited()) {
            return redirect()->route('posts.show', $post)
                ->with('error', __('This post cannot be edited.'));
        }

        $data = $request->validated();
        $user = auth()->user();

        $mediaPath = $post->media_path;
        $mediaType = $post->media_type;
        $mediaUrl = $post->media_url;

        if ($request->hasFile('media')) {
            if ($post->media_path) {
                Storage::disk('public')->delete($post->media_path);
            }

            $file = $request->file('media');
            $mediaPath = $file->store('posts', 'public');
            $mediaType = $file->getMimeType();
            $mediaUrl = url('storage/'.$mediaPath);
        } elseif ($request->boolean('remove_media') && $post->media_path) {
            Storage::disk('public')->delete($post->media_path);
            $mediaPath = null;
            $mediaType = null;
            $mediaUrl = null;
        }

        $scheduledAt = $post->scheduled_at;
        $status = $post->status;

        if (! empty($data['scheduled_at'])) {
            $timezone = $data['timezone'] ?? $user->timezone;
            $scheduledAt = Carbon::parse($data['scheduled_at'], $timezone)->utc();
            $status = Post::STATUS_SCHEDULED;
        }

        $post->update([
            'content' => $data['content'],
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'scheduled_at' => $scheduledAt,
            'timezone' => $data['timezone'] ?? $user->timezone,
            'status' => $status,
        ]);

        $post->channels()->sync(
            collect($data['channels'])->mapWithKeys(fn ($id) => [$id => ['status' => 'pending']])->all()
        );

        return redirect()->route('posts.show', $post)
            ->with('success', __('Post updated successfully.'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        if ($post->media_path) {
            Storage::disk('public')->delete($post->media_path);
        }

        $post->delete();

        return redirect()->route('posts.index')
            ->with('success', __('Post deleted successfully.'));
    }

    public function cancel(Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        if (! $post->canBeCancelled()) {
            return redirect()->route('posts.show', $post)
                ->with('error', __('This post cannot be cancelled.'));
        }

        $post->cancel();

        return redirect()->route('posts.show', $post)
            ->with('success', __('Post has been cancelled.'));
    }

    public function publishNow(Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        if (! in_array($post->status, [Post::STATUS_DRAFT, Post::STATUS_SCHEDULED, Post::STATUS_FAILED])) {
            return redirect()->route('posts.show', $post)
                ->with('error', __('This post cannot be published.'));
        }

        $post->markAsPublishing();
        $this->publishPost($post);

        return $this->redirectAfterPublish($post);
    }

    public function calendar(): View
    {
        $user = auth()->user();

        $posts = $user->posts()
            ->whereIn('status', [Post::STATUS_SCHEDULED, Post::STATUS_PUBLISHED])
            ->with('channels')
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => \Str::limit($post->content, 50),
                'start' => $post->scheduled_at?->toIso8601String() ?? $post->published_at?->toIso8601String(),
                'url' => route('posts.show', $post),
                'className' => $post->isPublished() ? 'bg-green-500' : 'bg-blue-500',
            ]);

        return view('posts.calendar', compact('posts'));
    }

    protected function publishPost(Post $post): void
    {
        $post->load('postChannels.socialAccount');

        $allSuccessful = true;

        foreach ($post->postChannels as $postChannel) {
            $result = $this->publisherManager->publish($postChannel);

            if ($result->success) {
                $postChannel->markAsPublished($result->platformPostId, $result->permalink);
            } elseif ($result->needsPolling) {
                $postChannel->setContainer($result->containerId, $result->containerStatus);
                $allSuccessful = false;
            } else {
                $postChannel->markAsFailed($result->errorMessage, $result->errorCode);
                $allSuccessful = false;
            }
        }

        if ($allSuccessful) {
            $post->markAsPublished();
        } else {
            $pendingCount = $post->postChannels()->where('status', 'pending')->count();
            $publishingCount = $post->postChannels()->where('status', 'publishing')->count();
            $failedCount = $post->postChannels()->where('status', 'failed')->count();

            if ($pendingCount === 0 && $publishingCount === 0) {
                if ($failedCount === $post->postChannels()->count()) {
                    $post->markAsFailed();
                } else {
                    $post->markAsPublished();
                }
            }
        }

        Log::info('Post publish attempt completed', [
            'post_id' => $post->id,
            'status' => $post->fresh()->status,
        ]);
    }

    protected function redirectAfterPublish(Post $post): RedirectResponse
    {
        $post->refresh();
        $post->load('postChannels');

        if ($post->isPublished()) {
            return redirect()->route('posts.show', $post)
                ->with('success', __('Your post has been published.'));
        }

        $failedChannel = $post->postChannels->first(
            fn ($channel) => $channel->isFailed()
        );

        if ($post->isFailed() || $failedChannel) {
            return redirect()->route('posts.show', $post)
                ->with('error', $failedChannel?->error_message ?: __('Publishing failed. Check the channel status below.'));
        }

        return redirect()->route('posts.show', $post)
            ->with('success', __('Your post is being published.'));
    }
}
