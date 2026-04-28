<div class="max-w-6xl space-y-6">

    {{-- ── Welcome ─────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:24px;color:#fff;letter-spacing:-.3px">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
                {{ Auth::user()->name }} 👋
            </h1>
            <p class="text-sm text-[#8da0b8] mt-1">
                @if($this->profileHealth['available'] === 'open_to_work')
                    Your profile is <span class="text-[#7EE8A2] font-medium">visible</span> to clients · {{ $this->profileHealth['score'] }}% complete
                @else
                    Your profile is <span class="text-amber-400 font-medium">{{ ucfirst(str_replace('_',' ',$this->profileHealth['available'] ?? 'unavailable')) }}</span>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" size="sm" icon="magnifying-glass"
                href="{{ route('backend.jobBoard') }}" wire:navigate>
                Browse jobs
            </flux:button>
            <flux:button variant="primary" size="sm" icon="user-circle"
                href="{{ route('backend.editProfile') }}" wire:navigate>
                My profile
            </flux:button>
        </div>
    </div>

    {{-- ── Today's meetings banner ────────────────────────────── --}}
    @if($this->todayBookings->isNotEmpty())
        <div class="flex items-center gap-4 p-4 bg-[#7EE8A2]/06 border border-[#7EE8A2]/25 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/20 flex items-center justify-center flex-shrink-0">
                <flux:icon.video-camera class="size-5 text-[#7EE8A2]"/>
            </div>
            <div class="flex-1">
                <p class="text-sm font-semibold text-white">{{ $this->todayBookings->count() }} meeting{{ $this->todayBookings->count() > 1 ? 's' : '' }} today</p>
                <p class="text-xs text-[#8da0b8]">
                    @foreach($this->todayBookings as $b)
                        {{ $b->title }} at {{ \Carbon\Carbon::parse($b->start_at)->format('H:i') }}
                        @if(! $loop->last) · @endif
                    @endforeach
                </p>
            </div>
            @if($this->todayBookings->first()->meetingRoom)
                <a href="{{ route('backend.meetingRoom', $this->todayBookings->first()->meetingRoom->room_token) }}"
                   wire:navigate
                   class="flex-shrink-0 px-4 py-2 rounded-xl bg-[#7EE8A2] text-[#080c14] text-sm font-bold hover:bg-[#9ef7b8] transition-colors">
                    Join now
                </a>
            @endif
        </div>
    @endif

    {{-- ── Earnings KPIs ────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach([
            ['label' => 'Available balance', 'value' => '$'.number_format($this->earnings['available'],2), 'color' => 'text-[#7EE8A2]', 'sub' => 'ready to withdraw'],
            ['label' => 'Earned this month',  'value' => '$'.number_format($this->earnings['this_month'],2), 'color' => 'text-white',    'sub' => 'from completed work'],
            ['label' => 'Pending release',    'value' => '$'.number_format($this->earnings['pending'],2),    'color' => 'text-amber-400', 'sub' => 'awaiting approval'],
            ['label' => 'Total earned',       'value' => '$'.number_format($this->earnings['total_earned'],2),'color' => 'text-[#8da0b8]','sub' => 'all time'],
        ] as $kpi)
            <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">{{ $kpi['label'] }}</p>
                <p style="font-family:'Syne',sans-serif;font-weight:800;font-size:24px;line-height:1.1;margin-top:6px"
                   class="{{ $kpi['color'] }}">{{ $kpi['value'] }}</p>
                <p class="text-[10px] text-[#506070] mt-1">{{ $kpi['sub'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Left: Tasks + Contracts ─────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- My tasks --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff">My tasks</h2>
                    <a href="{{ route('my-tasks') }}" wire:navigate class="text-xs text-[#7EE8A2] hover:underline">View all →</a>
                </div>
                @forelse($this->myTasks as $t)
                    <div class="flex items-center gap-3 mb-2.5" wire:key="t-{{ $t->id }}">
                        <div class="w-4 h-4 rounded-sm border-2 flex-shrink-0 mt-0.5
                            {{ $t->due_date->isPast() ? 'border-red-400' : 'border-[#254060]' }}">
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-[#dde6f0] truncate">{{ $t->title }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] text-[#506070]">{{ $t->project->name }}</span>
                                <span class="text-[10px] {{ $t->due_date->isPast() ? 'text-red-400 font-semibold' : 'text-[#506070]' }}">
                                    {{ $t->due_date->isPast() ? '⚠ Overdue · ' : '' }}{{ $t->due_date->format('M d') }}
                                </span>
                            </div>
                        </div>
                        <flux:badge size="sm"
                            :color="match($t->priority) {
                                'critical' => 'red',
                                'high'     => 'orange',
                                'medium'   => 'yellow',
                                default    => 'zinc',
                            }">{{ ucfirst($t->priority) }}</flux:badge>
                    </div>
                @empty
                    <div class="flex items-center justify-center py-8 text-sm text-[#506070] bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-xl">
                        No pending tasks 🎉
                    </div>
                @endforelse
            </div>

            {{-- Active contracts --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff">Active contracts</h2>
                    <a href="{{ route('backend.contracts') }}" wire:navigate class="text-xs text-[#7EE8A2] hover:underline">View all →</a>
                </div>
                @forelse($this->activeContracts as $c)
                    <a href="{{ route('backend.contracts') }}" wire:navigate
                       class="flex items-center gap-3 mb-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-3.5 hover:border-[#254060] transition-all block"
                       wire:key="c-{{ $c->id }}">
                        <flux:avatar name="{{ $c->client->name }}" src="{{ $c->client->avatar_url }}" size="sm" class="flex-shrink-0"/>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ $c->title }}</p>
                            <p class="text-xs text-[#506070]">with {{ $c->client->name }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-mono font-bold text-[#7EE8A2]">${{ number_format($c->total_amount) }}</p>
                            <flux:badge size="sm"
                                :color="match($c->status) {
                                    'active'    => 'green',
                                    'submitted' => 'yellow',
                                    default     => 'zinc',
                                }">{{ ucfirst($c->status) }}</flux:badge>
                        </div>
                    </a>
                @empty
                    <div class="flex flex-col items-center gap-3 py-8 text-center bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-xl">
                        <p class="text-sm text-[#506070]">No active contracts.</p>
                        <a href="{{ route('backend.jobBoard') }}" wire:navigate
                           class="text-xs text-[#7EE8A2] hover:underline">Browse jobs →</a>
                    </div>
                @endforelse
            </div>

            {{-- My applications --}}
            @if($this->myApplications->isNotEmpty())
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:16px;color:#fff">Recent applications</h2>
                        <a href="{{ route('backend.myApplications') }}" wire:navigate class="text-xs text-[#7EE8A2] hover:underline">View all →</a>
                    </div>
                    @foreach($this->myApplications as $a)
                        <div class="flex items-center gap-3 mb-2.5 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-3
                            {{ $a->status === 'shortlisted' ? 'border-amber-500/30' : '' }}"
                             wire:key="a-{{ $a->id }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-[#dde6f0] truncate">{{ $a->jobPost->title }}</p>
                                <p class="text-[10px] text-[#506070]">{{ $a->jobPost->client->name }} · Applied {{ $a->created_at->diffForHumans() }}</p>
                            </div>
                            <flux:badge size="sm"
                                :color="match($a->status) {
                                    'shortlisted' => 'yellow',
                                    'hired'       => 'green',
                                    'rejected'    => 'red',
                                    default       => 'zinc',
                                }">{{ ucfirst($a->status) }}</flux:badge>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Right sidebar ────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Profile health --}}
            <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:#fff">Profile health</h3>
                    <span class="font-mono text-sm text-[#7EE8A2]">{{ $this->profileHealth['score'] }}%</span>
                </div>
                <div class="h-2 bg-[#1c2e45] rounded-full overflow-hidden">
                    <div class="h-full bg-[#7EE8A2] rounded-full transition-all"
                         style="width:{{ $this->profileHealth['score'] }}%"></div>
                </div>
                @foreach([
                    ['key' => 'headline',  'label' => 'Headline'],
                    ['key' => 'bio',       'label' => 'Bio'],
                    ['key' => 'skills',    'label' => 'Skills'],
                    ['key' => 'rate',      'label' => 'Hourly rate'],
                    ['key' => 'avatar',    'label' => 'Profile photo'],
                    ['key' => 'portfolio', 'label' => 'Portfolio'],
                ] as $check)
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded-sm flex items-center justify-center flex-shrink-0
                            {{ $this->profileHealth['checks'][$check['key']] ? 'bg-[#7EE8A2]/15 text-[#7EE8A2]' : 'bg-[#1c2e45] text-[#506070]' }}">
                            @if($this->profileHealth['checks'][$check['key']])
                                <flux:icon.check class="size-2.5"/>
                            @endif
                        </div>
                        <span class="text-xs {{ $this->profileHealth['checks'][$check['key']] ? 'text-[#8da0b8]' : 'text-[#506070]' }}">
                            {{ $check['label'] }}
                        </span>
                    </div>
                @endforeach

                @if($this->profileHealth['score'] < 100)
                    <a href="{{ route('backend.editProfile') }}" wire:navigate
                       class="block text-center text-xs text-[#7EE8A2] hover:underline mt-1">
                        Complete profile →
                    </a>
                @endif
            </div>

            {{-- Rating snapshot --}}
            @if($this->profileHealth['reviews'] > 0)
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4">
                    <h3 style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:#fff" class="mb-3">My rating</h3>
                    <div class="flex items-center gap-3 mb-3">
                        <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:36px;color:#fff;line-height:1">
                            {{ number_format($this->profileHealth['rating'],1) }}
                        </span>
                        <div>
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg width="14" height="14" viewBox="0 0 24 24"
                                         fill="{{ $i <= round($this->profileHealth['rating']) ? '#f59e0b' : '#1c2e45' }}">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-xs text-[#506070] mt-0.5">{{ $this->profileHealth['reviews'] }} reviews</p>
                        </div>
                    </div>
                    @foreach($this->recentReviews as $r)
                        <div class="mb-3 pb-3 border-b border-[#1c2e45] last:border-b-0 last:pb-0 last:mb-0">
                            <p class="text-xs text-[#8da0b8] line-clamp-2 italic">"{{ $r->body }}"</p>
                            <p class="text-[10px] text-[#506070] mt-1">— {{ $r->reviewer?->name }} · {{ $r->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Upcoming sessions --}}
            @if($this->upcomingBookings->isNotEmpty())
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-4 space-y-3">
                    <h3 style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:#fff">Upcoming sessions</h3>
                    @foreach($this->upcomingBookings as $b)
                        <div class="flex items-center gap-2.5" wire:key="ub-{{ $b->id }}">
                            <div class="w-9 h-9 rounded-lg bg-[#7EE8A2]/08 border border-[#7EE8A2]/15 flex flex-col items-center justify-center flex-shrink-0">
                                <span class="text-[8px] font-mono text-[#7EE8A2] uppercase">{{ \Carbon\Carbon::parse($b->start_at)->format('M') }}</span>
                                <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:#fff;line-height:1">
                                    {{ \Carbon\Carbon::parse($b->start_at)->format('d') }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-white truncate">{{ $b->title }}</p>
                                <p class="text-[10px] text-[#506070]">
                                    {{ \Carbon\Carbon::parse($b->start_at)->format('H:i') }}
                                    @if($b->client_name) · {{ $b->client_name }} @endif
                                </p>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('backend.bookingInbox') }}" wire:navigate
                       class="block text-center text-xs text-[#7EE8A2] hover:underline">
                        Manage bookings →
                    </a>
                </div>
            @endif

            {{-- Quick actions --}}
            <div class="space-y-2">
                <h3 style="font-family:'Syne',sans-serif;font-weight:700;font-size:14px;color:#fff" class="mb-3">Quick actions</h3>
                @foreach([
                    ['route' => 'backend.jobBoard',          'icon' => 'magnifying-glass', 'label' => 'Find jobs to apply'],
                    ['route' => 'backend.editProfile',       'icon' => 'user-circle',      'label' => 'Update my profile'],
                    ['route' => 'backend.availabilitySettings','icon' => 'calendar',       'label' => 'Set availability'],
                    ['route' => 'backend.wallet',            'icon' => 'banknotes',        'label' => 'Withdraw earnings'],
                ] as $action)
                    <a href="{{ route($action['route']) }}" wire:navigate
                       class="flex items-center gap-2.5 px-3 py-2.5 bg-[#0e1420] border border-[#1c2e45] rounded-xl text-sm text-[#8da0b8] hover:text-[#dde6f0] hover:border-[#254060] transition-all">
                        <flux:icon :name="$action['icon']" class="size-4 flex-shrink-0 text-[#7EE8A2]"/>
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
