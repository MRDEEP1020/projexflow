<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    {{-- ── Welcome banner ─────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-['Syne'] font-extrabold text-2xl text-white tracking-tight">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }},
                {{ Auth::user()->name }} 👋
            </h1>
            <p class="text-sm text-gray-400 mt-1">Here's what's happening across your projects today.</p>
        </div>
        <div class="flex gap-3">
            <flux:button variant="outline" size="sm" icon="plus" href="{{ route('backend.projectCreate') }}" wire:navigate>
                New project
            </flux:button>
            <flux:button variant="primary" size="sm" icon="magnifying-glass" href="{{ route('client.marketplace') }}" wire:navigate>
                Find freelancers
            </flux:button>
        </div>
    </div>

    {{-- ── Stats Grid ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Spent this month -->
        <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-5">
            <p class="text-xs font-['DM_Mono'] uppercase tracking-wider text-gray-500">SPENT THIS MONTH</p>
            <p class="font-['Syne'] font-extrabold text-3xl text-white mt-2">${{ number_format($this->spending['this_month'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">in completed payments</p>
        </div>

        <!-- In active escrow -->
        <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-5">
            <p class="text-xs font-['DM_Mono'] uppercase tracking-wider text-gray-500">IN ACTIVE ESCROW</p>
            <p class="font-['Syne'] font-extrabold text-3xl text-amber-400 mt-2">${{ number_format($this->spending['active_escrow'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">held in contracts</p>
        </div>

        <!-- Active contracts -->
        <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-5">
            <p class="text-xs font-['DM_Mono'] uppercase tracking-wider text-gray-500">ACTIVE CONTRACTS</p>
            <p class="font-['Syne'] font-extrabold text-3xl text-blue-400 mt-2">{{ $this->spending['open_contracts'] }}</p>
            <p class="text-xs text-gray-500 mt-1">currently running</p>
        </div>

        <!-- Total spent -->
        <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-5">
            <p class="text-xs font-['DM_Mono'] uppercase tracking-wider text-gray-500">TOTAL SPENT</p>
            <p class="font-['Syne'] font-extrabold text-3xl text-gray-400 mt-2">${{ number_format($this->spending['total'], 0) }}</p>
            <p class="text-xs text-gray-500 mt-1">all time on platform</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- ── Left Column (2/3 width on desktop) ────────────────── --}}
        <div class="lg:col-span-2 space-y-6">
            <!-- Active Projects Section -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-['Syne'] font-bold text-lg text-white">Active projects</h2>
                    <a href="{{ route('backend.projectList') }}" wire:navigate class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                        View all →
                    </a>
                </div>

                @forelse($this->myProjects as $p)
                    <a href="{{ route('backend.projectBoard', $p->id) }}" wire:navigate 
                       class="block bg-[#0e1420] border border-gray-800 rounded-xl p-4 mb-3 hover:border-gray-700 transition-all">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <div class="w-2 h-2 rounded-full 
                                        {{ match($p->status) {
                                            'active' => 'bg-green-400',
                                            'planning' => 'bg-blue-400',
                                            'on_hold' => 'bg-amber-400',
                                            default => 'bg-gray-500'
                                        } }}"></div>
                                    <p class="text-sm font-semibold text-white font-['Syne']">{{ $p->name }}</p>
                                    <flux:badge size="sm" :color="match($p->status) {
                                        'active' => 'green',
                                        'planning' => 'blue',
                                        'on_hold' => 'yellow',
                                        default => 'gray'
                                    }">{{ ucfirst(str_replace('_', ' ', $p->status)) }}</flux:badge>
                                </div>
                                <div class="flex items-center gap-3 mt-3">
                                    <div class="flex-1">
                                        <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-blue-500 rounded-full transition-all" style="width: {{ $p->progress }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-xs font-['DM_Mono'] text-gray-500">{{ $p->progress }}%</span>
                                </div>
                                <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                    <span>{{ $p->tasks_count }} tasks</span>
                                    @if($p->due_date)
                                        <span class="{{ $p->due_date->isPast() ? 'text-red-400' : '' }}">
                                            Due {{ $p->due_date->format('M d') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="bg-[#0e1420] border border-dashed border-gray-800 rounded-xl p-8 text-center">
                        <flux:icon.rectangle-group class="size-10 text-gray-600 mx-auto mb-3"/>
                        <p class="text-sm text-gray-500">No active projects yet.</p>
                        <flux:button variant="primary" size="sm" icon="plus" href="{{ route('backend.projectCreate') }}" wire:navigate class="mt-3">
                            Create first project
                        </flux:button>
                    </div>
                @endforelse
            </div>

            <!-- Active Contracts Section -->
            @if($this->activeContracts->isNotEmpty())
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-['Syne'] font-bold text-lg text-white">Active contracts</h2>
                        <a href="{{ route('backend.contracts') }}" wire:navigate class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                            View all →
                        </a>
                    </div>
                    @foreach($this->activeContracts as $c)
                        <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-4 mb-3 {{ $c->status === 'submitted' ? 'border-amber-500/30' : '' }}">
                            <div class="flex items-center gap-3">
                                <flux:avatar name="{{ $c->freelancer->name }}" size="sm" src="{{ $c->freelancer->avatar_url ?? '' }}" class="flex-shrink-0"/>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate">{{ $c->title }}</p>
                                    <p class="text-xs text-gray-500">with {{ $c->freelancer->name }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-mono font-bold text-white">${{ number_format($c->total_amount) }}</p>
                                    <flux:badge size="sm" :color="match($c->status) {
                                        'active' => 'green',
                                        'submitted' => 'yellow',
                                        'draft' => 'gray',
                                        default => 'gray'
                                    }">{{ ucfirst($c->status) }}</flux:badge>
                                </div>
                                @if($c->status === 'submitted')
                                    <a href="{{ route('backend.contracts') }}" wire:navigate class="px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/25 text-xs font-medium hover:bg-amber-500/20 transition-colors flex-shrink-0">
                                        Review →
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Right Column (1/3 width on desktop) ───────────────── --}}
        <div class="space-y-6">
            <!-- Urgent Tasks -->
            <div>
                <h3 class="font-['Syne'] font-bold text-base text-white mb-3">Urgent tasks</h3>
                @forelse($this->urgentTasks as $t)
                    <div class="flex items-start gap-2.5 mb-3">
                        <div class="w-4 h-4 rounded border border-gray-700 flex-shrink-0 mt-0.5"></div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-200 truncate">{{ $t->title }}</p>
                            <p class="text-xs {{ $t->due_date->isPast() ? 'text-red-400' : 'text-gray-500' }}">
                                {{ $t->due_date->isPast() ? 'Overdue · ' : '' }}{{ $t->due_date->format('M d') }}
                                · {{ $t->project->name }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="bg-[#0e1420] border border-gray-800 rounded-xl p-6 text-center">
                        <flux:icon.check-circle class="size-8 text-gray-600 mx-auto mb-2"/>
                        <p class="text-sm text-gray-500">No urgent tasks 🎉</p>
                    </div>
                @endforelse
            </div>

            <!-- Open Job Posts -->
            @if($this->openJobs->isNotEmpty())
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-['Syne'] font-bold text-base text-white">My job posts</h3>
                        <a href="{{ route('backend.myJobs') }}" wire:navigate class="text-xs text-blue-400 hover:text-blue-300">All →</a>
                    </div>
                    @foreach($this->openJobs as $j)
                        <div class="flex items-center justify-between bg-[#0e1420] border border-gray-800 rounded-lg p-3 mb-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-200 truncate">{{ $j->title }}</p>
                                <p class="text-xs text-gray-500">{{ $j->applications_count }} applicants</p>
                            </div>
                            <a href="{{ route('backend.jobPostDetail', $j->id) }}" wire:navigate class="text-xs px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 ml-2 flex-shrink-0 transition-colors">
                                Review
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Upcoming Bookings -->
            @if($this->upcomingBookings->isNotEmpty())
                <div>
                    <h3 class="font-['Syne'] font-bold text-base text-white mb-3">Upcoming sessions</h3>
                    @foreach($this->upcomingBookings as $b)
                        <div class="flex items-center gap-3 bg-[#0e1420] border border-gray-800 rounded-xl p-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/15 flex flex-col items-center justify-center flex-shrink-0">
                                <span class="text-[9px] font-['DM_Mono'] text-blue-400 uppercase">{{ \Carbon\Carbon::parse($b->start_at)->format('M') }}</span>
                                <span class="font-['Syne'] font-extrabold text-base text-white leading-none">{{ \Carbon\Carbon::parse($b->start_at)->format('d') }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate">with {{ $b->provider->name }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($b->start_at)->format('g:i A') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Quick Actions -->
            <div>
                <h3 class="font-['Syne'] font-bold text-base text-white mb-3">Quick actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('backend.jobPostCreate') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 bg-[#0e1420] border border-gray-800 rounded-xl text-sm text-gray-400 hover:text-white hover:border-gray-700 transition-all">
                        <flux:icon.plus class="size-4 flex-shrink-0"/>
                        Post a job
                    </a>
                    <a href="{{ route('client.marketplace') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 bg-[#0e1420] border border-gray-800 rounded-xl text-sm text-gray-400 hover:text-white hover:border-gray-700 transition-all">
                        <flux:icon.magnifying-glass class="size-4 flex-shrink-0"/>
                        Find freelancers
                    </a>
                    <a href="{{ route('backend.contracts') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 bg-[#0e1420] border border-gray-800 rounded-xl text-sm text-gray-400 hover:text-white hover:border-gray-700 transition-all">
                        <flux:icon.document-text class="size-4 flex-shrink-0"/>
                        Create contract
                    </a>
                    <a href="{{ route('backend.projectCreate') }}" wire:navigate class="flex items-center gap-3 px-3 py-2.5 bg-[#0e1420] border border-gray-800 rounded-xl text-sm text-gray-400 hover:text-white hover:border-gray-700 transition-all">
                        <flux:icon.rectangle-group class="size-4 flex-shrink-0"/>
                        New project
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>