<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\SendMessage;
use App\Models\PulseConversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Messaging extends Component
{
    #[Url]
    public ?int $conversationId = null;

    public string $messageBody  = '';
    public string $searchUser   = '';

    public function mount(?int $activeId = null): void
    {
        $this->conversationId = $activeId;
    }

    #[Computed]
    public function conversations(): Collection
    {
        return PulseConversation::with(['participants', 'lastMessage.sender'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest('updated_at')
            ->limit(30)
            ->get();
    }

    #[Computed]
    public function activeConversation(): ?PulseConversation
    {
        if (! $this->conversationId) {
            return null;
        }

        return PulseConversation::with(['participants', 'messages.sender'])
            ->whereHas('participants', fn ($q) => $q->where('user_id', Auth::id()))
            ->find($this->conversationId);
    }

    #[Computed]
    public function userSearchResults(): Collection
    {
        if (strlen(trim($this->searchUser)) < 2) {
            return collect();
        }

        return User::where('id', '!=', Auth::id())
            ->where('name', 'like', '%' . $this->searchUser . '%')
            ->limit(8)
            ->get();
    }

    public function openConversation(int $id): void
    {
        $this->conversationId = $id;
        unset($this->activeConversation);
    }

    public function startConversationWith(int $userId): void
    {
        $conversation = PulseConversation::directBetween(Auth::id(), $userId);
        $this->conversationId = $conversation->id;
        $this->searchUser     = '';
        unset($this->conversations, $this->activeConversation, $this->userSearchResults);
    }

    public function sendMessage(): void
    {
        $this->validate(['messageBody' => 'required|min:1|max:4000']);

        if (! $this->conversationId) {
            return;
        }

        app(SendMessage::class)->handle(
            conversationId: $this->conversationId,
            senderId:       Auth::id(),
            body:           $this->messageBody,
        );

        $this->messageBody = '';
        unset($this->activeConversation, $this->conversations);
    }

    public function handleNewMessage(): void
    {
        if (! $this->conversationId) {
            return;
        }
        unset($this->activeConversation, $this->conversations);
    }

    /**
     * Override getListeners so Livewire does not try to evaluate {conversationId}
     * as a placeholder when it is null.
     */
    public function getListeners(): array
    {
        if (! $this->conversationId) {
            return [];
        }

        return [
            "echo-private:conversation.{$this->conversationId},MessageSent" => 'handleNewMessage',
        ];
    }

    public function otherParticipant(PulseConversation $conversation): ?User
    {
        return $conversation->participants
            ->firstWhere('id', '!=', Auth::id());
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.messaging');
    }
}
