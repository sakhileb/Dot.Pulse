<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\NotificationBell;
use App\Models\User;
use App\Notifications\NewFollower;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_reflects_unread_notifications(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $followerOne = User::factory()->withPersonalTeam()->create();
        $followerTwo = User::factory()->withPersonalTeam()->create();

        $user->notify(new NewFollower($followerOne));
        $user->notify(new NewFollower($followerTwo));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);

        $this->assertSame(2, $component->get('unreadCount'));
        $this->assertCount(2, $component->get('recent'));
    }

    public function test_mark_all_read_zeroes_the_unread_count(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();
        $user->notify(new NewFollower($follower));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(1, $component->get('unreadCount'));

        $component->call('markAllRead');

        $this->assertSame(0, $component->get('unreadCount'));
        $this->assertNotNull($user->notifications()->first()->read_at);
    }

    public function test_the_notification_received_event_refreshes_the_bell(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(0, $component->get('unreadCount'));

        $follower = User::factory()->withPersonalTeam()->create();
        $user->notify(new NewFollower($follower));

        $component->dispatch('notification-received');
        $this->assertSame(1, $component->get('unreadCount'));
    }

    public function test_a_users_bell_only_shows_their_own_notifications(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $stranger = User::factory()->withPersonalTeam()->create();
        $follower = User::factory()->withPersonalTeam()->create();

        $stranger->notify(new NewFollower($follower));

        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertSame(0, $component->get('unreadCount'));
    }
}
