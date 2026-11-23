<?php

namespace App\Http\Requests\Concerns;

trait NormalizesPostChannels
{
    /**
     * Keep only the current user's active social accounts and always
     * expose `channels` as a list of integer IDs (empty when none selected).
     */
    protected function normalizeChannels(): void
    {
        $validIds = $this->user()
            ->activeSocialAccounts()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $raw = $this->input('channels', []);

        if (! is_array($raw)) {
            $raw = $raw === null || $raw === '' ? [] : [$raw];
        }

        $selected = collect($raw)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && in_array($id, $validIds, true))
            ->unique()
            ->values()
            ->all();

        $this->merge(['channels' => $selected]);
    }
}
