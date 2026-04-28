<div class="space-y-5">

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-[#1c2e45]">
        @foreach(['open'=>'Open','resolved'=>'Resolved','all'=>'All'] as $key => $label)
            <button wire:click="$set('filter','{{ $key }}')"
                class="px-4 py-2 text-sm border-b-2 -mb-px transition-all"
                style="{{ $filter === $key ? 'border-color:#f87171;color:#f87171' : 'border-color:transparent;color:#8da0b8' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->disputes">
        <flux:table.columns>
            <flux:table.column>Contract</flux:table.column>
            <flux:table.column>Raised by</flux:table.column>
            <flux:table.column>Reason</flux:table.column>
            <flux:table.column>Amount</flux:table.column>
            <flux:table.column>Date</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->disputes as $d)
                <flux:table.row :key="$d->id" wire:key="d-{{ $d->id }}">

                    <flux:table.cell>
                        <div>
                            <p class="text-sm font-medium text-white">{{ $d->contract->title }}</p>
                            <p class="text-xs text-[#506070]">
                                {{ $d->contract->client->name }} vs {{ $d->contract->freelancer->name }}
                            </p>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <p class="text-sm text-[#dde6f0]">{{ $d->raisedBy->name ?? '—' }}</p>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-xs text-[#8da0b8]">{{ ucfirst(str_replace('_',' ',$d->reason)) }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="font-mono text-sm text-white">${{ number_format($d->contract->total_amount) }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-xs text-[#506070]">{{ $d->created_at->format('M d, Y') }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm"
                            :color="match($d->status) {
                                'open'     => 'red',
                                'resolved' => 'green',
                                default    => 'zinc',
                            }">{{ ucfirst($d->status) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($d->status === 'open')
                            <flux:button variant="primary" size="sm"
                                wire:click="$set('viewId', {{ $d->id }})">
                                Resolve
                            </flux:button>
                        @endif
                    </flux:table.cell>

                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>

{{-- Resolve drawer --}}
@if($viewId && $this->openDispute)
    @php $d = $this->openDispute; @endphp
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="$set('viewId',null)"></div>
        <div class="relative z-50 w-full max-w-lg bg-[#0e1420] border-l border-[#1c2e45] h-full overflow-y-auto shadow-2xl"
             style="animation:slideIn .25s ease both">

            <div class="flex items-center justify-between px-5 py-4 border-b border-[#1c2e45] sticky top-0 bg-[#0e1420] z-10">
                <p class="font-['Syne'] font-bold text-white">Resolve Dispute</p>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="$set('viewId',null)"/>
            </div>

            <div class="p-5 space-y-5">

                {{-- Contract summary --}}
                <div class="p-4 bg-[#080c14] border border-[#1c2e45] rounded-xl space-y-2">
                    <p class="text-sm font-semibold text-white">{{ $d->contract->title }}</p>
                    <div class="grid grid-cols-2 gap-2 text-xs text-[#8da0b8]">
                        <div><span class="text-[#506070]">Client:</span> {{ $d->contract->client->name }}</div>
                        <div><span class="text-[#506070]">Freelancer:</span> {{ $d->contract->freelancer->name }}</div>
                        <div><span class="text-[#506070]">Value:</span> <span class="text-white font-mono">${{ number_format($d->contract->total_amount) }}</span></div>
                        <div><span class="text-[#506070]">Raised by:</span> {{ $d->raisedBy->name ?? '—' }}</div>
                    </div>
                </div>

                {{-- Dispute description --}}
                <div>
                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070] mb-2">Dispute reason</p>
                    <p class="text-xs text-[#8da0b8] bg-[#131d2e] rounded-xl p-3 border border-[#1c2e45] leading-relaxed">
                        <strong class="text-[#dde6f0]">{{ ucfirst(str_replace('_',' ',$d->reason)) }}:</strong>
                        {{ $d->description }}
                    </p>
                </div>

                {{-- Resolution form --}}
                <div class="space-y-4">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-[#506070]">Resolution</p>

                    {{-- Who gets paid --}}
                    <div class="grid grid-cols-3 gap-2">
                        @foreach([
                            ['val' => 'freelancer', 'label' => 'Freelancer wins', 'color' => 'text-[#7EE8A2]'],
                            ['val' => 'client',     'label' => 'Client refund',   'color' => 'text-blue-400'],
                            ['val' => 'split',      'label' => 'Split payment',   'color' => 'text-amber-400'],
                        ] as $opt)
                            <label class="flex flex-col items-center p-3 rounded-xl border cursor-pointer text-center text-xs font-medium transition-all
                                {{ $resolveFor === $opt['val']
                                    ? 'border-[#7EE8A2] bg-[#7EE8A2]/06 '.$opt['color']
                                    : 'border-[#1c2e45] text-[#506070] hover:border-[#254060]' }}">
                                <input type="radio" wire:model.live="resolveFor" value="{{ $opt['val'] }}" class="sr-only">
                                {{ $opt['label'] }}
                            </label>
                        @endforeach
                    </div>

                    {{-- Split percentage --}}
                    @if($resolveFor === 'split')
                        <flux:field>
                            <flux:label>Freelancer receives (%)</flux:label>
                            <flux:input wire:model.live="splitPct" type="number" min="0" max="100" step="5"/>
                            <flux:description>
                                Freelancer: ${{ number_format(($d->contract->total_amount - $d->contract->platform_fee_amount) * ($splitPct / 100), 2) }}
                                · Client: ${{ number_format(($d->contract->total_amount - $d->contract->platform_fee_amount) * ((100 - $splitPct) / 100), 2) }}
                            </flux:description>
                        </flux:field>
                    @endif

                    {{-- Resolution notes --}}
                    <flux:field>
                        <flux:label>Resolution notes <<span class="text-red-400">*</span>/></flux:label>
                        <flux:textarea wire:model="resolution" rows="4"
                            placeholder="Explain the resolution decision. This will be shared with both parties."/>
                        <flux:error name="resolution"/>
                    </flux:field>

                    <flux:button variant="primary" class="w-full" icon="check-circle"
                        wire:click="resolve({{ $d->id }})"
                        wire:confirm="Resolve this dispute? This action cannot be undone."
                        wire:loading.attr="disabled"
                        :disabled="!$resolveFor">
                        <span wire:loading.remove>Resolve dispute</span>
                        <span wire:loading>Resolving…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    </div>
    <style>@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}</style>
@endif
