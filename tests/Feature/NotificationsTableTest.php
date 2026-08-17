<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The notifications table already exists in the shared ecosystem
 * PostgreSQL database (created by another platform sharing it) -- this
 * repo has no migration for it, so a fresh test database has no such
 * table at all. See docs/superpowers/specs/
 * 2026-08-17-follow-graph-polish-and-notifications.md.
 */
class NotificationsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notifications_table_exists_with_the_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumns('notifications', [
            'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at',
        ]));
    }
}
