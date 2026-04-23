<div class="max-w-5xl space-y-5">

    {{-- ── LIST VIEW ──────────────────────────────────────────── --}}
    @if($view === 'list')

        <div class="flex items-center justify-between">
            <flux:heading size="xl">Contracts</flux:heading>
            <flux:button variant="primary" size="sm" icon="plus" wire:click="$set('view','create')">
                New contract
            </flux:button>
        </div>

        {{-- Status filter tabs --}}
        <div class="flex gap-1 border-b border-[#1c2e45] flex-wrap">
            @foreach(['all'=>'All','draft'=>'Draft','active'=>'Active','submitted'=>'Review','completed'=>'Completed','disputed'=>'Disputed'] as $key => $label)
                <button wire:click="$set('filterStatus','{{ $key }}')"
                    class="flex items-center gap-1.5 px-3 py-2 text-sm border-b-2 -mb-px transition-all"
                    style="{{ $filterStatus === $key ? 'border-color:#7EE8A2;color:#7EE8A2' : 'border-color:transparent;color:#8da0b8' }}">
                    {{ $label }}
                    @if($this->contractCounts[$key] > 0)
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded"
                              style="{{ $key === 'disputed' ? 'background:rgba(239,68,68,.15);color:#f87171' : 'background:#1c2e45;color:#8da0b8' }}">
                            {{ $this->contractCounts[$key] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        @if($this->contracts->isEmpty())
            <div class="flex flex-col items-center gap-3 py-14 bg-[#0e1420] border border-dashed border-[#1c2e45] rounded-2xl text-center">
                <flux:icon.document-text class="size-8 text-[#506070]"/>
                <flux:heading>No contracts yet</flux:heading>
                <flux:text class="text-sm">Create a contract to start working with a client securely.</flux:text>
                <flux:button variant="primary" size="sm" wire:click="$set('view','create')">Create contract</flux:button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->contracts as $c)
                    <div wire:click="$set('openContractId', {{ $c->id }}); $set('view','detail')"
                         wire:key="ct-{{ $c->id }}"
                         class="flex items-start gap-4 bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4 hover:border-[#254060] cursor-pointer transition-all">

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-['Syne'] font-semibold text-sm text-white">{{ $c->title }}</p>
                                <flux:badge size="sm"
                                    :color="match($c->status) {
                                        'active'    => 'green',
                                        'submitted' => 'yellow',
                                        'completed' => 'lime',
                                        'disputed'  => 'red',
                                        'refunded'  => 'orange',
                                        default     => 'zinc',
                                    }"
                                >{{ ucfirst($c->status) }}</flux:badge>
                            </div>
                            <div class="flex items-center gap-3 mt-1.5 text-xs text-[#506070]">
                                @if($c->freelancer_id === Auth::id())
                                    <span>Client: {{ $c->client->name }}</span>
                                @else
                                    <span>Freelancer: {{ $c->freelancer->name }}</span>
                                @endif
                                <span>Created {{ $c->created_at->diffForHumans() }}</span>
                                @if($c->status === 'submitted' && $c->auto_release_at)
                                    <span class="text-amber-400">
                                        Auto-release {{ $c->auto_release_at->diffForHumans() }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <p class="font-['Syne'] text-base font-bold text-white">
                                {{ $c->currency }} {{ number_format($c->total_amount) }}
                            </p>
                            <p class="text-[11px] text-[#506070]">
                                Deposit: {{ $c->currency }} {{ number_format($c->deposit_amount) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    {{-- ── CREATE VIEW ────────────────────────────────────────── --}}
    @if($view === 'create')
        <div class="space-y-5" wire:key="create-view">
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="$set('view','list')"/>
                <flux:heading size="xl">New Contract</flux:heading>
            </div>

            <flux:card class="bg-[#0e1420] border-[#1c2e45]">
                <form wire:submit="createContract" class="space-y-5">

                    <flux:field>
                        <flux:label>Contract title <<span class="text-red-400">*</span>/></flux:label>
                        <flux:input wire:model="ctTitle" placeholder="e.g. Website Redesign — Acme Corp"/>
                        <flux:error name="ctTitle"/>
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="ctDescription" rows="3" placeholder="Scope of work…"/>
                    </flux:field>

                    <flux:field>
                        <flux:label>Client <<span class="text-red-400">*</span>/></flux:label>
                        <flux:input wire:model.live="ctClientId" type="number" placeholder="Client user ID (search coming soon)"/>
                        <flux:description>Enter the client's user ID from the platform.</flux:description>
                        <flux:error name="ctClientId"/>
                    </flux:field>

                    <div class="grid grid-cols-3 gap-4">
                        <flux:field>
                            <flux:label>Total amount <<span class="text-red-400">*</span>/></flux:label>
                            <flux:input wire:model.live="ctTotalAmount" type="number" min="1" step="0.01" placeholder="1000"/>
                            <flux:error name="ctTotalAmount"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>Deposit % <<span class="text-red-400">*</span>/></flux:label>
                            <flux:input wire:model.live="ctDepositPct" type="number" min="1" max="100" placeholder="30"/>
                            <flux:error name="ctDepositPct"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>Currency</flux:label>
                            <flux:select wire:model="ctCurrency">
                                <flux:select.option value="USD">USD</flux:select.option>
                                <flux:select.option value="EUR">EUR</flux:select.option>
                                <flux:select.option value="XAF">XAF (FCFA)</flux:select.option>
                                <flux:select.option value="GBP">GBP</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>

                    {{-- Live fee breakdown --}}
                    @if($ctTotalAmount > 0)
                        <div class="grid grid-cols-3 gap-3 p-4 rounded-xl bg-[#080c14] border border-[#1c2e45]">
                            <div>
                                <p class="text-[10px] text-[#506070] font-mono uppercase">Client pays now</p>
                                <p class="font-['Syne'] text-lg font-bold text-[#7EE8A2]">
                                    {{ $ctCurrency }} {{ number_format(round($ctTotalAmount * $ctDepositPct / 100, 2)) }}
                                </p>
                                <p class="text-[10px] text-[#506070]">{{ $ctDepositPct }}% deposit</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#506070] font-mono uppercase">On completion</p>
                                <p class="font-['Syne'] text-lg font-bold text-white">
                                    {{ $ctCurrency }} {{ number_format(round($ctTotalAmount * (1 - $ctDepositPct/100), 2)) }}
                                </p>
                                <p class="text-[10px] text-[#506070]">remaining balance</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-[#506070] font-mono uppercase">Platform fee (10%)</p>
                                <p class="font-['Syne'] text-lg font-bold text-amber-400">
                                    {{ $ctCurrency }} {{ number_format(round($ctTotalAmount * 0.10, 2)) }}
                                </p>
                                <p class="text-[10px] text-[#506070]">deducted on release</p>
                            </div>
                        </div>
                    @endif

                    {{-- Milestones --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <flux:label>Milestones <span class="text-[#506070] font-normal text-xs">(optional)</span></flux:label>
                            @if(count($ctMilestones) > 0 && $ctTotalAmount > 0)
                                @php $diff = abs($this->getMsTotal() - $ctTotalAmount); @endphp
                                <span class="text-xs font-mono {{ $diff < 0.01 ? 'text-[#7EE8A2]' : 'text-red-400' }}">
                                    Total: {{ $ctCurrency }} {{ number_format($this->getMsTotal()) }}
                                    / {{ number_format($ctTotalAmount) }}
                                </span>
                            @endif
                        </div>

                        @foreach($ctMilestones as $i => $ms)
                            <div class="flex items-center gap-3 p-3 bg-[#080c14] border border-[#1c2e45] rounded-xl"
                                 wire:key="ms-{{ $i }}">
                                <div class="flex-1">
                                    <p class="text-sm text-[#dde6f0]">{{ $ms['title'] }}</p>
                                    <p class="text-xs text-[#506070]">
                                        {{ $ctCurrency }} {{ number_format($ms['amount']) }}
                                        @if($ms['due_date']) · Due {{ $ms['due_date'] }} @endif
                                    </p>
                                </div>
                                <flux:button variant="ghost" size="sm" icon="trash"
                                    wire:click="removeMilestone({{ $i }})"
                                    class="text-[#506070] hover:text-red-400"/>
                            </div>
                        @endforeach

                        {{-- Add milestone --}}
                        <div class="flex flex-wrap gap-2 p-3 bg-[#080c14] border border-dashed border-[#1c2e45] rounded-xl">
                            <flux:input wire:model="ctNewMsTitle" placeholder="Milestone title" class="flex-1 min-w-32"/>
                            <flux:input wire:model="ctNewMsAmount" type="number" min="1" placeholder="Amount" class="w-28"/>
                            <flux:input wire:model="ctNewMsDue" type="date" class="w-36"/>
                            <flux:button type="button" variant="ghost" size="sm" icon="plus" wire:click="addMilestone">Add</flux:button>
                        </div>
                        @error('ctMilestones')<p class="text-xs text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-[#1c2e45]">
                        <flux:button variant="ghost" wire:click="$set('view','list')">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" icon="document-check">
                            <span wire:loading.remove>Create contract</span>
                            <span wire:loading>Creating…</span>
                        </flux:button>
                    </div>
                </form>
            </flux:card>
        </div>
    @endif

    {{-- ── DETAIL VIEW ─────────────────────────────────────────── --}}
    @if($view === 'detail' && $this->openContract)
        @php $c = $this->openContract; $isFreelancer = $c->freelancer_id === Auth::id(); @endphp

        <div class="space-y-5" wire:key="detail-{{ $c->id }}">

            <div class="flex items-center gap-3">
                <flux:button variant="ghost" size="sm" icon="arrow-left" wire:click="$set('view','list')"/>
                <div class="flex-1 min-w-0">
                    <flux:heading size="lg">{{ $c->title }}</flux:heading>
                </div>
                <flux:badge size="sm"
                    :color="match($c->status) {
                        'active'    => 'green',
                        'submitted' => 'yellow',
                        'completed' => 'lime',
                        'disputed'  => 'red',
                        default     => 'zinc',
                    }"
                >{{ ucfirst($c->status) }}</flux:badge>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach([
                    ['label' => 'Total value',   'value' => $c->currency . ' ' . number_format($c->total_amount),    'color' => 'text-white'],
                    ['label' => 'Deposit',        'value' => $c->currency . ' ' . number_format($c->deposit_amount),  'color' => 'text-[#7EE8A2]'],
                    ['label' => 'Platform fee',   'value' => $c->currency . ' ' . number_format($c->platform_fee_amount), 'color' => 'text-amber-400'],
                    ['label' => 'You receive',    'value' => $c->currency . ' ' . number_format($c->total_amount - $c->platform_fee_amount), 'color' => 'text-[#7EE8A2]'],
                ] as $stat)
                    <div class="p-3 bg-[#0e1420] border border-[#1c2e45] rounded-xl">
                        <p class="text-[10px] text-[#506070] font-mono uppercase">{{ $stat['label'] }}</p>
                        <p class="font-['Syne'] text-base font-bold {{ $stat['color'] }} mt-1">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Action area --}}
            <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
                <flux:heading size="sm">Actions</flux:heading>

                {{-- Freelancer: Submit work --}}
                @if($isFreelancer && $c->status === 'active')
                    <flux:callout icon="information-circle" color="blue">
                        <flux:callout.heading>Ready to deliver?</flux:callout.heading>
                        <flux:callout.text>Submit your work. The client will have 7 days to approve or request changes. If no response, payment releases automatically.</flux:callout.text>
                    </flux:callout>
                    <flux:button variant="primary" icon="paper-airplane"
                        wire:click="submitWork({{ $c->id }})" wire:loading.attr="disabled">
                        <span wire:loading.remove>Submit work for review</span>
                        <span wire:loading>Submitting…</span>
                    </flux:button>
                @endif

                {{-- Client: Pay deposit (draft) --}}
                @if(! $isFreelancer && $c->status === 'draft')
                    <flux:callout icon="credit-card" color="yellow">
                        <flux:callout.heading>Deposit required to activate</flux:callout.heading>
                        <flux:callout.text>Pay the deposit of {{ $c->currency }} {{ number_format($c->deposit_amount) }} to allow work to begin. Payment via Stripe.</flux:callout.text>
                    </flux:callout>
                    <div id="stripe-payment-element" class="p-4 bg-[#080c14] border border-[#1c2e45] rounded-xl min-h-[80px] flex items-center justify-center">
                        <p class="text-xs text-[#506070]">Stripe Elements mounts here — wire up with Livewire JS hooks.</p>
                    </div>
                @endif

                {{-- Client: Approve + dispute (submitted) --}}
                @if(! $isFreelancer && $c->status === 'submitted')
                    @if($c->auto_release_at)
                        <div class="flex items-center gap-2 text-xs text-amber-400 bg-amber-500/08 border border-amber-500/20 rounded-lg px-3 py-2">
                            <flux:icon.clock class="size-4 flex-shrink-0"/>
                            Auto-release on {{ $c->auto_release_at->format('M d, Y \a\t H:i') }}
                            ({{ $c->auto_release_at->diffForHumans() }})
                        </div>
                    @endif
                    <div class="flex gap-2 flex-wrap">
                        <flux:button variant="primary" icon="check-circle"
                            wire:click="releasePayment({{ $c->id }})" wire:loading.attr="disabled">
                            <span wire:loading.remove>Approve &amp; release payment</span>
                            <span wire:loading>Processing…</span>
                        </flux:button>
                        <flux:button variant="ghost"
                            class="border-red-500/20 text-red-400 hover:border-red-400"
                            wire:click="$set('showDisputeForm', true)"
                        >Open dispute</flux:button>
                    </div>
                @endif

                {{-- Dispute (active/submitted) --}}
                @if(in_array($c->status, ['active','submitted']) && !$showDisputeForm)
                    <flux:button variant="ghost" size="sm" icon="exclamation-triangle"
                        class="text-[#506070] hover:text-red-400"
                        wire:click="$set('showDisputeForm', true)"
                    >Open dispute</flux:button>
                @endif

                @if($showDisputeForm)
                    <div class="p-4 bg-red-500/04 border border-red-500/20 rounded-xl space-y-3">
                        <p class="text-sm font-semibold text-red-400">Open a dispute</p>
                        <flux:field>
                            <flux:label>Reason</flux:label>
                            <flux:select wire:model="disputeReason">
                                <flux:select.option value="">Select reason…</flux:select.option>
                                <flux:select.option value="work_not_delivered">Work not delivered</flux:select.option>
                                <flux:select.option value="quality_issues">Quality doesn't meet spec</flux:select.option>
                                <flux:select.option value="scope_creep">Scope changed without agreement</flux:select.option>
                                <flux:select.option value="non_responsive">Non-responsive</flux:select.option>
                                <flux:select.option value="other">Other</flux:select.option>
                            </flux:select>
                        </flux:field>
                        <flux:field>
                            <flux:label>Description <span class="text-[#506070] font-normal text-xs">(min 50 chars)</span></flux:label>
                            <flux:textarea wire:model="disputeDescription" rows="4"
                                placeholder="Explain the issue in detail. This will be reviewed by our team."/>
                            <flux:error name="disputeDescription"/>
                        </flux:field>
                        <div class="flex gap-2">
                            <flux:button variant="danger" wire:click="openDispute({{ $c->id }})">Submit dispute</flux:button>
                            <flux:button variant="ghost" wire:click="$set('showDisputeForm', false)">Cancel</flux:button>
                        </div>
                    </div>
                @endif

                {{-- Disputed state --}}
                @if($c->status === 'disputed' && $c->dispute)
                    <flux:callout icon="exclamation-triangle" color="red">
                        <flux:callout.heading>Dispute open</flux:callout.heading>
                        <flux:callout.text>
                            Funds are frozen. Our team is reviewing the case.
                            Reason: {{ ucfirst(str_replace('_',' ',$c->dispute->reason)) }}.
                            Status: {{ ucfirst($c->dispute->status) }}.
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

            {{-- Milestones --}}
            @if($c->milestones->isNotEmpty())
                <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
                    <div class="px-4 py-3 border-b border-[#1c2e45]">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Milestones</p>
                    </div>
                    <div class="divide-y divide-[#1c2e45]">
                        @foreach($c->milestones as $ms)
                            <div class="flex items-center gap-3 px-4 py-3" wire:key="cms-{{ $ms->id }}">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center border-2 flex-shrink-0
                                    {{ $ms->status === 'completed' ? 'bg-[#7EE8A2] border-[#7EE8A2]' : 'border-[#254060]' }}">
                                    @if($ms->status === 'completed')
                                        <flux:icon.check class="size-3 text-[#080c14]"/>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-[#dde6f0]">{{ $ms->title }}</p>
                                    @if($ms->due_date)
                                        <p class="text-xs text-[#506070]">Due {{ \Carbon\Carbon::parse($ms->due_date)->format('M d, Y') }}</p>
                                    @endif
                                </div>
                                <span class="font-mono text-sm text-[#7EE8A2]">
                                    {{ $c->currency }} {{ number_format($ms->amount) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- Transaction history --}}
            @if($c->transactions->isNotEmpty())
                <flux:card class="bg-[#0e1420] border-[#1c2e45] !p-0 overflow-hidden">
                    <div class="px-4 py-3 border-b border-[#1c2e45]">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Transaction history</p>
                    </div>
                    <div class="divide-y divide-[#1c2e45]">
                        @foreach($c->transactions->sortByDesc('created_at') as $tx)
                            <div class="flex items-center gap-3 px-4 py-3" wire:key="tx-{{ $tx->id }}">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0
                                    {{ $tx->type === 'platform_fee' ? 'bg-amber-500/10 text-amber-400' : 'bg-[#7EE8A2]/10 text-[#7EE8A2]' }}">
                                    <flux:icon.banknotes class="size-4"/>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm text-[#dde6f0]">{{ ucfirst(str_replace('_',' ',$tx->type)) }}</p>
                                    <p class="text-[11px] text-[#506070]">{{ $tx->created_at->format('M d, Y H:i') }}</p>
                                </div>
                                <flux:badge size="sm" :color="$tx->status === 'completed' ? 'green' : 'yellow'">
                                    {{ $tx->status }}
                                </flux:badge>
                                <span class="font-mono text-sm font-semibold {{ $tx->type === 'platform_fee' ? 'text-amber-400' : 'text-[#7EE8A2]' }}">
                                    {{ $c->currency }} {{ number_format($tx->amount, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

        </div>
    @endif
</div>
