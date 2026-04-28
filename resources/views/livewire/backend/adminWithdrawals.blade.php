{{-- ════════════════════════════════════════════════════════════
     resources/views/livewire/admin/adminWithdrawals.blade.php
     ════════════════════════════════════════════════════════════ --}}
<div class="space-y-5">

    {{-- Totals --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-[#0e1420] border border-amber-500/25 rounded-xl p-4">
            <p class="text-[10px] font-mono uppercase text-[#506070]">Pending amount</p>
            <p class="font-['Syne'] text-2xl font-bold text-amber-400 mt-1">${{ number_format($this->totals['pending'],0) }}</p>
        </div>
        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4">
            <p class="text-[10px] font-mono uppercase text-[#506070]">Total paid out</p>
            <p class="font-['Syne'] text-2xl font-bold text-[#7EE8A2] mt-1">${{ number_format($this->totals['completed'],0) }}</p>
        </div>
        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-xl p-4">
            <p class="text-[10px] font-mono uppercase text-[#506070]">Failed requests</p>
            <p class="font-['Syne'] text-2xl font-bold text-red-400 mt-1">{{ $this->totals['failed'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex gap-3">
        <flux:select wire:model.live="filter" class="w-36">
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="processing">Processing</flux:select.option>
            <flux:select.option value="completed">Completed</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
            <flux:select.option value="all">All</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="method" class="w-40">
            <flux:select.option value="all">All methods</flux:select.option>
            <flux:select.option value="mobile_money">Mobile Money</flux:select.option>
            <flux:select.option value="bank">Bank transfer</flux:select.option>
            <flux:select.option value="stripe">Stripe</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->withdrawals">
        <flux:table.columns>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Method</flux:table.column>
            <flux:table.column>Requested</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->withdrawals as $w)
                <flux:table.row :key="$w->id" wire:key="w-{{ $w->id }}">
                    <flux:table.cell>
                        <div class="flex items-center gap-2.5">
                            <flux:avatar name="{{ $w->user->name }}" size="xs"/>
                            <div>
                                <p class="text-sm text-white">{{ $w->user->name }}</p>
                                <p class="text-xs text-[#506070]">{{ $w->user->email }}</p>
                            </div>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="font-mono font-bold text-white">{{ $w->currency }} {{ number_format($w->amount,2) }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="zinc">{{ ucfirst(str_replace('_',' ',$w->method)) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span class="text-xs text-[#8da0b8]">{{ $w->created_at->format('M d, Y H:i') }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm"
                            :color="match($w->status) {
                                'completed'  => 'green',
                                'processing' => 'blue',
                                'pending'    => 'yellow',
                                'failed'     => 'red',
                                default      => 'zinc',
                            }">{{ ucfirst($w->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($w->status === 'pending')
                            <div class="flex gap-1">
                                <flux:button variant="primary" size="sm"
                                    wire:click="approve({{ $w->id }})"
                                    wire:confirm="Force-approve this withdrawal?">
                                    Approve
                                </flux:button>
                                <flux:button variant="ghost" size="sm"
                                    wire:click="$set('viewId', {{ $w->id }})"
                                    class="text-red-400">
                                    Reject
                                </flux:button>
                            </div>
                        @elseif($w->payout_ref)
                            <span class="text-[10px] font-mono text-[#506070]">{{ $w->payout_ref }}</span>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>

{{-- Reject modal --}}
@if($viewId && $this->openRequest)
    <flux:modal wire:model.live="viewId" class="max-w-md">
        <div class="space-y-4">
            <flux:heading>Reject withdrawal</flux:heading>
            <p class="text-sm text-[#8da0b8]">
                {{ $this->openRequest->user->name }} · {{ $this->openRequest->currency }} {{ number_format($this->openRequest->amount,2) }}
            </p>
            <flux:field>
                <flux:label>Reason for rejection <<span class="text-red-400">*</span>/></flux:label>
                <flux:textarea wire:model="failNote" rows="3"
                    placeholder="Explain why this withdrawal is being rejected. The user will see this."/>
                <flux:error name="failNote"/>
            </flux:field>
            <div class="flex gap-2 justify-end">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="reject({{ $viewId }})">Reject & refund wallet</flux:button>
            </div>
        </div>
    </flux:modal>
@endif
