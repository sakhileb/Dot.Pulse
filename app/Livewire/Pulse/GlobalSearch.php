<?php

namespace App\Livewire\Pulse;

use App\Models\Community;
use App\Models\PulsePost;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class GlobalSearch extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    public string $tab = 'posts';

    #[Computed]
    public function results(): array
    {
        if (strlen(trim($this->query)) < 2) {
            return ['posts' => collect(), 'communities' => collect(), 'users' => collect()];
        }

        $q = '%'.$this->query.'%';

        return [
            'posts' => PulsePost::with(['author', 'community'])
                ->published()
                ->where(function ($builder) use ($q) {
                    $builder->where('title', 'like', $q)
                        ->orWhere('body', 'like', $q);
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),

            'communities' => Community::where('visibility', 'public')
                ->where(function ($builder) use ($q) {
                    $builder->where('name', 'like', $q)
                        ->orWhere('description', 'like', $q)
                        ->orWhere('industry', 'like', $q);
                })
                ->limit(10)
                ->get(),

            'users' => User::where('name', 'like', $q)
                ->limit(10)
                ->get(),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        return view('livewire.pulse.global-search');
    }
}
