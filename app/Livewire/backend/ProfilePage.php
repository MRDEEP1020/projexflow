<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\ServiceProfile;
use App\Models\Review;
use App\Models\PortfolioItem;
use Illuminate\Support\Facades\Auth;

#[Layout('layouts.app')]
#[Title('Profile')]
class ProfilePage extends Component
{
    public User          $profileUser;
    public bool          $isOwn = false;

    public function mount(string $username): void
    {
        $this->profileUser = User::where('name', $username)
            ->orWhere('slug', $username)
            ->firstOrFail();

        abort_unless($this->profileUser->is_marketplace_enabled, 404);

        $this->isOwn = Auth::id() === $this->profileUser->id;
    }

    #[Computed]
    public function profile(): ?ServiceProfile
    {
        return ServiceProfile::where('user_id', $this->profileUser->id)->first();
    }

    #[Computed]
    public function portfolio()
    {
        return PortfolioItem::where('user_id', $this->profileUser->id)
            ->where('is_public', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function reviews()
    {
        return Review::where('reviewee_id', $this->profileUser->id)
            ->with('reviewer')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function ratingBreakdown(): array
    {
        $dist = [];
        for ($i = 5; $i >= 1; $i--) {
            $count   = Review::where('reviewee_id', $this->profileUser->id)->where('rating', $i)->count();
            $total   = max(1, $this->profile?->total_reviews ?? 1);
            $dist[$i] = ['count' => $count, 'pct' => round(($count / $total) * 100)];
        }
        return $dist;
    }

    public function render()
    {
        return view('livewire.backend.profilePage');
    }
}
