<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\ServiceProfile;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
#[Title('Marketplace')]
class MarketplaceBrowse extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $category     = 'all';
    public string $availability = 'all';
    public int    $minRating    = 0;
    public int    $maxRate      = 0;
    public string $language     = 'all';
    public string $sortBy       = 'rank';

    public function updatingSearch():       void { $this->resetPage(); }
    public function updatingCategory():     void { $this->resetPage(); }
    public function updatingAvailability(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search','category','availability','minRating','maxRate','language']);
        $this->resetPage();
    }

    #[Computed]
    public function profiles()
    {
        $q = ServiceProfile::query()
            ->join('users', 'service_profiles.user_id', '=', 'users.id')
            ->where('users.is_marketplace_enabled', true)
            ->with('user')
            ->when($this->category !== 'all', fn($q) => $q->where('profession_category', $this->category))
            ->when($this->minRating > 0,      fn($q) => $q->where('avg_rating', '>=', $this->minRating))
            ->when($this->maxRate  > 0,        fn($q) => $q->where('hourly_rate', '<=', $this->maxRate))
            ->when($this->availability !== 'all', fn($q) => $q->where('availability_status', $this->availability))
            ->when($this->language !== 'all',  fn($q) => $q->whereJsonContains('languages', $this->language))
            ->when($this->search, fn($q) => $q->where(function($q) {
                $q->where('headline','like','%'.$this->search.'%')
                  ->orWhere('bio','like','%'.$this->search.'%')
                  ->orWhereJsonContains('skills', $this->search);
            }));

        // ALG-ARCH-02 ranking score
        $q->selectRaw('service_profiles.*,
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
            ) AS ranking_score');

        $q->orderByRaw(match($this->sortBy) {
            'rating'    => 'avg_rating DESC, total_reviews DESC',
            'rate_asc'  => 'hourly_rate ASC',
            'rate_desc' => 'hourly_rate DESC',
            'reviews'   => 'total_reviews DESC',
            default     => 'ranking_score DESC, total_reviews DESC',
        });

        return $q->paginate(20);
    }

    #[Computed]
    public function featured()
    {
        return ServiceProfile::join('users','service_profiles.user_id','=','users.id')
            ->where('users.is_marketplace_enabled', true)
            ->where('is_verified', true)
            ->where('avg_rating','>=', 4.5)
            ->where('availability_status','open_to_work')
            ->select('service_profiles.*')->with('user')
            ->orderByDesc('avg_rating')->limit(4)->get();
    }

    public function render()
    {
        return view('livewire.backend.marketplaceBrowse');
    }
}
