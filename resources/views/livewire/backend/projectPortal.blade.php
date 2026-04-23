<div class="min-h-screen">

    {{-- ── Hero / progress header ──────────────────────────── --}}
    <div class="bg-gradient-to-b from-[#0d1825] to-[#080c14] border-b border-[#1c2e45]">
        <div class="max-w-4xl mx-auto px-5 py-10">
            <div class="flex flex-col sm:flex-row sm:items-center gap-6">

                {{-- Project name + meta --}}
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="font-mono text-[10px] uppercase tracking-widest text-[#506070]">
                            Client Portal
                        </span>
                        <flux:badge size="sm"
                            :color="match($project->status) {
                                                            'active'    => 'green',
                                                            'planning'  => 'blue',
                                                            'completed' => 'lime',
                                                            'on_hold'   => 'yellow',
                                                            default     => 'zinc',
                                                        }">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}</flux:badge>
                    </div>

                    <h1
                        style="font-family:'Syne',sans-serif;font-weight:800;font-size:clamp(22px,4vw,32px);color:#fff;letter-spacing:-0.5px;line-height:1.1">
                        {{ $project->name }}
                    </h1>

                    @if ($project->description)
                        <p class="text-sm text-[#8da0b8] mt-2 max-w-lg leading-relaxed">
                            {{ $project->description }}
                        </p>
                    @endif

                    @if ($project->due_date)
                        <div class="flex items-center gap-1.5 mt-3 text-sm text-[#8da0b8]">
                            <flux:icon.calendar-days class="size-4" />
                            Due {{ $project->due_date->format('F j, Y') }}
                        </div>
                    @endif
                </div>

                {{-- SVG progress ring --}}
                <div class="flex flex-col items-center gap-2 flex-shrink-0">
                    <div class="relative w-28 h-28">
                        @php
                            $radius = 42;
                            $circumference = 2 * M_PI * $radius;
                            $offset = $circumference * (1 - $project->progress_percentage / 100);
                        @endphp
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="{{ $radius }}" fill="none"
                                stroke="#1c2e45" stroke-width="8" />
                            <circle cx="50" cy="50" r="{{ $radius }}" fill="none"
                                stroke="#7EE8A2" stroke-width="8" stroke-linecap="round"
                                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                                style="transition: stroke-dashoffset 1s ease" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span
                                style="font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:#fff;line-height:1">
                                {{ $project->progress_percentage }}%
                            </span>
                            <span class="text-[10px] text-[#506070] mt-0.5">Complete</span>
                        </div>
                    </div>
                    <p class="text-xs text-[#506070]">
                        Updated {{ $project->updated_at->diffForHumans() }}
                    </p>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Page body ────────────────────────────────────────── --}}
    <div class="max-w-4xl mx-auto px-5 py-8 space-y-8">

        {{-- ── Milestones ─────────────────────────────────────── --}}
        @if ($milestoneProgress->isNotEmpty())
            <section class="space-y-4">
                <div class="flex items-center gap-3">
                    <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:18px;color:#fff">
                        Milestones
                    </h2>
                    <div class="flex-1 h-px bg-[#1c2e45]"></div>
                    <span class="text-xs text-[#506070] font-mono">{{ $milestoneProgress->count() }} total</span>
                </div>

                @foreach ($milestoneProgress as $ms)
                    <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl overflow-hidden"
                        wire:key="ms-{{ $ms['id'] }}">

                        {{-- Milestone header --}}
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2.5 flex-wrap">

                                        {{-- Completion check circle --}}
                                        <div
                                            class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 border-2
                                            {{ $ms['completed_at'] ? 'bg-[#7EE8A2] border-[#7EE8A2]' : 'border-[#254060]' }}">
                                            @if ($ms['completed_at'])
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                                    stroke="#080c14" stroke-width="3.5" stroke-linecap="round">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                            @endif
                                        </div>

                                        <h3
                                            style="font-family:'Syne',sans-serif;font-weight:600;font-size:15px;color:#fff">
                                            {{ $ms['name'] }}
                                        </h3>

                                        {{-- Status badge --}}
                                        @if ($ms['completed_at'])
                                            <flux:badge size="sm" color="lime">Completed</flux:badge>
                                        @elseif($ms['due_date'] && \Carbon\Carbon::parse($ms['due_date'])->isPast())
                                            <flux:badge size="sm" color="red">Overdue</flux:badge>
                                        @elseif($ms['pct'] > 0)
                                            <flux:badge size="sm" color="blue">In Progress</flux:badge>
                                        @else
                                            <flux:badge size="sm" color="zinc">Upcoming</flux:badge>
                                        @endif

                                    </div>

                                    @if ($ms['due_date'])
                                        <p class="text-xs text-[#506070] mt-1.5 flex items-center gap-1">
                                            <flux:icon.calendar class="size-3" />
                                            Due {{ \Carbon\Carbon::parse($ms['due_date'])->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>

                                {{-- Task count --}}
                                @if ($ms['task_total'] > 0)
                                    <div class="text-right flex-shrink-0">
                                        <p
                                            style="font-family:'Syne',sans-serif;font-size:18px;font-weight:700;color:#fff;line-height:1">
                                            {{ $ms['pct'] }}%
                                        </p>
                                        <p class="text-[11px] text-[#506070] mt-0.5">
                                            {{ $ms['task_done'] }}/{{ $ms['task_total'] }} tasks
                                        </p>
                                    </div>
                                @endif
                            </div>

                            {{-- Progress bar --}}
                            @if ($ms['task_total'] > 0)
                                <div class="mt-3 h-1.5 bg-[#1c2e45] rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700
                                            {{ $ms['pct'] >= 100 ? 'bg-[#7EE8A2]' : 'bg-blue-500' }}"
                                        style="width: {{ $ms['pct'] }}%"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Deliverables for this milestone --}}
                        @if ($ms['deliverables']->isNotEmpty())
                            <div class="border-t border-[#1c2e45] bg-[#080c14]/60">
                                <div class="px-5 py-2.5">
                                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">
                                        Deliverables ({{ $ms['deliverables']->count() }})
                                    </p>
                                </div>
                                <div class="divide-y divide-[#1c2e45]">
                                    @foreach ($ms['deliverables'] as $task)
                                        <div class="flex items-center gap-3 px-5 py-3"
                                            wire:key="deliv-{{ $task['id'] }}">

                                            {{-- Type icon --}}
                                            <div
                                                class="w-7 h-7 flex-shrink-0 flex items-center justify-center rounded-lg bg-[#7EE8A2]/10 border border-[#7EE8A2]/15">
                                                @switch($task['deliverable_type'] ?? '')
                                                    @case('figma')
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#7EE8A2">
                                                            <path
                                                                d="M8 24c2.208 0 4-1.792 4-4v-4H8c-2.208 0-4 1.792-4 4s1.792 4 4 4z" />
                                                            <path d="M4 12c0-2.208 1.792-4 4-4h4v8H8c-2.208 0-4-1.792-4-4z" />
                                                            <path d="M4 4c0-2.208 1.792-4 4-4h4v8H8C5.792 8 4 6.208 4 4z" />
                                                            <path d="M12 0h4c2.208 0 4 1.792 4 4s-1.792 4-4 4h-4V0z" />
                                                            <path
                                                                d="M20 12c0 2.208-1.792 4-4 4s-4-1.792-4-4 1.792-4 4-4 4 1.792 4 4z" />
                                                        </svg>
                                                    @break

                                                    @case('github_pr')
                                                        <flux:icon.code-bracket class="size-3.5 text-[#7EE8A2]" />
                                                    @break

                                                    @case('loom')
                                                        <flux:icon.video-camera class="size-3.5 text-[#7EE8A2]" />
                                                    @break

                                                    @default
                                                        <flux:icon.link class="size-3.5 text-[#7EE8A2]" />
                                                @endswitch
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-[#dde6f0] truncate">{{ $task['title'] }}</p>
                                                @if (!empty($task['deliverable_note']))
                                                    <p class="text-xs text-[#506070] truncate">
                                                        {{ $task['deliverable_note'] }}</p>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-3 flex-shrink-0">
                                                @if (!empty($task['completed_at']))
                                                    <span class="text-[11px] text-[#506070]">
                                                        {{ \Carbon\Carbon::parse($task['completed_at'])->format('M d') }}
                                                    </span>
                                                @endif
                                                <a href="{{ $task['deliverable_url'] }}" target="_blank"
                                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors"
                                                    style="background:rgba(126,232,162,.1);color:#7EE8A2;border:1px solid rgba(126,232,162,.2)"
                                                    onmouseover="this.style.background='rgba(126,232,162,.2)'"
                                                    onmouseout="this.style.background='rgba(126,232,162,.1)'">
                                                    View
                                                    <flux:icon.arrow-top-right-on-square class="size-3" />
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Per-milestone feedback button --}}
                        <div class="px-5 py-3 border-t border-[#1c2e45] flex justify-end">
                            <button
                                wire:click="$set('feedbackMilestone', {{ $ms['id'] }}); $set('showFeedbackForm', true)"
                                class="flex items-center gap-1.5 text-xs text-[#506070] hover:text-[#dde6f0] transition-colors">
                                <flux:icon.chat-bubble-left-ellipsis class="size-3.5" />
                                Leave feedback on this milestone
                            </button>
                        </div>

                    </div>
                @endforeach
            </section>
        @endif

        {{-- ── Ungrouped deliverables ─────────────────────────── --}}
        @php $ungrouped = $doneTasks->whereNull('milestone_id'); @endphp
        @if ($ungrouped->isNotEmpty())
            <section class="space-y-3">
                <div class="flex items-center gap-3">
                    <h2 style="font-family:'Syne',sans-serif;font-weight:700;font-size:18px;color:#fff">
                        Deliverables
                    </h2>
                    <div class="flex-1 h-px bg-[#1c2e45]"></div>
                </div>
                <div class="bg-[#0e1420] border border-[#1c2e45] rounded-xl divide-y divide-[#1c2e45] overflow-hidden">
                    @foreach ($ungrouped as $task)
                        <div class="flex items-center gap-3 px-5 py-3" wire:key="ug-{{ $task->id }}">
                            <div
                                class="w-6 h-6 rounded-lg flex-shrink-0 flex items-center justify-center bg-[#7EE8A2]/10 border border-[#7EE8A2]/15">
                                <flux:icon.check class="size-3.5 text-[#7EE8A2]" />
                            </div>
                            <p class="flex-1 text-sm text-[#dde6f0] truncate">{{ $task->title }}</p>
                            <a href="{{ $task->deliverable_url }}" target="_blank"
                                class="flex items-center gap-1 text-xs text-[#7EE8A2] hover:underline">
                                View <flux:icon.arrow-top-right-on-square class="size-3" />
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ── Feedback sent confirmation ───────────────────────── --}}
        @if ($feedbackSent)
            <div class="flex items-center gap-3 p-4 rounded-xl border"
                style="background:rgba(126,232,162,.06);border-color:rgba(126,232,162,.2)">
                <flux:icon.check-circle class="size-5 text-[#7EE8A2] flex-shrink-0" />
                <p class="text-sm text-[#7EE8A2]">
                    Your feedback has been sent to the project team. Thank you!
                </p>
            </div>
        @endif

        {{-- ── General feedback CTA ─────────────────────────────── --}}
        @if (!$showFeedbackForm && !$feedbackSent)
            <div class="flex justify-center pt-2 pb-8">
                <button wire:click="$set('showFeedbackForm', true)"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium transition-all"
                    style="border:1px solid #1c2e45;color:#8da0b8;background:transparent"
                    onmouseover="this.style.borderColor='#254060';this.style.color='#dde6f0'"
                    onmouseout="this.style.borderColor='#1c2e45';this.style.color='#8da0b8'">
                    <flux:icon.chat-bubble-left-right class="size-4" />
                    Send feedback to the team
                </button>
            </div>
        @endif

    </div>

    {{-- ── Feedback modal ───────────────────────────────────────── --}}
    <flux:modal wire:model="showFeedbackForm" class="max-w-lg">
        <div class="space-y-5">

            <div>
                <flux:heading>Leave feedback</flux:heading>
                <flux:text class="mt-1">Your feedback goes directly to the project team.</flux:text>
            </div>

            <form wire:submit="submitFeedback" class="space-y-4">

                {{-- Feedback type selector --}}
                <div class="grid grid-cols-3 gap-2">
                    @foreach ([['value' => 'comment', 'icon' => 'chat-bubble-left', 'label' => 'Comment'], ['value' => 'approval', 'icon' => 'hand-thumb-up', 'label' => 'Approve'], ['value' => 'revision_request', 'icon' => 'arrow-uturn-left', 'label' => 'Revision']] as $opt)
                        <label
                            class="flex flex-col items-center gap-1.5 p-3 rounded-xl border cursor-pointer transition-all text-center"
                            style="{{ $feedbackType === $opt['value']
                                ? 'border-color:#7EE8A2;background:rgba(126,232,162,.05);color:#7EE8A2'
                                : 'border-color:#1c2e45;color:#506070' }}">
                            <input type="radio" wire:model.live="feedbackType" value="{{ $opt['value'] }}"
                                class="sr-only">
                            <flux:icon :name="$opt['icon']" class="size-5" />
                            <span class="text-xs font-medium">{{ $opt['label'] }}</span>
                        </label>
                    @endforeach
                </div>
                @error('feedbackType')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror

                {{-- Message --}}
                <flux:field>
                    <flux:label>Message <span class="text-red-400">*</span></flux:label>
                    <flux:textarea wire:model="feedbackBody" placeholder="Describe your feedback…" rows="4" />
                    <flux:error name="feedbackBody" />
                </flux:field>

                {{-- Name + email --}}
                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Your name <span class="text-[#506070] font-normal text-xs">(optional)</span>
                        </flux:label>
                        <flux:input wire:model="feedbackName"
                            placeholder="{{ $project->client_name ?: 'Your name' }}" icon="user" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Your email <span class="text-[#506070] font-normal text-xs">(optional)</span>
                        </flux:label>
                        <flux:input wire:model="feedbackEmail" type="email"
                            placeholder="{{ $project->client_email ?: 'Your email' }}" icon="envelope" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="$set('feedbackMilestone', null)">
                            Cancel
                        </flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove>Send feedback</span>
                        <span wire:loading>Sending…</span>
                    </flux:button>
                </div>

            </form>
        </div>
    </flux:modal>

</div>



{{-- Real-time Echo subscription --}}
<script>
    document.addEventListener('livewire:init', () => {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private('client.{{ $token }}')
                .listen('.progress.updated', (data) => {
                    Livewire.dispatch(
                        'echo-private:client.{{ $token }},.progress.updated',
                        data
                    );
                });
        }
    });
</script>
