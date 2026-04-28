<div class="max-w-4xl space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">
            {{ $mode === 'client' ? 'My Job Posts' : 'My Applications' }}
        </flux:heading>
        <div class="flex gap-2">
            {{-- Mode toggle --}}
            <flux:button.group>
                <flux:button
                    size="sm"
                    :variant="$mode === 'client' ? 'filled' : 'ghost'"
                    wire:click="$set('mode','client'); $set('filter','all')"
                    icon="briefcase">
                    My jobs
                </flux:button>
                <flux:button
                    size="sm"
                    :variant="$mode === 'freelancer' ? 'filled' : 'ghost'"
                    wire:click="$set('mode','freelancer'); $set('filter','all')"
                    icon="paper-airplane">
                    My applications
                </flux:button>
            </flux:button.group>
            @if($mode === 'client')
                <flux:button variant="primary" size="sm" icon="plus"
                    href="{{ route('backend.jobPostCreate') }}" wire:navigate>
                    Post a job
                </flux:button>
            @endif
        </div>
    </div>

    {{-- ── CLIENT MODE ──────────────────────────────────────── --}}
    @if($mode === 'client')
        {{-- Status filter tabs --}}
        <div class="flex gap-1 border-b border-[#1c2e45]">
            @foreach(['all'=>'All','open'=>'Open','draft'=>'Draft','filled'=>'Filled','closed'=>'Closed'] as $key => $label)
                <button wire:click="$set('filter','{{ $key }}')"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px transition-all"
                    style="{{ $filter === $key ? 'border-color:#7EE8A2;color:#7EE8A2' : 'border-color:transparent;color:#8da0b8' }}">
                    {{ $label }}
                    @if($this->jobCounts[$key] > 0)
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-[#1c2e45] text-[#506070]">
                            {{ $this->jobCounts[$key] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        @if($this->myJobs->isEmpty())
            <div class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                <flux:icon.briefcase class="size-8 text-[#506070]"/>
                <flux:heading>No job posts yet</flux:heading>
                <flux:text class="text-sm">Post a job to start finding freelancers for your projects.</flux:text>
                <flux:button variant="primary" size="sm" icon="plus"
                    href="{{ route('backend.jobPostCreate') }}" wire:navigate>
                    Post your first job
                </flux:button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->myJobs as $job)
                    <div class="flex items-start gap-4 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4 hover:border-[#254060] transition-all"
                         wire:key="job-{{ $job->id }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('backend.jobPostDetail', $job->id) }}" wire:navigate
                                   class="font-['Syne'] font-bold text-white hover:text-[#7EE8A2] transition-colors">
                                    {{ $job->title }}
                                </a>
                                <flux:badge size="sm"
                                    :color="match($job->status) {
                                        'open'   => 'green',
                                        'draft'  => 'yellow',
                                        'filled' => 'lime',
                                        'closed' => 'zinc',
                                        default  => 'zinc',
                                    }"
                                >{{ ucfirst($job->status) }}</flux:badge>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-[#506070]">
                                @if($job->budget_max)
                                    <span class="text-[#7EE8A2] font-mono">
                                        {{ $job->currency }} {{ number_format($job->budget_min ?? 0) }}–{{ number_format($job->budget_max) }}
                                    </span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <flux:icon.users class="size-3"/>
                                    {{ $job->applications_count }}/{{ $job->max_applicants }} applicants
                                </span>
                                @if($job->applications_count > 0)
                                    <span class="text-amber-400">{{ $job->applications_count }} new</span>
                                @endif
                                <span>{{ $job->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="flex gap-1 flex-shrink-0">
                            <flux:button variant="primary" size="sm"
                                href="{{ route('backend.jobPostDetail', $job->id) }}" wire:navigate>
                                Manage
                            </flux:button>
                            @if($job->status === 'draft')
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="delete({{ $job->id }})"
                                    wire:confirm="Delete this draft?"
                                    class="text-[#506070] hover:text-red-400"/>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ── FREELANCER MODE ─────────────────────────────────── --}}
    @if($mode === 'freelancer')
        {{-- Status filter tabs --}}
        <div class="flex gap-1 border-b border-[#1c2e45]">
            @foreach([
                'all'         => 'All',
                'pending'     => 'Pending',
                'shortlisted' => '⭐ Shortlisted',
                'hired'       => '✅ Hired',
                'rejected'    => 'Rejected',
            ] as $key => $label)
                <button wire:click="$set('filter','{{ $key }}')"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px transition-all"
                    style="{{ $filter === $key ? 'border-color:#7EE8A2;color:#7EE8A2' : 'border-color:transparent;color:#8da0b8' }}">
                    {{ $label }}
                    @if($this->appCounts[$key] > 0)
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded
                            {{ $key === 'shortlisted' ? 'bg-amber-500/15 text-amber-400' : ($key === 'hired' ? 'bg-[#7EE8A2]/15 text-[#7EE8A2]' : 'bg-[#1c2e45] text-[#506070]') }}">
                            {{ $this->appCounts[$key] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        @if($this->myApplications->isEmpty())
            <div class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                <flux:icon.paper-airplane class="size-8 text-[#506070]"/>
                <flux:heading>No applications yet</flux:heading>
                <flux:text class="text-sm">Browse open jobs and apply to start getting work.</flux:text>
                <flux:button variant="primary" size="sm" icon="magnifying-glass"
                    href="{{ route('backend.jobBoard') }}" wire:navigate>
                    Find jobs
                </flux:button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->myApplications as $app)
                    <div class="flex items-start gap-4 bg-[#0e1420] border rounded-xl p-4 transition-all
                        {{ $app->status === 'hired'
                            ? 'border-[#7EE8A2]/30 bg-[#7EE8A2]/02'
                            : ($app->status === 'shortlisted' ? 'border-amber-500/30' : 'border-[#1c2e45] hover:border-[#254060]') }}"
                         wire:key="app-{{ $app->id }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <a href="{{ route('backend.jobPostDetail', $app->job_post_id) }}" wire:navigate
                                   class="font-['Syne'] font-semibold text-white hover:text-[#7EE8A2] transition-colors">
                                    {{ $app->jobPost->title }}
                                </a>
                                <flux:badge size="sm"
                                    :color="match($app->status) {
                                        'shortlisted' => 'yellow',
                                        'hired'       => 'green',
                                        'rejected'    => 'red',
                                        default       => 'zinc',
                                    }"
                                >{{ ucfirst($app->status) }}</flux:badge>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-[#506070]">
                                <span>{{ $app->jobPost->client->name }}</span>
                                @if($app->proposed_rate)
                                    <span class="text-[#7EE8A2] font-mono">
                                        {{ $app->jobPost->currency }} {{ number_format($app->proposed_rate) }}
                                        {{ $app->jobPost->type === 'hourly' ? '/hr' : '' }}
                                    </span>
                                @endif
                                <span>Applied {{ $app->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Hired call to action --}}
                            @if($app->status === 'hired')
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-[#7EE8A2]">🎉 You got the job! Time to create a contract.</span>
                                    <flux:button variant="primary" size="sm"
                                        href="{{ route('backend.contracts') }}" wire:navigate>
                                        Create contract
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                        @if($app->status === 'pending')
                            <flux:button variant="ghost" size="sm"
                                wire:click="withdrawApplication({{ $app->id }})"
                                wire:confirm="Withdraw this application?"
                                class="text-[#506070] hover:text-red-400 flex-shrink-0"
                                icon="x-mark" title="Withdraw"/>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif

</div>
