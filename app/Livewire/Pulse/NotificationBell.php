<?php

namespace App\Livewire\Pulse;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    #[Computed]
    public function recent(): Collection
    {
        return Auth::user()->notifications()->latest()->limit(8)->get();
    }

    public function toggleOpen(): void
    {
        $this->open = ! $this->open;
        if ($this->open) {
            unset($this->recent, $this->unreadCount);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
        unset($this->unreadCount, $this->recent);
    }

    public function render(): View
    {
        return view('livewire.pulse.notification-bell');
    }
}
