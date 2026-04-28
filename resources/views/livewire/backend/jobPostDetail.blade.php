<div class="max-w-5xl space-y-6">

    <div class="flex items-center gap-3">
        <flux:button variant="ghost" size="sm" icon="arrow-left"
            href="{{ route('backend.jobBoard') }}" wire:navigate/>
        <flux:heading size="xl" class="flex-1 truncate">{{ $job->title }}</flux:heading>
        <flux:badge size="sm"
            :color="match($job->status) {
                'open'   => 'green',
                'filled' => 'lime',
                'closed' => 'zinc',
                'draft'  => 'yellow',
                default  => 'zinc',
            }"
        >{{ ucfirst($job->status) }}</flux:badge>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ── Main content ────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Description --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
                <flux:heading size="lg">Job description</flux:heading>
                <div class="text-sm text-[#8da0b8] leading-relaxed whitespace-pre-wrap">{{ $job->description }}</div>
            </flux:card>

            {{-- Required skills --}}
            @if(!empty($job->skills_required))
                <flux:card class="bg-[#0e1420] border-[#1c2e45]">
                    <flux:heading size="sm" class="mb-3">Required skills</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach((array)$job->skills_required as $skill)
                            <flux:badge size="sm" color="zinc">{{ $skill }}</flux:badge>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- ── CLIENT: Applicants section ──────────────── --}}
            @if($isOwner)
                <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
                    <div class="px-5 py-4 border-b border-[#1c2e45] flex items-center justify-between">
                        <flux:heading size="lg">
                            Applicants
                            <span class="text-[#506070] font-normal text-sm ml-1">({{ $this->appCounts['all'] }}/{{ $job->max_applicants }})</span>
                        </flux:heading>
                        {{-- Filter tabs --}}
                        <div class="flex gap-1">
                            @foreach(['all'=>'All','pending'=>'Pending','shortlisted'=>'★','rejected'=>'✗'] as $key => $lbl)
                                <button wire:click="$set('appFilter','{{ $key }}')"
                                    class="px-2.5 py-1 rounded-lg text-xs font-medium transition-all
                                        {{ $appFilter === $key
                                            ? 'bg-[#7EE8A2]/10 text-[#7EE8A2] border border-[#7EE8A2]/20'
                                            : 'text-[#506070] hover:text-[#dde6f0]' }}">
                                    {{ $lbl }}
                                    @if($this->appCounts[$key] > 0)
                                        ({{ $this->appCounts[$key] }})
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if($this->applications->isEmpty())
                        <div class="flex flex-col items-center gap-2 py-10 text-center">
                            <flux:icon.user-group class="size-7 text-[#506070]"/>
                            <p class="text-sm text-[#506070]">No applications yet.</p>
                        </div>
                    @else
                        <div class="divide-y divide-[#1c2e45]">
                            @foreach($this->applications as $app)
                                <div class="flex items-start gap-3 px-5 py-4 hover:bg-[#131d2e] transition-colors"
                                     wire:key="app-{{ $app->id }}">
                                    <flux:avatar
                                        src="{{ $app->freelancer->avatar_url }}"
                                        name="{{ $app->freelancer->name }}"
                                        size="sm" class="flex-shrink-0 mt-0.5"/>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a href="{{ route('backend.profilePage', $app->freelancer->name) }}"
                                               wire:navigate
                                               class="text-sm font-semibold text-white hover:text-[#7EE8A2] transition-colors">
                                                {{ $app->freelancer->name }}
                                            </a>
                                            <flux:badge size="sm"
                                                :color="match($app->status) {
                                                    'shortlisted' => 'green',
                                                    'rejected'    => 'red',
                                                    'hired'       => 'lime',
                                                    default       => 'zinc',
                                                }"
                                            >{{ ucfirst($app->status) }}</flux:badge>
                                            @if($app->freelancer->serviceProfile?->is_verified)
                                                <flux:icon.check-badge class="size-3.5 text-[#7EE8A2]"/>
                                            @endif
                                        </div>
                                        @if($app->freelancer->serviceProfile?->headline)
                                            <p class="text-xs text-[#506070] mt-0.5">
                                                {{ $app->freelancer->serviceProfile->headline }}
                                            </p>
                                        @endif
                                        <p class="text-xs text-[#8da0b8] mt-1.5 line-clamp-2">
                                            {{ $app->cover_letter }}
                                        </p>
                                        <div class="flex items-center gap-3 mt-1.5 text-[11px] text-[#506070]">
                                            @if($app->proposed_rate)
                                                <span class="text-[#7EE8A2] font-mono">
                                                    {{ $job->currency }} {{ number_format($app->proposed_rate) }}
                                                    {{ $job->type === 'hourly' ? '/hr' : '' }}
                                                </span>
                                            @endif
                                            @if($app->availability)
                                                <span>Starts: {{ ucfirst(str_replace('_',' ',$app->availability)) }}</span>
                                            @endif
                                            <span>Applied {{ $app->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    {{-- Actions --}}
                                    <div class="flex gap-1 flex-shrink-0">
                                        <flux:button variant="ghost" size="sm"
                                            wire:click="$set('viewAppId', {{ $app->id }})"
                                            icon="eye" title="View full application"/>
                                        @if($app->status === 'pending')
                                            <flux:button variant="ghost" size="sm" icon="star"
                                                wire:click="shortlist({{ $app->id }})"
                                                class="text-amber-400" title="Shortlist"/>
                                            <flux:button variant="ghost" size="sm" icon="x-mark"
                                                wire:click="reject({{ $app->id }})"
                                                class="text-[#506070] hover:text-red-400" title="Reject"/>
                                        @endif
                                        @if(in_array($app->status, ['pending','shortlisted']))
                                            <flux:button variant="primary" size="sm" icon="check"
                                                wire:click="hire({{ $app->id }})"
                                                wire:confirm="Hire {{ $app->freelancer->name }} for this job?"
                                            >Hire</flux:button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </flux:card>
            @else
                {{-- ── FREELANCER: already applied notice ────── --}}
                @if($hasApplied)
                    <flux:callout icon="check-circle" color="green">
                        <flux:callout.heading>You've applied to this job</flux:callout.heading>
                        <flux:callout.text>
                            The client is reviewing applications.
                            Check your <a href="{{ route('backend.myApplications') }}" wire:navigate class="underline text-[#7EE8A2]">My Applications</a> page for updates.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            @endif
        </div>

        {{-- ── Sidebar ──────────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Budget & type --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
                <div>
                    @if($job->budget_min || $job->budget_max)
                        <p class="font-['Syne'] text-2xl font-extrabold text-[#7EE8A2]">
                            {{ $job->currency }}
                            {{ $job->budget_min ? number_format($job->budget_min) : '' }}
                            @if($job->budget_min && $job->budget_max) – @endif
                            {{ $job->budget_max ? number_format($job->budget_max) : '' }}
                        </p>
                        <p class="text-xs text-[#506070] mt-0.5">
                            {{ $job->type === 'hourly' ? 'Hourly rate' : 'Fixed price' }}
                        </p>
                    @else
                        <p class="text-sm text-[#506070]">Budget: Not disclosed</p>
                    @endif
                </div>

                <div class="space-y-2 text-xs text-[#8da0b8]">
                    @foreach([
                        ['icon'=>'tag',      'label'=>'Category',   'val'=> ucfirst(str_replace('_',' ',$job->category))],
                        ['icon'=>'academic-cap','label'=>'Level',   'val'=> ucfirst($job->experience_level)],
                        ['icon'=>'clock',    'label'=>'Duration',   'val'=> $job->duration ? ucfirst(str_replace('_',' ',$job->duration)) : '—'],
                        ['icon'=>'calendar', 'label'=>'Deadline',   'val'=> $job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('M d, Y') : '—'],
                        ['icon'=>'users',    'label'=>'Applicants', 'val'=> $this->appCounts['all'] . ' / ' . $job->max_applicants],
                    ] as $row)
                        <div class="flex items-center gap-2">
                            <flux:icon :name="$row['icon']" class="size-3.5 text-[#506070] flex-shrink-0"/>
                            <span class="text-[#506070] w-20">{{ $row['label'] }}</span>
                            <span class="text-[#dde6f0]">{{ $row['val'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Apply CTA (freelancer, not applied yet) --}}
                @if(! $isOwner && ! $hasApplied && $job->status === 'open')
                    <flux:button variant="primary" class="w-full" icon="paper-airplane"
                        href="{{ route('backend.jobBoard') }}" wire:navigate>
                        Apply to this job
                    </flux:button>
                @endif
            </flux:card>

            {{-- Client info --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45]">
                <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-3">Client</p>
                <div class="flex items-center gap-2.5">
                    <flux:avatar src="{{ $job->client->avatar_url }}" name="{{ $job->client->name }}" size="sm"/>
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $job->client->name }}</p>
                        <p class="text-xs text-[#506070]">Member since {{ $job->client->created_at->format('M Y') }}</p>
                    </div>
                </div>
            </flux:card>

            {{-- Owner actions --}}
            @if($isOwner)
                <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-2">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-1">Manage job</p>
                    @if($job->status === 'open')
                        <flux:button variant="ghost" size="sm" class="w-full justify-start"
                            wire:click="closeJob" icon="x-circle">
                            Close job
                        </flux:button>
                    @elseif($job->status === 'closed')
                        <flux:button variant="ghost" size="sm" class="w-full justify-start"
                            wire:click="reopenJob" icon="arrow-path">
                            Reopen job
                        </flux:button>
                    @endif
                    <flux:button variant="ghost" size="sm" class="w-full justify-start text-[#506070]"
                        href="{{ route('backend.myJobs') }}" wire:navigate icon="arrow-left">
                        All my jobs
                    </flux:button>
                </flux:card>
            @endif
        </div>
    </div>
</div>

{{-- Full application detail drawer --}}
@if($viewAppId && $this->viewApp)
    @php $app = $this->viewApp; @endphp
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             wire:click="$set('viewAppId', null)"></div>
        <div class="relative z-50 w-full max-w-lg bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
             style="animation:slideIn .25s ease both">

            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <flux:heading>Application</flux:heading>
                <flux:button variant="ghost" size="sm" icon="x-mark"
                    wire:click="$set('viewAppId', null)"/>
            </div>

            <div class="p-5 space-y-5">
                {{-- Freelancer profile snippet --}}
                <div class="flex items-center gap-3">
                    <flux:avatar
                        src="{{ $app->freelancer->avatar_url }}"
                        name="{{ $app->freelancer->name }}"
                        size="md"/>
                    <div>
                        <p class="font-['Syne'] font-bold text-white">{{ $app->freelancer->name }}</p>
                        @if($app->freelancer->serviceProfile?->headline)
                            <p class="text-xs text-[#8da0b8]">{{ $app->freelancer->serviceProfile->headline }}</p>
                        @endif
                        @if($app->freelancer->serviceProfile)
                            <div class="flex items-center gap-2 mt-1">
                                @if($app->freelancer->serviceProfile->avg_rating > 0)
                                    <span class="flex items-center gap-0.5 text-[11px] text-amber-400">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        {{ number_format($app->freelancer->serviceProfile->avg_rating, 1) }}
                                        ({{ $app->freelancer->serviceProfile->total_reviews }})
                                    </span>
                                @endif
                                @if($app->freelancer->serviceProfile->hourly_rate)
                                    <span class="text-[11px] text-[#7EE8A2] font-mono">
                                        ${{ number_format($app->freelancer->serviceProfile->hourly_rate) }}/hr
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Proposal --}}
                @if($app->proposed_rate)
                    <div class="flex items-center gap-3 p-3 bg-[#080c14] border border-[#1c2e45] rounded-xl">
                        <div>
                            <p class="text-[10px] text-[#506070] font-mono uppercase">Proposed</p>
                            <p class="font-['Syne'] text-xl font-bold text-[#7EE8A2]">
                                {{ $job->currency }} {{ number_format($app->proposed_rate) }}
                                {{ $job->type === 'hourly' ? '/hr' : '' }}
                            </p>
                        </div>
                        @if($app->availability)
                            <div class="ml-auto">
                                <p class="text-[10px] text-[#506070] font-mono uppercase">Start</p>
                                <p class="text-sm text-[#dde6f0]">{{ ucfirst(str_replace('_',' ',$app->availability)) }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Cover letter --}}
                <div class="space-y-1.5">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Cover letter</p>
                    <p class="text-sm text-[#8da0b8] bg-[#131d2e] rounded-xl p-4 leading-relaxed whitespace-pre-wrap border border-[#1c2e45]">
                        {{ $app->cover_letter }}
                    </p>
                </div>

                {{-- Actions --}}
                @if(in_array($app->status, ['pending', 'shortlisted']))
                    <div class="flex gap-2 pt-2 border-t border-[#1c2e45]">
                        @if($app->status === 'pending')
                            <flux:button variant="ghost" size="sm" icon="star"
                                wire:click="shortlist({{ $app->id }})"
                                class="flex-1 text-amber-400 border-amber-500/20">
                                Shortlist
                            </flux:button>
                            <flux:button variant="ghost" size="sm" icon="x-mark"
                                wire:click="reject({{ $app->id }})"
                                class="text-[#506070] hover:text-red-400">
                                Reject
                            </flux:button>
                        @endif
                        <flux:button variant="primary" size="sm" icon="check"
                            class="flex-1"
                            wire:click="hire({{ $app->id }})"
                            wire:confirm="Hire {{ $app->freelancer->name }} for this job?">
                            Hire
                        </flux:button>
                    </div>
                @endif

                {{-- View full profile --}}
                <a href="{{ route('backend.profilePage', $app->freelancer->name) }}"
                   wire:navigate
                   class="flex items-center justify-center gap-1.5 w-full py-2 rounded-xl border border-[#1c2e45] text-xs text-[#8da0b8] hover:text-[#dde6f0] hover:border-[#254060] transition-all">
                    <flux:icon.user class="size-3.5"/>
                    View full profile & portfolio
                </a>
            </div>
        </div>
    </div>
    <style>@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}</style>
@endif
