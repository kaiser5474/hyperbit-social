<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DataDeletionThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_data_deletion_callback_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->postJson('/api/meta/data-deletion', [])->assertStatus(400);
        }

        $this->postJson('/api/meta/data-deletion', [])->assertStatus(429);
    }
}
