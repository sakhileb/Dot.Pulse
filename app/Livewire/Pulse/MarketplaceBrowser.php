<?php

namespace App\Livewire\Pulse;

use App\Models\PulseMarketplaceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MarketplaceBrowser extends Component
{
    public string $search = '';

    public string $category = '';

    public static array $categories = [
        'agent', 'template', 'dashboard', 'prompt_library',
        'automation', 'integration', 'extension', 'workflow',
    ];

    #[Computed]
    public function items(): Collection
    {
        return PulseMarketplaceItem::with('author')
            ->where('is_published', true)
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, fn ($q) => $q->where(function ($b) {
                $b->where('title', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%');
            }))
            ->orderByDesc('installs_count')
            ->limit(24)
            ->get();
    }

    public function install(int $itemId): void
    {
        $item = PulseMarketplaceItem::findOrFail($itemId);

        $alreadyInstalled = $item->installs()->where('user_id', Auth::id())->exists();

        if (! $alreadyInstalled) {
            $item->installs()->create(['user_id' => Auth::id()]);
            $item->increment('installs_count');
        }

        unset($this->items);
    }

    public function render(): View
    {
        return view('livewire.pulse.marketplace-browser');
    }
}
