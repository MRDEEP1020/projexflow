<div class="space-y-5 text-sm" x-data>

    {{-- ── Status bar ──────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center gap-2">
        @foreach([
            'todo'        => ['label' => 'To Do',      'color' => 'zinc'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'blue'],
            'in_review'   => ['label' => 'In Review',   'color' => 'yellow'],
            'done'        => ['label' => 'Done',        'color' => 'green'],
            'blocked'     => ['label' => 'Blocked',     'color' => 'red'],
        ] as $s => $cfg)
            <button
                wire:click="updateStatus('{{ $s }}')"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border transition-all
                    {{ $task->status === $s
                        ? 'bg-[#7EE8A2]/10 border-[#7EE8A2]/40 text-[#7EE8A2]'
                        : 'border-[#1c2e45] text-[#506070] hover:border-[#254060] hover:text-[#8da0b8]' }}"
            >
                <div class="w-1.5 h-1.5 rounded-full
                    {{ match($s) {
                        'in_progress' => 'bg-blue-400',
                        'in_review'   => 'bg-amber-400',
                        'done'        => 'bg-[#7EE8A2]',
                        'blocked'     => 'bg-red-400',
                        default       => 'bg-[#506070]'
                    } }}">
                </div>
                {{ $cfg['label'] }}
            </button>
        @endforeach
    </div>

    {{-- ── Title ───────────────────────────────────────────── --}}
    @if($editingTitle)
        <div class="space-y-2">
            <flux:input wire:model="editTitle" autofocus/>
            <div class="flex gap-2">
                <flux:button size="sm" variant="primary" wire:click="saveTitle">Save</flux:button>
                <flux:button size="sm" variant="ghost" wire:click="$set('editingTitle',false)">Cancel</flux:button>
            </div>
        </div>
    @else
        <h2
            class="font-['Syne'] text-lg font-semibold text-white leading-snug cursor-pointer hover:text-[#7EE8A2] transition-colors"
            wire:click="$set('editingTitle',true)"
            title="Click to edit"
        >{{ $task->title }}</h2>
    @endif

    {{-- ── Meta grid ───────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3">

        {{-- Priority --}}
        <div class="bg-[#080c14] border border-[#1c2e45] rounded-xl p-3 space-y-1.5">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Priority</p>
            <flux:select wire:model.live="editPriority" wire:change="updatePriority($event.target.value)" size="sm">
                <flux:select.option value="low">Low</flux:select.option>
                <flux:select.option value="medium">Medium</flux:select.option>
                <flux:select.option value="high">High</flux:select.option>
                <flux:select.option value="critical">Critical</flux:select.option>
            </flux:select>
        </div>

        {{-- Due date --}}
        <div class="bg-[#080c14] border border-[#1c2e45] rounded-xl p-3 space-y-1.5">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Due date</p>
            <flux:input type="date" wire:model.live="editDueDate" wire:change="updateDueDate" size="sm"
                class="{{ $task->isOverdue() ? 'text-red-400' : '' }}"/>
        </div>

        {{-- Assignee --}}
        <div class="bg-[#080c14] border border-[#1c2e45] rounded-xl p-3 space-y-1.5">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Assignee</p>
            <flux:select wire:model.live="editAssignee" wire:change="updateAssignee" size="sm">
                <flux:select.option value="">Unassigned</flux:select.option>
                @foreach($this->teamMembers as $m)
                    <flux:select.option value="{{ $m->user_id }}">{{ $m->user->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Milestone --}}
        <div class="bg-[#080c14] border border-[#1c2e45] rounded-xl p-3 space-y-1.5">
            <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Milestone</p>
            <flux:select wire:model.live="editMilestone" wire:change="updateMilestone" size="sm">
                <flux:select.option value="">None</flux:select.option>
                @foreach($this->milestones as $ms)
                    <flux:select.option value="{{ $ms->id }}">{{ $ms->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

    </div>

    {{-- ── Description ─────────────────────────────────────── --}}
    <div class="border border-[#1c2e45] rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-[#1c2e45] bg-[#080c14]">
            <span class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Description</span>
            @if(!$editingDesc)
                <flux:button variant="ghost" size="sm" icon="pencil"
                    wire:click="$set('editingDesc',true)" class="!p-1"/>
            @endif
        </div>
        <div class="p-3">
            @if($editingDesc)
                <flux:textarea wire:model="editDescription" rows="4" autofocus/>
                <div class="flex gap-2 mt-2">
                    <flux:button size="sm" variant="primary" wire:click="saveDescription">Save</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="$set('editingDesc',false)">Cancel</flux:button>
                </div>
            @else
                @if($task->description)
                    <p class="text-sm text-[#8da0b8] leading-relaxed whitespace-pre-wrap cursor-pointer hover:text-[#dde6f0]"
                       wire:click="$set('editingDesc',true)">
                        {{ $task->description }}
                    </p>
                @else
                    <p class="text-sm text-[#506070] cursor-pointer hover:text-[#8da0b8] italic"
                       wire:click="$set('editingDesc',true)">
                        Add a description…
                    </p>
                @endif
            @endif
        </div>
    </div>

    {{-- ── Deliverable ──────────────────────────────────────── --}}
    <div class="border border-[#1c2e45] rounded-xl overflow-hidden">
        <div class="flex items-center justify-between px-3 py-2.5 bg-[#080c14] border-b border-[#1c2e45]">
            <span class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Deliverable</span>
            <flux:button variant="ghost" size="sm" icon="{{ $showDelivForm ? 'x-mark' : 'plus' }}"
                wire:click="$toggle('showDelivForm')" class="!p-1"/>
        </div>

        @if($task->deliverable_url)
            <div class="p-3 space-y-2">
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" color="lime">{{ ucfirst(str_replace('_',' ',$task->deliverable_type)) }}</flux:badge>
                    <a href="{{ $task->deliverable_url }}" target="_blank"
                       class="flex items-center gap-1 text-xs text-[#7EE8A2] hover:underline truncate">
                        <flux:icon.arrow-top-right-on-square class="size-3 flex-shrink-0"/>
                        {{ $task->deliverable_url }}
                    </a>
                </div>
                @if($task->deliverable_note)
                    <p class="text-xs text-[#8da0b8]">{{ $task->deliverable_note }}</p>
                @endif
            </div>
        @elseif(!$showDelivForm)
            <p class="p-3 text-xs text-[#506070]">No deliverable submitted yet.</p>
        @endif

        @if($showDelivForm)
            <div class="p-3 space-y-3 border-t border-[#1c2e45]">
                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model="delivType" size="sm">
                        <flux:select.option value="url">URL / Link</flux:select.option>
                        <flux:select.option value="figma">Figma</flux:select.option>
                        <flux:select.option value="github_pr">GitHub PR</flux:select.option>
                        <flux:select.option value="notion">Notion</flux:select.option>
                        <flux:select.option value="loom">Loom Video</flux:select.option>
                        <flux:select.option value="file_upload">File upload</flux:select.option>
                        <flux:select.option value="other">Other</flux:select.option>
                    </flux:select>
                </flux:field>

                @if($delivType !== 'file_upload')
                    <flux:field>
                        <flux:label>URL</flux:label>
                        <flux:input wire:model="delivUrl" type="url" placeholder="https://…" icon="link"/>
                        <flux:error name="delivUrl"/>
                    </flux:field>
                @else
                    <flux:field>
                        <flux:label>File <flux:description>max 20MB</flux:description></flux:label>
                        <input type="file" wire:model="delivFile" class="text-sm text-[#8da0b8]">
                        <flux:error name="delivFile"/>
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>Note (optional)</flux:label>
                    <flux:input wire:model="delivNote" placeholder="Describe what you're submitting…"/>
                </flux:field>

                <flux:button variant="primary" size="sm" wire:click="submitDeliverable" wire:loading.attr="disabled">
                    <span wire:loading.remove>Submit deliverable</span>
                    <span wire:loading>Uploading…</span>
                </flux:button>
            </div>
        @endif
    </div>

    {{-- ── Subtasks ──────────────────────────────────────────── --}}
    @if($task->subtasks->isNotEmpty() || $showSubtaskForm)
        <div class="border border-[#1c2e45] rounded-xl overflow-hidden">
            <div class="flex items-center justify-between px-3 py-2.5 bg-[#080c14] border-b border-[#1c2e45]">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Subtasks</span>
                    <flux:badge size="sm" color="zinc">
                        {{ $task->subtasks->where('status','done')->count() }}/{{ $task->subtasks->count() }}
                    </flux:badge>
                </div>
                <flux:button variant="ghost" size="sm" icon="{{ $showSubtaskForm ? 'x-mark' : 'plus' }}"
                    wire:click="$toggle('showSubtaskForm')" class="!p-1"/>
            </div>

            <div class="divide-y divide-[#1c2e45]">
                @foreach($task->subtasks as $sub)
                    <div class="flex items-center gap-2.5 px-3 py-2.5" wire:key="sub-{{ $sub->id }}">
                        <button wire:click="toggleSubtask({{ $sub->id }})"
                            class="w-4 h-4 rounded flex items-center justify-center flex-shrink-0 border transition-all
                                {{ $sub->status === 'done'
                                    ? 'bg-[#7EE8A2] border-[#7EE8A2]'
                                    : 'border-[#254060] hover:border-[#7EE8A2]' }}">
                            @if($sub->status === 'done')
                                <flux:icon.check class="size-2.5 text-[#080c14]"/>
                            @endif
                        </button>
                        <span class="text-sm flex-1 {{ $sub->status === 'done' ? 'line-through text-[#506070]' : 'text-[#dde6f0]' }}">
                            {{ $sub->title }}
                        </span>
                        @if($sub->assignee)
                            <flux:avatar src="{{ $sub->assignee->avatar_url }}" name="{{ $sub->assignee->name }}" size="xs"/>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($showSubtaskForm)
                <div class="p-3 border-t border-[#1c2e45] flex gap-2">
                    <flux:input wire:model="subtaskTitle" placeholder="Subtask title…" autofocus class="flex-1" size="sm"/>
                    <flux:button size="sm" variant="primary" wire:click="addSubtask">Add</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="$set('showSubtaskForm',false)">Cancel</flux:button>
                </div>
            @endif
        </div>
    @else
        <flux:button variant="ghost" size="sm" icon="plus" wire:click="$set('showSubtaskForm',true)">
            Add subtask
        </flux:button>
    @endif

    {{-- ── Files ─────────────────────────────────────────────── --}}
    @if($task->taskFiles->isNotEmpty())
        <div class="border border-[#1c2e45] rounded-xl overflow-hidden">
            <div class="px-3 py-2.5 bg-[#080c14] border-b border-[#1c2e45]">
                <span class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">
                    Files ({{ $task->taskFiles->count() }})
                </span>
            </div>
            <div class="divide-y divide-[#1c2e45]">
                @foreach($task->taskFiles as $f)
                    <div class="flex items-center gap-2.5 px-3 py-2.5" wire:key="file-{{ $f->id }}">
                        <flux:icon.paper-clip class="size-4 text-[#506070] flex-shrink-0"/>
                        <a href="{{ Storage::url($f->disk_path) }}" target="_blank"
                           class="flex-1 text-xs text-[#8da0b8] hover:text-[#7EE8A2] truncate">
                            {{ $f->original_name }}
                        </a>
                        <span class="text-[10px] text-[#506070]">
                            {{ round($f->file_size / 1024) }}KB
                        </span>
                        <flux:button variant="ghost" size="sm" icon="trash"
                            wire:click="deleteFile({{ $f->id }})"
                            class="text-[#506070] hover:text-red-400 !p-1"/>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── Comments ──────────────────────────────────────────── --}}
    <div class="space-y-3">
        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">
            Comments ({{ $this->comments->count() }})
        </p>

        {{-- New comment --}}
        <div class="space-y-2">
            <flux:textarea
                wire:model="commentBody"
                placeholder="{{ $replyTo ? 'Write a reply…' : 'Add a comment… Use @name to mention someone.' }}"
                rows="3"
            />
            @error('commentBody')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
            <div class="flex items-center gap-2">
                <flux:button size="sm" variant="primary" wire:click="addComment" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $replyTo ? 'Reply' : 'Comment' }}</span>
                    <span wire:loading>Posting…</span>
                </flux:button>
                @if($replyTo)
                    <flux:button size="sm" variant="ghost" wire:click="$set('replyTo',null)">Cancel reply</flux:button>
                @endif
            </div>
        </div>

        {{-- Comment list --}}
        @foreach($this->comments as $comment)
            <div class="space-y-2" wire:key="cmt-{{ $comment->id }}">

                {{-- Parent comment --}}
                <div class="flex gap-2.5">
                    <flux:avatar src="{{ $comment->user->avatar_url }}" name="{{ $comment->user->name }}"
                        size="sm" class="flex-shrink-0 mt-0.5"/>
                    <div class="flex-1 min-w-0">
                        <div class="bg-[#131d2e] border border-[#1c2e45] rounded-xl px-3.5 py-2.5">
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <span class="text-xs font-semibold text-[#dde6f0]">{{ $comment->user->name }}</span>
                                <span class="text-[10px] text-[#506070]">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-[#8da0b8] leading-relaxed whitespace-pre-wrap">{{ $comment->body }}</p>
                        </div>
                        <button
                            wire:click="$set('replyTo',{{ $comment->id }})"
                            class="text-[11px] text-[#506070] hover:text-[#7EE8A2] mt-1 ml-1 transition-colors"
                        >Reply</button>
                    </div>
                </div>

                {{-- Replies --}}
                @foreach($comment->replies as $reply)
                    <div class="flex gap-2.5 ml-8" wire:key="reply-{{ $reply->id }}">
                        <flux:avatar src="{{ $reply->user->avatar_url }}" name="{{ $reply->user->name }}"
                            size="xs" class="flex-shrink-0 mt-0.5"/>
                        <div class="flex-1 min-w-0">
                            <div class="bg-[#0e1420] border border-[#1c2e45] rounded-xl px-3.5 py-2">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-xs font-semibold text-[#dde6f0]">{{ $reply->user->name }}</span>
                                    <span class="text-[10px] text-[#506070]">{{ $reply->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm text-[#8da0b8] leading-relaxed">{{ $reply->body }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

    </div>

</div>
