<?php

namespace App\Livewire\Pulse;

use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function recent(): DatabaseNotificationCollection
    {
        return auth()->user()->notifications()->latest()->limit(20)->get();
    }

    /**
     * resources/js/pulse-realtime.js dispatches this on a live
     * App\Notifications\NewFollower broadcast -- mirrors HomeFeed's own
     * #[On('post-created')] pattern.
     */
    #[On('notification-received')]
    public function refresh(): void
    {
        unset($this->unreadCount, $this->recent);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.pulse.notification-bell');
    }
}
