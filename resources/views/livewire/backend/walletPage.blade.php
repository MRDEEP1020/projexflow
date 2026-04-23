<div class="max-w-4xl space-y-6">

    <flux:heading size="xl">Wallet</flux:heading>

    {{-- ── Balance cards ───────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-gradient-to-br from-[#0e1420] to-[#131d2e] border border-[#7EE8A2]/20 rounded-2xl p-5">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#506070]">Available balance</p>
            <p class="font-['Syne'] text-3xl font-extrabold text-[#7EE8A2] mt-2 leading-none">
                ${{ number_format($this->wallet->available_balance, 2) }}
            </p>
            <p class="text-xs text-[#506070] mt-1">Ready to withdraw</p>
        </div>

        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-5">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#506070]">Held in escrow</p>
            <p class="font-['Syne'] text-3xl font-extrabold text-amber-400 mt-2 leading-none">
                ${{ number_format($this->wallet->held_balance, 2) }}
            </p>
            <p class="text-xs text-[#506070] mt-1">Pending release</p>
        </div>

        <div class="bg-[#0e1420] border border-[#1c2e45] rounded-2xl p-5">
            <p class="text-[10px] font-mono uppercase tracking-widest text-[#506070]">Total earned</p>
            <p class="font-['Syne'] text-3xl font-extrabold text-white mt-2 leading-none">
                ${{ number_format($this->wallet->total_earned, 2) }}
            </p>
            <p class="text-xs text-[#506070] mt-1">All time</p>
        </div>
    </div>

    {{-- ── Pending withdrawals ──────────────────────────────── --}}
    @if($this->pendingWithdrawals->isNotEmpty())
        <flux:callout icon="clock" color="yellow">
            <flux:callout.heading>Pending withdrawals</flux:callout.heading>
            <flux:callout.text>
                {{ $this->pendingWithdrawals->count() }} withdrawal(s) processing.
                Total: ${{ number_format($this->pendingWithdrawals->sum('amount'), 2) }}
            </flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ── Withdrawal form ──────────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
            <flux:heading size="lg">Withdraw funds</flux:heading>

            <form wire:submit="requestWithdrawal" class="space-y-4">

                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Amount <<span class="text-red-400">*</span>/></flux:label>
                        <flux:input wire:model="amount" type="number" min="1"
                            :max="$this->wallet->available_balance"
                            placeholder="0.00"/>
                        <flux:error name="amount"/>
                        <flux:description>Available: ${{ number_format($this->wallet->available_balance, 2) }}</flux:description>
                    </flux:field>
                    <flux:field>
                        <flux:label>Currency</flux:label>
                        <flux:select wire:model="currency">
                            <flux:select.option value="USD">USD</flux:select.option>
                            <flux:select.option value="XAF">XAF (FCFA)</flux:select.option>
                            <flux:select.option value="EUR">EUR</flux:select.option>
                            <flux:select.option value="NGN">NGN</flux:select.option>
                        </flux:select>
                    </flux:field>
                </div>

                {{-- Method selector --}}
                <div class="grid grid-cols-3 gap-2">
                    @foreach([
                        ['value' => 'mobile_money', 'icon' => 'device-phone-mobile', 'label' => 'Mobile Money'],
                        ['value' => 'bank',         'icon' => 'building-library',    'label' => 'Bank transfer'],
                        ['value' => 'stripe',       'icon' => 'credit-card',         'label' => 'Stripe'],
                    ] as $m)
                        <label class="flex flex-col items-center gap-1.5 p-3 rounded-xl border cursor-pointer text-center text-xs font-medium transition-all
                            {{ $method === $m['value']
                                ? 'border-[#7EE8A2] bg-[#7EE8A2]/05 text-[#7EE8A2]'
                                : 'border-[#1c2e45] text-[#506070] hover:border-[#254060]' }}">
                            <input type="radio" wire:model.live="method" value="{{ $m['value'] }}" class="sr-only">
                            <flux:icon :name="$m['icon']" class="size-5"/>
                            {{ $m['label'] }}
                        </label>
                    @endforeach
                </div>

                {{-- Mobile money fields --}}
                @if($method === 'mobile_money')
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>Phone number <<span class="text-red-400">*</span>/></flux:label>
                            <flux:input wire:model="mmPhone" placeholder="+237 6XX XXX XXX" icon="device-phone-mobile"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>Operator</flux:label>
                            <flux:select wire:model="mmOperator">
                                <flux:select.option value="mtn">MTN Mobile Money</flux:select.option>
                                <flux:select.option value="orange">Orange Money</flux:select.option>
                                <flux:select.option value="airtel">Airtel Money</flux:select.option>
                                <flux:select.option value="mpesa">M-Pesa</flux:select.option>
                                <flux:select.option value="wave">Wave</flux:select.option>
                            </flux:select>
                        </flux:field>
                    </div>
                    <flux:field>
                        <flux:label>Country</flux:label>
                        <flux:select wire:model="mmCountry">
                            <flux:select.option value="CM">Cameroon 🇨🇲</flux:select.option>
                            <flux:select.option value="NG">Nigeria 🇳🇬</flux:select.option>
                            <flux:select.option value="GH">Ghana 🇬🇭</flux:select.option>
                            <flux:select.option value="KE">Kenya 🇰🇪</flux:select.option>
                            <flux:select.option value="SN">Senegal 🇸🇳</flux:select.option>
                            <flux:select.option value="CI">Côte d'Ivoire 🇨🇮</flux:select.option>
                            <flux:select.option value="TZ">Tanzania 🇹🇿</flux:select.option>
                            <flux:select.option value="UG">Uganda 🇺🇬</flux:select.option>
                        </flux:select>
                    </flux:field>
                @endif

                {{-- Bank fields --}}
                @if($method === 'bank')
                    <flux:field>
                        <flux:label>Account number <<span class="text-red-400">*</span>/></flux:label>
                        <flux:input wire:model="bankAccount" icon="building-library"/>
                    </flux:field>
                    <div class="grid grid-cols-2 gap-3">
                        <flux:field>
                            <flux:label>Bank name</flux:label>
                            <flux:input wire:model="bankName" placeholder="e.g. Afriland First Bank"/>
                        </flux:field>
                        <flux:field>
                            <flux:label>SWIFT code</flux:label>
                            <flux:input wire:model="bankSwift" placeholder="e.g. CCEICMCX"/>
                        </flux:field>
                    </div>
                @endif

                {{-- Stripe --}}
                @if($method === 'stripe')
                    @if(Auth::user()->stripe_connect_id)
                        <flux:callout icon="check-circle" color="green">
                            <flux:callout.text>Stripe account connected. Funds will be paid out to your connected account.</flux:callout.text>
                        </flux:callout>
                    @else
                        <flux:callout icon="exclamation-triangle" color="yellow">
                            <flux:callout.heading>Verification required</flux:callout.heading>
                            <flux:callout.text>Connect your Stripe account to enable direct payouts.</flux:callout.text>
                        </flux:callout>
                    @endif
                @endif

                <flux:error name="method"/>

                <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled"
                    :disabled="$this->wallet->available_balance <= 0">
                    <span wire:loading.remove>Request withdrawal</span>
                    <span wire:loading>Processing…</span>
                </flux:button>
            </form>
        </flux:card>

        {{-- ── Transaction history ──────────────────────────── --}}
        <flux:card class="bg-[#0e1420] border-[#1c2e45] space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Transactions</flux:heading>
                <flux:select wire:model.live="txFilter" size="sm" class="w-32">
                    <flux:select.option value="all">All types</flux:select.option>
                    <flux:select.option value="deposit">Deposits</flux:select.option>
                    <flux:select.option value="milestone_release">Releases</flux:select.option>
                    <flux:select.option value="platform_fee">Fees</flux:select.option>
                    <flux:select.option value="refund">Refunds</flux:select.option>
                </flux:select>
            </div>

            @if($this->transactions->isEmpty())
                <div class="flex flex-col items-center gap-2 py-8 text-[#506070]">
                    <flux:icon.banknotes class="size-8"/>
                    <p class="text-sm">No transactions yet.</p>
                </div>
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($this->transactions as $tx)
                        <div class="flex items-center gap-3 p-3 bg-[#080c14] border border-[#1c2e45] rounded-xl"
                             wire:key="tx-{{ $tx->id }}">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                {{ in_array($tx->type, ['deposit','milestone_release'])
                                    ? 'bg-[#7EE8A2]/10 text-[#7EE8A2]'
                                    : 'bg-amber-500/10 text-amber-400' }}">
                                <flux:icon.banknotes class="size-4"/>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-[#dde6f0]">
                                    {{ ucfirst(str_replace('_',' ',$tx->type)) }}
                                </p>
                                <p class="text-[10px] text-[#506070]">
                                    {{ $tx->created_at->format('M d, Y') }}
                                    @if($tx->contract) · {{ $tx->contract->title }} @endif
                                </p>
                            </div>
                            <span class="font-mono text-sm font-semibold
                                {{ in_array($tx->type, ['deposit','milestone_release']) && $tx->payee_id === Auth::id()
                                    ? 'text-[#7EE8A2]'
                                    : 'text-amber-400' }}">
                                {{ in_array($tx->type, ['deposit','milestone_release']) && $tx->payee_id === Auth::id() ? '+' : '-' }}${{ number_format($tx->amount, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

    </div>
</div>
