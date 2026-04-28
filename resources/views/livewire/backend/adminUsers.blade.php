<div class="space-y-5">

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search name or email…" icon="magnifying-glass" clearable/>
        </div>
        <flux:select wire:model.live="role" class="w-32">
            <flux:select.option value="all">All roles</flux:select.option>
            <flux:select.option value="user">User</flux:select.option>
            <flux:select.option value="admin">Admin</flux:select.option>
            <flux:select.option value="moderator">Moderator</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="status" class="w-36">
            <flux:select.option value="all">Any status</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="suspended">Suspended</flux:select.option>
        </flux:select>
    </div>

    {{-- Table --}}
    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>User</flux:table.column>
            <flux:table.column>Role</flux:table.column>
            <flux:table.column>Mode</flux:table.column>
            <flux:table.column>Joined</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach($this->users as $u)
                <flux:table.row :key="$u->id" wire:key="u-{{ $u->id }}">

                    <flux:table.cell>
                        <div class="flex items-center gap-2.5">
                            <flux:avatar name="{{ $u->name }}" size="sm"/>
                            <div>
                                <p class="text-sm font-medium text-white">{{ $u->name }}</p>
                                <p class="text-xs text-[#506070]">{{ $u->email }}</p>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm"
                            :color="match($u->role) {
                                'admin'     => 'red',
                                'moderator' => 'yellow',
                                default     => 'zinc',
                            }">{{ ucfirst($u->role ?? 'user') }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex flex-col gap-1">
                            @if($u->is_marketplace_enabled)
                                <flux:badge size="sm" color="green">Freelancer</flux:badge>
                            @endif
                            <flux:badge size="sm" color="blue">Client</flux:badge>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell>
                        <span class="text-xs text-[#8da0b8]">{{ $u->created_at->format('M d, Y') }}</span>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if($u->suspended_at)
                            <flux:badge size="sm" color="red">Suspended</flux:badge>
                        @else
                            <flux:badge size="sm" color="green">Active</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        <div class="flex gap-1">
                            {{-- Suspend / Unsuspend --}}
                            @if($u->suspended_at)
                                <flux:button variant="ghost" size="sm"
                                    wire:click="unsuspend({{ $u->id }})"
                                    class="text-[#7EE8A2]">Unsuspend</flux:button>
                            @elseif($u->id !== Auth::id())
                                <flux:button variant="ghost" size="sm"
                                    wire:click="suspend({{ $u->id }})"
                                    wire:confirm="Suspend {{ $u->name }}?"
                                    class="text-red-400">Suspend</flux:button>
                            @endif

                            {{-- Verify freelancer --}}
                            @if($u->is_marketplace_enabled && !$u->serviceProfile?->is_verified)
                                <flux:button variant="ghost" size="sm"
                                    wire:click="verifyFreelancer({{ $u->id }})"
                                    class="text-[#7EE8A2]" icon="check-badge">
                                    Verify
                                </flux:button>
                            @endif

                            {{-- Admin toggle --}}
                            @if($u->id !== Auth::id())
                                @if($u->role === 'admin')
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="revokeAdmin({{ $u->id }})"
                                        wire:confirm="Remove admin from {{ $u->name }}?"
                                        class="text-[#506070]">Revoke admin</flux:button>
                                @else
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="makeAdmin({{ $u->id }})"
                                        wire:confirm="Make {{ $u->name }} a super admin?"
                                        class="text-amber-400">Make admin</flux:button>
                                @endif
                            @endif
                        </div>
                    </flux:table.cell>

                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</div>
