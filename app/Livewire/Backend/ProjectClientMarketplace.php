<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\ServiceProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CLIENT-FACING marketplace interface.
 *
 * Completely separate from MarketplaceBrowse (which is the freelancer's
 * own profile management page). This page is where CLIENTS come to find,
 * evaluate, and contact freelancers. The UX mirrors how Upwork's client
 * side works: search → view profile → shortlist → contact/book/contract.
 */
#[Layout('components.layouts.app')]
#[Title('Find Freelancers')]
class ProjectClientMarketplace extends Component
{
    use WithPagination;

    // ── Search & filters ───────────────────────────────────────
    public string $search       = '';
    public string $category     = 'all';
    public string $availability = 'all';
    public string $experience   = 'all';
    public int    $maxRate      = 0;
    public string $language     = 'all';
    public string $sortBy       = 'rank';

    // ── Shortlist state ────────────────────────────────────────
    // Stored in session — clients can shortlist freelancers before deciding
    public array $shortlisted   = [];

    // ── Quick view panel ───────────────────────────────────────
    public ?int  $quickViewId   = null;

    public function mount(): void
    {
        // Switch user's marketplace mode to 'client'
        if (Auth::user()->marketplace_mode !== 'client') {
            // Don't force override if they're in 'both' mode
        }

        // Load existing shortlist from session
        $this->shortlisted = session('client_shortlist', []);
    }

    public function updatingSearch():   void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search','category','availability','experience','maxRate','language']);
        $this->resetPage();
    }

    // ── Shortlist actions ──────────────────────────────────────
    public function shortlist(int $userId): void
    {
        if (! in_array($userId, $this->shortlisted)) {
            $this->shortlisted[] = $userId;
            session(['client_shortlist' => $this->shortlisted]);
            $this->dispatch('toast', ['message' => 'Added to shortlist.', 'type' => 'success']);
        } else {
            $this->shortlisted = array_values(array_filter($this->shortlisted, fn($id) => $id !== $userId));
            session(['client_shortlist' => $this->shortlisted]);
            $this->dispatch('toast', ['message' => 'Removed from shortlist.', 'type' => 'info']);
        }
    }

    public function openQuickView(int $userId): void
    {
        $this->quickViewId = $userId;
    }

    public function closeQuickView(): void
    {
        $this->quickViewId = null;
    }

    // ── Computed: ALG-ARCH-02 ranked freelancers ───────────────
    #[Computed]
    public function freelancers()
    {
        $q = ServiceProfile::query()
            ->join('users', 'service_profiles.user_id', '=', 'users.id')
            ->where('users.is_marketplace_enabled', true)
            ->with('user')
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('headline', 'like', '%'.$this->search.'%')
                  ->orWhere('bio', 'like', '%'.$this->search.'%')
                  ->orWhere('users.name', 'like', '%'.$this->search.'%')
                  ->orWhereJsonContains('skills', $this->search);
            }))
            ->when($this->category !== 'all', fn($q) => $q->where('profession_category', $this->category))
            ->when($this->availability !== 'all', fn($q) => $q->where('availability_status', $this->availability))
            ->when($this->experience !== 'all', fn($q) => $q->where(
                DB::raw('CASE
                    WHEN years_experience < 2 THEN "entry"
                    WHEN years_experience < 5 THEN "mid"
                    WHEN years_experience < 10 THEN "senior"
                    ELSE "expert" END'),
                $this->experience
            ))
            ->when($this->maxRate > 0, fn($q) => $q->where('hourly_rate', '<=', $this->maxRate))
            ->when($this->language !== 'all', fn($q) => $q->whereJsonContains('languages', $this->language))
            ->selectRaw('service_profiles.*,
                (avg_rating*20
                + IF(is_verified=1,10,0)
                + LEAST(total_reviews*0.5,15)
                + CASE availability_status
                    WHEN "open_to_work" THEN 5
                    WHEN "not_available" THEN -20
                    ELSE 0 END
                + CASE WHEN response_time_hours<=2 THEN 5
                       WHEN response_time_hours<=24 THEN 2
                       ELSE 0 END
                ) AS ranking_score')
            ->orderByRaw(match($this->sortBy) {
                'rating'    => 'avg_rating DESC, total_reviews DESC',
                'rate_asc'  => 'hourly_rate ASC',
                'rate_desc' => 'hourly_rate DESC',
                'newest'    => 'service_profiles.created_at DESC',
                default     => 'ranking_score DESC',
            });

        return $q->paginate(12);
    }

    #[Computed]
    public function shortlistedProfiles()
    {
        if (empty($this->shortlisted)) return collect();
        return ServiceProfile::whereIn('user_id', $this->shortlisted)->with('user')->get();
    }

    #[Computed]
    public function quickViewProfile(): ?ServiceProfile
    {
        if (! $this->quickViewId) return null;
        return ServiceProfile::with([
            'user',
            'user.portfolioItems' => fn($q) => $q->where('is_public', true)->orderByDesc('is_featured')->limit(4),
            'user.reviews' => fn($q) => $q->latest()->limit(3),
        ])->where('user_id', $this->quickViewId)->first();
    }

    #[Computed]
    public function categoryStats(): array
    {
        return ServiceProfile::join('users','service_profiles.user_id','=','users.id')
            ->where('users.is_marketplace_enabled', true)
            ->where('availability_status', 'open_to_work')
            ->groupBy('profession_category')
            ->selectRaw('profession_category, COUNT(*) as count')
            ->pluck('count', 'profession_category')
            ->toArray();
    }

    public function render()
    {
        return view('livewire.backend.projectclientMarketplace');
    }
}