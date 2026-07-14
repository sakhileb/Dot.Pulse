<div>
    {{-- Filters --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;">
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            @foreach(['all' => 'All', 'agent' => 'Agents', 'template' => 'Templates', 'automation' => 'Automations', 'integration' => 'Integrations', 'workflow' => 'Workflows', 'dashboard' => 'Dashboards', 'extension' => 'Extensions'] as $key => $label)
                <button wire:click="$set('category','{{ $key === 'all' ? '' : $key }}')" style="padding:5px 12px;font-size:11px;font-weight:600;border-radius:100px;border:1px solid {{ ($key === 'all' && $category === '') || $category === $key ? 'rgba(192,132,252,0.35)' : 'rgba(255,255,255,0.07)' }};background:{{ ($key === 'all' && $category === '') || $category === $key ? 'rgba(192,132,252,0.1)' : 'transparent' }};color:{{ ($key === 'all' && $category === '') || $category === $key ? '#c084fc' : '#71717a' }};cursor:pointer;transition:all .13s;">
                    {{ $label }}
                </button>
            @endforeach
        </div>
        <div style="position:relative;">
            <span class="material-symbols-rounded" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:15px;color:#52525b;pointer-events:none;">search</span>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search marketplace…" class="dot-input" style="padding-left:32px;font-size:12px;width:200px;" />
        </div>
    </div>

    @if($this->items->isEmpty())
        <div style="text-align:center;padding:4rem;color:#3f3f46;">
            <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:0.5rem;">storefront</span>
            <p style="font-size:13px;">No marketplace items yet.</p>
        </div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:0.875rem;">
            @foreach($this->items as $item)
                @php
                    $catColors = ['agent'=>'#c084fc','template'=>'#60a5fa','dashboard'=>'#34d399','prompt_library'=>'#fbbf24','automation'=>'#fb923c','integration'=>'#2dd4bf','extension'=>'#818cf8','workflow'=>'#f472b6'];
                    $catColor = $catColors[$item->category] ?? '#71717a';
                    $catIcons = ['agent'=>'smart_toy','template'=>'description','dashboard'=>'dashboard','prompt_library'=>'chat','automation'=>'auto_mode','integration'=>'extension','extension'=>'puzzle_piece','workflow'=>'account_tree'];
                    $catIcon = $catIcons[$item->category] ?? 'category';
                @endphp
                <div class="dot-card" style="padding:1.25rem;">
                    <div style="display:flex;align-items:flex-start;gap:0.75rem;margin-bottom:0.875rem;">
                        <div style="width:42px;height:42px;border-radius:10px;background:{{ $catColor }}15;border:1px solid {{ $catColor }}25;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <span class="material-symbols-rounded" style="font-size:20px;color:{{ $catColor }};">{{ $catIcon }}</span>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#f4f4f5;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $item->title }}</div>
                            <div style="font-size:11px;color:#52525b;">by {{ $item->author->name }}</div>
                        </div>
                    </div>
                    @if($item->description)
                        <p style="font-size:12px;color:#71717a;margin:0 0 0.875rem;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $item->description }}</p>
                    @endif
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:10px;font-size:11px;color:#52525b;">
                            <span style="display:flex;align-items:center;gap:3px;">
                                <span class="material-symbols-rounded" style="font-size:12px;">download</span>
                                {{ number_format($item->installs_count) }}
                            </span>
                            @if($item->avg_rating > 0)
                                <span style="display:flex;align-items:center;gap:3px;color:#fbbf24;">
                                    <span class="material-symbols-rounded" style="font-size:12px;">star</span>
                                    {{ number_format($item->avg_rating, 1) }}
                                </span>
                            @endif
                            <span style="font-size:10px;padding:2px 6px;border-radius:4px;background:{{ $catColor }}10;color:{{ $catColor }};font-weight:600;">{{ str_replace('_',' ',ucfirst($item->category)) }}</span>
                        </div>
                        <button wire:click="install({{ $item->id }})" class="dot-btn dot-btn-primary" style="font-size:11px;padding:5px 12px;">
                            <span class="material-symbols-rounded" style="font-size:13px;">download</span>
                            Install
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
