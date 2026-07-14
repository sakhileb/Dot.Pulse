<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\PublishMarketplaceItem;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CreateMarketplaceItem extends Component
{
    public string $title       = '';
    public string $description = '';
    public string $category    = '';
    public string $version     = '1.0.0';

    protected function rules(): array
    {
        return [
            'title'       => 'required|min:3|max:120',
            'description' => 'required|min:10|max:1000',
            'category'    => 'required|in:agent,template,dashboard,prompt_library,automation,integration,extension,workflow',
            'version'     => 'required|regex:/^\d+\.\d+\.\d+$/',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $item = app(PublishMarketplaceItem::class)->handle(
            userId:      Auth::id(),
            title:       $this->title,
            description: $this->description,
            category:    $this->category,
            version:     $this->version,
        );

        $this->redirect(route('marketplace.index'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.create-marketplace-item');
    }
}
