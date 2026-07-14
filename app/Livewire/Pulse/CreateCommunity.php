<?php

namespace App\Livewire\Pulse;

use App\Actions\Pulse\CreateCommunity as CreateCommunityAction;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CreateCommunity extends Component
{
    public string $name        = '';
    public string $description = '';
    public string $industry    = '';
    public string $visibility  = 'public';

    public static array $industries = [
        'Agriculture', 'AI & Machine Learning', 'Automation',
        'Construction', 'CRM', 'Developers',
        'ERP', 'Fleet Management', 'Healthcare',
        'Logistics', 'Manufacturing', 'Mining',
        'Startups', 'SaaS', 'Other',
    ];

    protected function rules(): array
    {
        return [
            'name'        => 'required|min:3|max:80|unique:communities,name',
            'description' => 'nullable|max:500',
            'industry'    => 'nullable|max:60',
            'visibility'  => 'required|in:public,private,enterprise',
        ];
    }

    public function create(): void
    {
        $this->validate();

        $community = app(CreateCommunityAction::class)->handle(
            userId:      Auth::id(),
            name:        $this->name,
            description: $this->description,
            industry:    $this->industry,
            visibility:  $this->visibility,
            teamId:      Auth::user()->currentTeam?->id,
        );

        $this->redirect(route('communities.show', $community->slug), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.pulse.create-community');
    }
}
