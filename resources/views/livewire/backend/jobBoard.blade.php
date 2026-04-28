<div class="max-w-6xl space-y-5 px-4 sm:px-0">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">Find Jobs</flux:heading>
            <flux:text class="mt-0.5">Browse open jobs posted by clients. Apply directly.</flux:text>
        </div>
        <flux:button variant="primary" size="sm" icon="plus" href="{{ route('backend.jobPostCreate') }}"
            wire:navigate>
            Post a job
        </flux:button>
    </div>

    {{-- Filters ─────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <div class="flex-1 min-w-[200px] sm:min-w-48">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title, skill, or keyword…"
                icon="magnifying-glass" clearable />
        </div>

        <flux:select wire:model.live="category" class="w-full sm:w-44">
            <flux:select.option value="all">All categories</flux:select.option>
            @foreach (['software_dev' => 'Software Dev', 'ui_ux' => 'UI/UX', 'digital_marketing' => 'Digital Marketing', 'data_analytics' => 'Data Analytics', 'content_writing' => 'Content Writing', 'video_editing' => 'Video Editing', 'virtual_assistant' => 'Virtual Assistant', 'cybersecurity' => 'Cybersecurity', 'ai_ml' => 'AI / ML', 'project_management' => 'Project Management', 'other' => 'Other'] as $v => $l)
                <flux:select.option value="{{ $v }}">{{ $l }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="type" class="w-full sm:w-32">
            <flux:select.option value="all">Any type</flux:select.option>
            <flux:select.option value="fixed">Fixed price</flux:select.option>
            <flux:select.option value="hourly">Hourly</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="experience" class="w-full sm:w-32">
            <flux:select.option value="all">Any level</flux:select.option>
            <flux:select.option value="entry">Entry</flux:select.option>
            <flux:select.option value="mid">Mid</flux:select.option>
            <flux:select.option value="senior">Senior</flux:select.option>
            <flux:select.option value="expert">Expert</flux:select.option>
        </flux:select>

        <flux:select wire:model.live="sortBy" class="w-full sm:w-36">
            <flux:select.option value="newest">Newest first</flux:select.option>
            <flux:select.option value="budget_high">Highest budget</flux:select.option>
            <flux:select.option value="budget_low">Lowest budget</flux:select.option>
        </flux:select>

        @if ($search || $category !== 'all' || $type !== 'all' || $experience !== 'all')
            <flux:button variant="ghost" size="sm" wire:click="clearFilters">Clear</flux:button>
        @endif
    </div>

    {{-- Job list ─────────────────────────────────────────────── --}}
    @if ($this->jobs->isEmpty())
        <div
            class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
            <flux:icon.briefcase class="size-8 text-[#506070]" />
            <flux:heading>No jobs found</flux:heading>
            <flux:text class="text-sm">Try adjusting your filters or check back later.</flux:text>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($this->jobs as $job)
                @php $hasApplied = in_array($job->id, $this->appliedJobIds); @endphp
                <div class="group bg-[#0e1420] border rounded-2xl p-4 sm:p-5 transition-all
                    {{ $hasApplied ? 'border-[#7EE8A2]/20' : 'border-[#1c2e45] hover:border-[#254060]' }}"
                    wire:key="job-{{ $job->id }}">

                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        {{-- Client avatar --}}
                        <flux:avatar src="{{ $job->client->avatar_url }}" name="{{ $job->client->name }}" size="sm"
                            class="flex-shrink-0 mt-0.5 hidden sm:block" />
                        
                        {{-- Mobile avatar --}}
                        <flux:avatar src="{{ $job->client->avatar_url }}" name="{{ $job->client->name }}" size="xs"
                            class="flex-shrink-0 sm:hidden" />

                        <div class="flex-1 min-w-0">
                            {{-- Title row --}}
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                <div class="flex-1">
                                    <a href="{{ route('backend.jobPostDetail', $job->id) }}" wire:navigate
                                        class="font-['Syne'] font-bold text-base text-white hover:text-[#7EE8A2] transition-colors block">
                                        {{ $job->title }}
                                    </a>
                                    <p class="text-xs text-[#506070] mt-0.5">
                                        Posted by {{ $job->client->name }}
                                        · {{ $job->created_at->diffForHumans() }}
                                    </p>
                                </div>

                                {{-- Budget --}}
                                <div class="text-left sm:text-right flex-shrink-0">
                                    @if ($job->budget_min || $job->budget_max)
                                        <p class="font-['Syne'] font-bold text-[#7EE8A2]">
                                            {{ $job->currency }}
                                            {{ $job->budget_min ? number_format($job->budget_min) : '' }}
                                            {{ $job->budget_min && $job->budget_max ? '–' : '' }}
                                            {{ $job->budget_max ? number_format($job->budget_max) : '' }}
                                        </p>
                                        <p class="text-[10px] text-[#506070]">
                                            {{ $job->type === 'hourly' ? 'per hour' : 'fixed price' }}
                                        </p>
                                    @else
                                        <p class="text-sm text-[#506070]">Budget TBD</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Description preview --}}
                            <p class="text-sm text-[#8da0b8] mt-2 line-clamp-2 leading-relaxed">
                                {{ $job->description }}
                            </p>

                            {{-- Meta badges --}}
                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                <flux:badge size="sm" color="blue">
                                    {{ ucfirst(str_replace('_', ' ', $job->category)) }}
                                </flux:badge>
                                <flux:badge size="sm"
                                    :color="match($job->experience_level) {
                                                                            'entry'  => 'zinc',
                                                                            'mid'    => 'blue',
                                                                            'senior' => 'purple',
                                                                            'expert' => 'yellow',
                                                                            default  => 'zinc',
                                                                        }">
                                    {{ ucfirst($job->experience_level) }}</flux:badge>

                                @if ($job->duration)
                                    <span class="flex items-center gap-1 text-xs text-[#506070]">
                                        <flux:icon.clock class="size-3.5" />
                                        <span class="hidden sm:inline">{{ ucfirst(str_replace('_', ' ', $job->duration)) }}</span>
                                        <span class="sm:hidden">{{ ucfirst(substr(str_replace('_', ' ', $job->duration), 0, 10)) }}{{ strlen(str_replace('_', ' ', $job->duration)) > 10 ? '...' : '' }}</span>
                                    </span>
                                @endif
                                @if ($job->deadline)
                                    <span class="flex items-center gap-1 text-xs text-[#506070]">
                                        <flux:icon.calendar class="size-3.5" />
                                        <span class="hidden sm:inline">Deadline {{ \Carbon\Carbon::parse($job->deadline)->format('M d') }}</span>
                                        <span class="sm:hidden">{{ \Carbon\Carbon::parse($job->deadline)->format('M d') }}</span>
                                    </span>
                                @endif
                                <span class="flex items-center gap-1 text-xs text-[#506070]">
                                    <flux:icon.users class="size-3.5" />
                                    {{ $job->applications_count }}/{{ $job->max_applicants }} applied
                                </span>
                            </div>

                            {{-- Skills --}}
                            @if (!empty($job->skills_required))
                                <div class="flex flex-wrap gap-1 mt-2">
                                    @foreach (array_slice((array) $job->skills_required, 0, 3) as $skill)
                                        <span
                                            class="text-[10px] px-2 py-0.5 rounded-md bg-[#131d2e] border border-[#1c2e45] text-[#8da0b8]">
                                            {{ $skill }}
                                        </span>
                                    @endforeach
                                    @if (count((array) $job->skills_required) > 3)
                                        <span class="text-[10px] text-[#506070]">
                                            +{{ count((array) $job->skills_required) - 3 }} more
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action row --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 mt-4 pt-4 border-t border-[#1c2e45]">
                        <a href="{{ route('backend.jobPostDetail', $job->id) }}" wire:navigate
                            class="text-xs text-[#506070] hover:text-[#dde6f0] transition-colors flex items-center gap-1 justify-center sm:justify-start">
                            View full details
                            <flux:icon.arrow-right class="size-3" />
                        </a>

                        <div class="flex-shrink-0">
                            @if ($hasApplied)
                                <flux:badge size="sm" color="green" icon="check-circle" class="w-full justify-center">Applied</flux:badge>
                            @elseif($job->applications_count >= $job->max_applicants)
                                <flux:badge size="sm" color="red" class="w-full justify-center">Closed</flux:badge>
                            @else
                                <flux:button variant="primary" size="sm" wire:click="openApply({{ $job->id }})" class="w-full">
                                    Apply now
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5 overflow-x-auto">
            {{ $this->jobs->links() }}
        </div>
    @endif

    {{-- ── Apply modal ────────────────────────────────────────────── --}}
<flux:modal wire:model.live="showApplyModal" class="max-w-2xl mx-4 sm:mx-auto">
        @if ($this->openJob)
            @php $job = $this->openJob; @endphp
            <div class="space-y-5 p-4 sm:p-0">

                @if ($applied)
                    {{-- Success state --}}
                    <div class="flex flex-col items-center gap-4 py-6 text-center">
                        <div
                            class="w-14 h-14 rounded-2xl bg-[#7EE8A2]/10 border border-[#7EE8A2]/20 flex items-center justify-center">
                            <flux:icon.check-circle class="size-7 text-[#7EE8A2]" />
                        </div>
                        <div>
                            <h3 class="font-['Syne'] text-lg font-bold text-white">Application sent!</h3>
                            <p class="text-sm text-[#8da0b8] mt-1">
                                The client will review your application and get back to you.
                            </p>
                        </div>
                        <flux:modal.close>
                            <flux:button variant="primary" size="sm">Done</flux:button>
                        </flux:modal.close>
                    </div>
                @else
                    {{-- Job summary --}}
                    <div class="flex items-start gap-3 p-3 bg-[#080c14] border border-[#1c2e45] rounded-xl">
                        <flux:avatar src="{{ $job->client->avatar_url }}" name="{{ $job->client->name }}"
                            size="sm" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white font-['Syne'] truncate">{{ $job->title }}</p>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 mt-0.5">
                                <span class="text-xs text-[#506070]">{{ $job->client->name }}</span>
                                @if ($job->budget_max)
                                    <span class="text-xs text-[#7EE8A2] font-mono">
                                        {{ $job->currency }}
                                        {{ number_format($job->budget_min ?? 0) }}–{{ number_format($job->budget_max) }}
                                        {{ $job->type === 'hourly' ? '/hr' : '' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <flux:heading size="lg">Your application</flux:heading>

                    <form wire:submit="submitApplication" class="space-y-4">

                        <flux:field>
                            <flux:label>Cover letter<span class="text-red-400">*</span></flux:label>
                            <flux:textarea wire:model="coverLetter" rows="6"
                                placeholder="Introduce yourself and explain why you're the right fit for this job.

• Relevant experience and similar projects
• Your approach to this specific project
• Timeline you can commit to
• Any questions for the client" />
                            <flux:description>Minimum 50 characters. Make it specific to this job.</flux:description>
                            <flux:error name="coverLetter" />
                        </flux:field>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>
                                    Your {{ $job->type === 'hourly' ? 'hourly rate' : 'proposed price' }}
                                    <span class="text-[#506070] font-normal text-xs">(optional)</span>
                                </flux:label>
                                <flux:input wire:model="proposedRate" type="number" min="1"
                                    placeholder="{{ $job->type === 'hourly' ? '$/hr' : 'Total $' }}"
                                    icon="currency-dollar" />
                                <flux:error name="proposedRate" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Availability<span class="text-red-400">*</span></flux:label>
                                <flux:select wire:model="availability">
                                    <flux:select.option value="">When can you start?</flux:select.option>
                                    <flux:select.option value="immediately">Immediately</flux:select.option>
                                    <flux:select.option value="within_3_days">Within 3 days</flux:select.option>
                                    <flux:select.option value="within_1_week">Within 1 week</flux:select.option>
                                    <flux:select.option value="within_2_weeks">Within 2 weeks</flux:select.option>
                                    <flux:select.option value="within_1_month">Within 1 month</flux:select.option>
                                </flux:select>
                                <flux:error name="availability" />
                            </flux:field>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-1 border-t border-[#1c2e45]">
                            <flux:modal.close>
                                <flux:button variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" icon="paper-airplane"
                                wire:loading.attr="disabled" class="w-full sm:w-auto">
                                <span wire:loading.remove>Submit application</span>
                                <span wire:loading>Submitting…</span>
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </flux:modal>

</div>