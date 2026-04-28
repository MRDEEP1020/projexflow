<div class="space-y-6">

    {{-- Period selector --}}
    <div class="flex items-center justify-between">
        <p class="text-xs text-[#506070]">Platform overview</p>
        <flux:button.group>
            @foreach(['7'=>'7d','30'=>'30d','90'=>'90d'] as $val => $lbl)
                <flux:button size="sm"
                    :variant="$period === '{{ $val }}' ? 'filled' : 'ghost'"
                    wire:click="$set('period','{{ $val }}')">{{ $lbl }}</flux:button>
            @endforeach
        </flux:button.group>
    </div>

    {{-- ── KPI grid ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Revenue --}}
        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Platform revenue</p>
            <p style="font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#7EE8A2;line-height:1.1;margin-top:6px">
                ${{ number_format($this->kpis['revenue'], 0) }}
            </p>
            @php $d = $this->kpis['revenue_delta']; @endphp
            <p class="text-xs mt-1 {{ $d >= 0 ? 'text-[#7EE8A2]' : 'text-red-400' }}">
                {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}% vs prev period
            </p>
        </div>

        {{-- New users --}}
        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">New users</p>
            <p style="font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#fff;line-height:1.1;margin-top:6px">
                {{ number_format($this->kpis['new_users']) }}
            </p>
            @php $d = $this->kpis['user_delta']; @endphp
            <p class="text-xs mt-1 {{ $d >= 0 ? 'text-[#7EE8A2]' : 'text-red-400' }}">
                {{ $d >= 0 ? '↑' : '↓' }} {{ abs($d) }}% vs prev period
            </p>
        </div>

        {{-- GMV --}}
        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">GMV (contracts)</p>
            <p style="font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#60a5fa;line-height:1.1;margin-top:6px">
                ${{ number_format($this->kpis['gmv'], 0) }}
            </p>
            <p class="text-xs text-[#506070] mt-1">{{ $this->kpis['contracts'] }} contracts created</p>
        </div>

        {{-- Alerts --}}
        <div class="bg-[#0e1420] border rounded-2xl p-4
            {{ $this->kpis['open_disputes'] > 0 ? 'border-red-500/30 bg-red-500/03' : 'border-[#1c2e45]' }}">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Needs attention</p>
            <div class="mt-2 space-y-1.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8da0b8]">Open disputes</span>
                    <span class="text-sm font-bold {{ $this->kpis['open_disputes'] > 0 ? 'text-red-400' : 'text-[#506070]' }}">
                        {{ $this->kpis['open_disputes'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-[#8da0b8]">Pending payouts</span>
                    <span class="text-sm font-bold {{ $this->kpis['pending_payouts'] > 0 ? 'text-amber-400' : 'text-[#506070]' }}">
                        {{ $this->kpis['pending_payouts'] }}
                    </span>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Platform totals ─────────────────────────────────── --}}
    <div class="grid grid-cols-4 gap-3">
        @foreach([
            ['label' => 'Total users',       'val' => number_format($this->kpis['total_users']),      'color' => 'text-white'],
            ['label' => 'Freelancers',        'val' => number_format($this->kpis['total_freelancers']),'color' => 'text-[#7EE8A2]'],
            ['label' => 'Organizations',      'val' => number_format($this->kpis['total_orgs']),       'color' => 'text-[#60a5fa]'],
            ['label' => 'Jobs posted',        'val' => number_format($this->kpis['total_jobs']),       'color' => 'text-[#a78bfa]'],
        ] as $stat)
            <div class="bg-[#0e1420] border border-[#1c2e45] rounded-xl px-4 py-3">
                <p class="text-[10px] text-[#506070] font-mono uppercase">{{ $stat['label'] }}</p>
                <p class="font-['Syne'] font-bold text-xl {{ $stat['color'] }} mt-1">{{ $stat['val'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── Open disputes ──────────────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#1c2e45]">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Open disputes</p>
                <a href="{{ route('admin.disputes') }}" class="text-xs text-[#7EE8A2] hover:underline">View all →</a>
            </div>
            @if($this->openDisputesList->isEmpty())
                <div class="flex items-center justify-center h-24 text-sm text-[#506070]">
                    ✓ No open disputes
                </div>
            @else
                <div class="divide-y divide-[#1c2e45]">
                    @foreach($this->openDisputesList as $d)
                        <div class="flex items-center gap-3 px-5 py-3" wire:key="d-{{ $d->id }}">
                            <div class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center flex-shrink-0">
                                <flux:icon.exclamation-triangle class="size-3.5 text-red-400"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-[#dde6f0] truncate">
                                    {{ $d->contract->title ?? 'Contract #'.$d->contract_id }}
                                </p>
                                <p class="text-[10px] text-[#506070]">
                                    {{ $d->contract->client->name ?? '?' }}
                                    vs {{ $d->contract->freelancer->name ?? '?' }}
                                    · {{ $d->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <a href="{{ route('admin.disputes') }}"
                               class="text-[10px] px-2 py-1 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
                                Resolve
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        {{-- ── Pending withdrawals ─────────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b border-[#1c2e45]">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Pending withdrawals</p>
                <a href="{{ route('admin.withdrawals') }}" class="text-xs text-[#7EE8A2] hover:underline">View all →</a>
            </div>
            @if($this->pendingWithdrawals->isEmpty())
                <div class="flex items-center justify-center h-24 text-sm text-[#506070]">
                    ✓ No pending withdrawals
                </div>
            @else
                <div class="divide-y divide-[#1c2e45]">
                    @foreach($this->pendingWithdrawals as $w)
                        <div class="flex items-center gap-3 px-5 py-3" wire:key="w-{{ $w->id }}">
                            <flux:avatar name="{{ $w->user->name }}" size="xs" class="flex-shrink-0"/>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-[#dde6f0] truncate">{{ $w->user->name }}</p>
                                <p class="text-[10px] text-[#506070]">
                                    {{ ucfirst(str_replace('_',' ',$w->method)) }}
                                    · {{ $w->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="font-mono text-sm font-bold text-amber-400">
                                ${{ number_format($w->amount, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        {{-- ── Top categories ──────────────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45]">
            <flux:heading size="sm" class="mb-4">Top freelancer categories</flux:heading>
            <div class="space-y-3">
                @php $maxCount = max(array_column($this->topCategories, 'count') ?: [1]); @endphp
                @foreach($this->topCategories as $cat)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-[#dde6f0]">
                                {{ ucfirst(str_replace('_',' ',$cat['profession_category'])) }}
                            </span>
                            <span class="text-xs font-mono text-[#506070]">
                                {{ $cat['count'] }} ·
                                @if($cat['avg_rate']) ${{ number_format($cat['avg_rate']) }}/hr avg @endif
                            </span>
                        </div>
                        <div class="h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                            <div class="h-full bg-[#7EE8A2] rounded-full transition-all"
                                 style="width:{{ round(($cat['count'] / $maxCount) * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- ── Recent contracts ────────────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-[#1c2e45]">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Recent contracts</p>
            </div>
            <div class="divide-y divide-[#1c2e45]">
                @foreach($this->recentContracts as $c)
                    <div class="flex items-center gap-3 px-5 py-3" wire:key="c-{{ $c->id }}">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-[#dde6f0] truncate">{{ $c->title }}</p>
                            <p class="text-[10px] text-[#506070]">
                                {{ $c->client->name ?? '?' }} → {{ $c->freelancer->name ?? '?' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-mono text-[#7EE8A2]">${{ number_format($c->total_amount) }}</p>
                            <flux:badge size="sm"
                                :color="match($c->status) {
                                    'active'    => 'green',
                                    'completed' => 'lime',
                                    'disputed'  => 'red',
                                    default     => 'zinc',
                                }"
                            >{{ $c->status }}</flux:badge>
                        </div>
                    </div>
                @endforeach
            </div>
        </flux:card>

    </div>
</div>