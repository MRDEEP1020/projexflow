<div class="space-y-5">

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-[#1c2e45]">
        @foreach(['jobs'=>'Job Posts','reviews'=>'Reviews','profiles'=>'Profiles'] as $key => $label)
            <button wire:click="$set('tab','{{ $key }}')"
                class="px-4 py-2.5 text-sm border-b-2 -mb-px transition-all"
                style="{{ $tab === $key ? 'border-color:#f87171;color:#f87171' : 'border-color:transparent;color:#8da0b8' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ── JOBS ──────────────────────────────────────────────── --}}
    @if($tab === 'jobs')
        <flux:table :paginate="$this->pendingJobs">
            <flux:table.columns>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Client</flux:table.column>
                <flux:table.column>Budget</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->pendingJobs as $j)
                    <flux:table.row :key="$j->id" wire:key="j-{{ $j->id }}">
                        <flux:table.cell>
                            <div>
                                <p class="text-sm text-white">{{ $j->title }}</p>
                                <p class="text-xs text-[#506070]">{{ $j->created_at->diffForHumans() }}</p>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <p class="text-sm text-[#8da0b8]">{{ $j->client->name }}</p>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($j->budget_max)
                                <span class="font-mono text-sm text-[#7EE8A2]">
                                    {{ $j->currency }} {{ number_format($j->budget_min??0) }}–{{ number_format($j->budget_max) }}
                                </span>
                            @else
                                <span class="text-[#506070] text-xs">Not set</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm"
                                :color="match($j->status) {
                                    'open'    => 'green',
                                    'draft'   => 'yellow',
                                    'removed' => 'red',
                                    default   => 'zinc',
                                }">{{ ucfirst($j->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <a href="{{ route('backend.jobPostDetail', $j->id) }}" target="_blank"
                                   class="text-xs text-[#506070] hover:text-[#dde6f0] px-2 py-1 rounded-lg border border-[#1c2e45] hover:border-[#254060] transition-all">
                                    View
                                </a>
                                @if($j->status !== 'removed')
                                    <flux:button variant="ghost" size="sm"
                                        wire:click="removeJob({{ $j->id }})"
                                        wire:confirm="Remove this job post?"
                                        class="text-red-400">Remove</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    {{-- ── REVIEWS ────────────────────────────────────────────── --}}
    @if($tab === 'reviews')
        <flux:table :paginate="$this->recentReviews">
            <flux:table.columns>
                <flux:table.column>Reviewer → Reviewee</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Review</flux:table.column>
                <flux:table.column>Verified</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->recentReviews as $r)
                    <flux:table.row :key="$r->id" wire:key="r-{{ $r->id }}">
                        <flux:table.cell>
                            <p class="text-sm text-[#dde6f0]">{{ $r->reviewer?->name ?? 'Anon' }}</p>
                            <p class="text-xs text-[#506070]">→ {{ $r->reviewee?->name }}</p>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg width="11" height="11" viewBox="0 0 24 24"
                                         fill="{{ $i <= $r->rating ? '#f59e0b' : '#1c2e45' }}">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                @endfor
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <p class="text-xs text-[#8da0b8] max-w-xs truncate">{{ $r->body ?: '—' }}</p>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$r->is_verified ? 'green' : 'zinc'">
                                {{ $r->is_verified ? 'Verified' : 'Unverified' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm"
                                wire:click="removeReview({{ $r->id }})"
                                wire:confirm="Delete this review? This will recalculate the user's rating."
                                class="text-red-400">Remove</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

    {{-- ── PROFILES ────────────────────────────────────────────── --}}
    @if($tab === 'profiles')
        <flux:table :paginate="$this->profiles">
            <flux:table.columns>
                <flux:table.column>Freelancer</flux:table.column>
                <flux:table.column>Category</flux:table.column>
                <flux:table.column>Rate</flux:table.column>
                <flux:table.column>Rating</flux:table.column>
                <flux:table.column>Verified</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->profiles as $sp)
                    <flux:table.row :key="$sp->id" wire:key="sp-{{ $sp->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:avatar name="{{ $sp->user->name }}" size="xs"/>
                                <div>
                                    <p class="text-sm text-white">{{ $sp->user->name }}</p>
                                    <p class="text-xs text-[#506070]">{{ $sp->user->email }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs text-[#8da0b8]">{{ ucfirst(str_replace('_',' ',$sp->profession_category)) }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="font-mono text-sm text-[#7EE8A2]">
                                @if($sp->hourly_rate) ${{ number_format($sp->hourly_rate) }}/hr @else — @endif
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm font-mono text-[#fbbf24]">
                                {{ number_format($sp->avg_rating,1) }}
                                <span class="text-[#506070]">({{ $sp->total_reviews }})</span>
                            </span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$sp->is_verified ? 'green' : 'zinc'">
                                {{ $sp->is_verified ? 'Verified' : 'Unverified' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                @if(! $sp->is_verified)
                                    <flux:button variant="ghost" size="sm" icon="check-badge"
                                        wire:click="featureProfile({{ $sp->user_id }})"
                                        class="text-[#7EE8A2]">Verify</flux:button>
                                @endif
                                <flux:button variant="ghost" size="sm"
                                    wire:click="suspendProfile({{ $sp->user_id }})"
                                    wire:confirm="Disable this freelancer's marketplace profile?"
                                    class="text-red-400">Disable</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif

</div>
