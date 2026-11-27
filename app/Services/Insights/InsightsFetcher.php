<?php

namespace App\Services\Insights;

use App\Models\Post;
use App\Models\PostChannel;
use App\Models\SocialAccount;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\Log;

class InsightsFetcher
{
    public const CACHE_MINUTES = 60;

    public function __construct(
        protected MetaGraphClient $client
    ) {}

    public function refreshPost(Post $post, bool $force = false): void
    {
        $post->loadMissing('postChannels.socialAccount');

        foreach ($post->postChannels as $channel) {
            if (! $channel->isPublished() || empty($channel->platform_post_id)) {
                continue;
            }

            if (! $force && ! $this->shouldRefresh($channel)) {
                continue;
            }

            $this->refreshChannel($channel);
        }
    }

    public function refreshChannel(PostChannel $channel): void
    {
        $channel->loadMissing('socialAccount');
        $account = $channel->socialAccount;

        if (! $account || empty($channel->platform_post_id)) {
            return;
        }

        try {
            $metrics = match ($account->platform) {
                SocialAccount::PLATFORM_FACEBOOK_PAGE => $this->fetchFacebook($channel, $account),
                SocialAccount::PLATFORM_INSTAGRAM_BUSINESS => $this->fetchInstagram($channel, $account),
                default => null,
            };

            if ($metrics === null) {
                $channel->storeInsightsError(__('Insights are not available for this platform.'));

                return;
            }

            $channel->storeInsights($metrics);
        } catch (MetaApiException $e) {
            Log::warning('Failed to fetch channel insights', [
                'post_channel_id' => $channel->id,
                'platform' => $account->platform,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            $channel->storeInsightsError($e->getUserFriendlyMessage());
        } catch (\Throwable $e) {
            Log::error('Unexpected insights fetch error', [
                'post_channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            $channel->storeInsightsError(__('Could not load metrics. Please try again.'));
        }
    }

    public function shouldRefresh(PostChannel $channel): bool
    {
        if ($channel->insights_fetched_at === null) {
            return true;
        }

        return $channel->insights_fetched_at->lte(now()->subMinutes(self::CACHE_MINUTES));
    }

    /**
     * @return array{reach: ?int, views: ?int, likes: ?int, comments: ?int, shares: ?int}
     */
    protected function fetchFacebook(PostChannel $channel, SocialAccount $account): array
    {
        try {
            $insights = $this->client->getFacebookPostInsights(
                $channel->platform_post_id,
                $account->access_token
            );
        } catch (MetaApiException $e) {
            if (! $e->isInvalidInsightsMetric()) {
                throw $e;
            }

            Log::info('Facebook unique media viewers metric failed; falling back to post_media_view', [
                'post_channel_id' => $channel->id,
                'error' => $e->getMessage(),
            ]);

            $insights = $this->client->getFacebookPostInsights(
                $channel->platform_post_id,
                $account->access_token,
                ['post_media_view']
            );
        }

        $byName = $this->mapInsightValues($insights['data'] ?? []);

        $engagement = $this->client->getFacebookPostEngagement(
            $channel->platform_post_id,
            $account->access_token
        );

        return [
            'reach' => $byName['post_total_media_view_unique'] ?? null,
            'views' => $byName['post_media_view'] ?? null,
            'likes' => $this->nestedCount($engagement, 'reactions'),
            'comments' => $this->nestedCount($engagement, 'comments'),
            'shares' => isset($engagement['shares']['count'])
                ? (int) $engagement['shares']['count']
                : null,
        ];
    }

    /**
     * @return array{reach: ?int, views: ?int, likes: ?int, comments: ?int, shares: ?int}
     */
    protected function fetchInstagram(PostChannel $channel, SocialAccount $account): array
    {
        $byName = [];

        try {
            $insights = $this->client->getInstagramMediaInsights(
                $channel->platform_post_id,
                $account->access_token
            );
            $byName = $this->mapInsightValues($insights['data'] ?? []);
        } catch (MetaApiException $e) {
            // Older media may reject "views"; retry with reach only then fill engagement.
            Log::info('Instagram insights primary metrics failed; falling back', [
                'media_id' => $channel->platform_post_id,
                'error' => $e->getMessage(),
            ]);

            try {
                $insights = $this->client->getInstagramMediaInsights(
                    $channel->platform_post_id,
                    $account->access_token,
                    ['reach', 'impressions']
                );
                $byName = $this->mapInsightValues($insights['data'] ?? []);
            } catch (MetaApiException) {
                // Continue with engagement fields only.
            }
        }

        $likes = $byName['likes'] ?? null;
        $comments = $byName['comments'] ?? null;

        if ($likes === null || $comments === null) {
            try {
                $engagement = $this->client->getInstagramMediaEngagement(
                    $channel->platform_post_id,
                    $account->access_token
                );
                $likes ??= isset($engagement['like_count']) ? (int) $engagement['like_count'] : null;
                $comments ??= isset($engagement['comments_count']) ? (int) $engagement['comments_count'] : null;
            } catch (MetaApiException) {
                // Leave nulls.
            }
        }

        return [
            'reach' => $byName['reach'] ?? null,
            'views' => $byName['views'] ?? $byName['impressions'] ?? null,
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $byName['saved'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, int>
     */
    protected function mapInsightValues(array $rows): array
    {
        $mapped = [];

        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['name'])) {
                continue;
            }

            $value = $row['values'][0]['value'] ?? $row['total_value']['value'] ?? null;

            if (is_numeric($value)) {
                $mapped[(string) $row['name']] = (int) $value;
            }
        }

        return $mapped;
    }

    protected function nestedCount(array $payload, string $key): ?int
    {
        $summary = $payload[$key]['summary']['total_count'] ?? null;

        return is_numeric($summary) ? (int) $summary : null;
    }
}
