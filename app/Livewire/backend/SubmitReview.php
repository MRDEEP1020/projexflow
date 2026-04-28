<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Review;
use App\Models\ServiceProfile;
use App\Models\Notification;
use App\Models\Booking;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Leave a Review')]
class SubmitReview extends Component
{
    public int     $revieweeId  = 0;
    public ?int    $bookingId   = null;
    public ?int    $projectId   = null;
    public int     $rating      = 0;
    public string  $body        = '';
    public bool    $submitted   = false;

    // Hover state for star display
    public int $hoverRating = 0;

    public function mount(int $reviewee, ?int $booking = null, ?int $project = null): void
    {
        $this->revieweeId = $reviewee;
        $this->bookingId  = $booking;
        $this->projectId  = $project;

        // Auth check: must have a real relationship with the reviewee
        $hasBooking = $booking && Booking::where('id', $booking)
            ->where('client_id', Auth::id())
            ->where('status', 'confirmed')
            ->exists();

        $hasProject = $project && \App\Models\ProjectMember::where('project_id', $project)
            ->where('user_id', Auth::id())
            ->exists();

        abort_unless($hasBooking || $hasProject, 403, 'You can only review someone you have worked with on the platform.');

        // Check for existing review (prevent duplicates)
        $existing = Review::where('reviewer_id', Auth::id())
            ->where('reviewee_id', $reviewee)
            ->when($booking, fn($q) => $q->where('booking_id', $booking))
            ->when($project, fn($q) => $q->where('project_id', $project))
            ->exists();

        if ($existing) {
            $this->submitted = true;
        }
    }

    public function setRating(int $r): void
    {
        $this->rating = $r;
    }

    public function submit(): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body'   => ['nullable', 'string', 'max:2000'],
        ]);

        // ALG-REV-01 Step 5: is_verified = true when backed by real booking or project
        $isVerified = $this->bookingId !== null || $this->projectId !== null;

        $review = Review::create([
            'reviewer_id'  => Auth::id(),
            'reviewee_id'  => $this->revieweeId,
            'booking_id'   => $this->bookingId,
            'project_id'   => $this->projectId,
            'rating'       => $this->rating,
            'body'         => $this->body ?: null,
            'is_verified'  => $isVerified,
        ]);

        // ALG-REV-02: Recalculate avg_rating (triggered by model observer in production;
        // we call it directly here for safety)
        $this->recalculateRating($this->revieweeId);

        // Notify reviewee
        Notification::create([
            'user_id' => $this->revieweeId,
            'type'    => 'new_review',
            'title'   => Auth::user()->name . ' left you a ' . $this->rating . '-star review',
            'body'    => $this->body ? substr($this->body, 0, 100) : '',
            'url'     => route('backend.profilePage', \App\Models\User::find($this->revieweeId)?->name ?? ''),
        ]);

        $this->submitted = true;
        $this->dispatch('toast', ['message' => 'Review submitted. Thank you!', 'type' => 'success']);
    }

    // ALG-REV-02
    protected function recalculateRating(int $userId): void
    {
        $stats = Review::where('reviewee_id', $userId)
            ->selectRaw('AVG(rating) as avg, COUNT(*) as total')
            ->first();

        ServiceProfile::where('user_id', $userId)->update([
            'avg_rating'    => round($stats->avg ?? 0, 2),
            'total_reviews' => $stats->total ?? 0,
        ]);
    }

    public function render()
    {
        return view('livewire.backend.submitReview', [
            'reviewee' => \App\Models\User::with('serviceProfile')->find($this->revieweeId),
        ]);
    }
}
