<?php
// ══════════════════════════════════════════════════════════════════
// app/Livewire/Backend/AdminModeration.php
// ══════════════════════════════════════════════════════════════════

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\JobPost;
use App\Models\Review;
use App\Models\ServiceProfile;

#[Layout('components.layouts.admin')]
#[Title('Moderation')]
class AdminModeration extends Component
{
    use WithPagination;

    public string $tab = 'jobs'; // jobs | reviews | profiles

    // ── Job post moderation ────────────────────────────────────
    public function removeJob(int $id): void
    {
        JobPost::findOrFail($id)->update(['status' => 'removed']);
        $this->dispatch('toast', ['message' => 'Job post removed.', 'type' => 'info']);
    }

    public function approveJob(int $id): void
    {
        JobPost::findOrFail($id)->update(['status' => 'open']);
        $this->dispatch('toast', ['message' => 'Job post approved.', 'type' => 'success']);
    }

    // ── Review moderation ──────────────────────────────────────
    public function removeReview(int $id): void
    {
        $review = Review::findOrFail($id);
        $revieweeId = $review->reviewee_id;
        $review->delete();

        // Recalculate avg
        $stats = Review::where('reviewee_id', $revieweeId)
            ->selectRaw('AVG(rating) as avg, COUNT(*) as total')
            ->first();
        ServiceProfile::where('user_id', $revieweeId)->update([
            'avg_rating'    => round($stats->avg ?? 0, 2),
            'total_reviews' => $stats->total ?? 0,
        ]);

        $this->dispatch('toast', ['message' => 'Review removed.', 'type' => 'info']);
    }

    // ── Profile moderation ─────────────────────────────────────
    public function suspendProfile(int $userId): void
    {
        ServiceProfile::where('user_id', $userId)->update([
            'availability_status' => 'not_available',
            'is_verified' => false,
        ]);
        \App\Models\User::where('id', $userId)->update(['is_marketplace_enabled' => false]);
        $this->dispatch('toast', ['message' => 'Marketplace profile disabled.', 'type' => 'info']);
    }

    public function featureProfile(int $userId): void
    {
        ServiceProfile::where('user_id', $userId)->update(['is_verified' => true]);
        $this->dispatch('toast', ['message' => 'Profile verified/featured.', 'type' => 'success']);
    }

    // ── Computed ───────────────────────────────────────────────
    #[Computed]
    public function pendingJobs()
    {
        return JobPost::where('status', 'draft')
            ->orWhere('status', 'open')
            ->with('client')
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function recentReviews()
    {
        return Review::with(['reviewer','reviewee'])
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function profiles()
    {
        return ServiceProfile::with('user')
            ->orderByDesc('total_reviews')
            ->paginate(20);
    }

    public function render() { return view('livewire.backend.adminModeration'); }
}
