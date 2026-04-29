<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Task;
use App\Models\Contract;
use App\Models\Booking;
use App\Models\JobApplication;
use App\Models\Wallet;
use App\Models\Notification;
use App\Models\ServiceProfile;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Freelancer Dashboard')]
class FreelancerDashboard extends Component
{
    // ── Computed: my tasks across all projects ─────────────────
    #[Computed]
    public function myTasks()
    {
        return Task::where('assigned_to', Auth::id())
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(6)
            ->get();
    }

    // ── Computed: active contracts where I am the FREELANCER ───
    #[Computed]
    public function activeContracts()
    {
        return Contract::where('freelancer_id', Auth::id())
            ->whereIn('status', ['active', 'submitted'])
            ->with('client')
            ->latest()
            ->limit(4)
            ->get();
    }

    // ── Computed: my job applications ──────────────────────────
    #[Computed]
    public function myApplications()
    {
        return JobApplication::where('freelancer_id', Auth::id())
            ->whereIn('status', ['pending', 'shortlisted'])
            ->with('jobPost.client')
            ->latest()
            ->limit(4)
            ->get();
    }

    // ── Computed: today's bookings ──────────────────────────────
    #[Computed]
    public function todayBookings()
    {
        return Booking::where('provider_id', Auth::id())
            ->where('status', 'confirmed')
            ->whereDate('start_at', today())
            ->orderBy('start_at')
            ->get();
    }

    // ── Computed: upcoming bookings ────────────────────────────
    #[Computed]
    public function upcomingBookings()
    {
        return Booking::where('provider_id', Auth::id())
            ->where('status', 'confirmed')
            ->where('start_at', '>', now())
            ->orderBy('start_at')
            ->limit(3)
            ->get();
    }

    // ── Computed: wallet + earnings ────────────────────────────
    #[Computed]
    public function earnings(): array
    {
        $wallet = Wallet::where('user_id', Auth::id())->first();

        $thisMonth = \App\Models\PaymentTransaction::where('payee_id', Auth::id())
            ->where('type', 'milestone_release')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $pendingPayout = Contract::where('freelancer_id', Auth::id())
            ->where('status', 'submitted')
            ->sum('total_amount');

        return [
            'available'    => $wallet?->available_balance ?? 0,
            'held'         => $wallet?->held_balance ?? 0,
            'total_earned' => $wallet?->total_earned ?? 0,
            'this_month'   => $thisMonth,
            'pending'      => $pendingPayout,
        ];
    }

    // ── Computed: profile health ───────────────────────────────
    #[Computed]
    public function profileHealth(): array
    {
        $sp = ServiceProfile::where('user_id', Auth::id())->first();
        $user = Auth::user();

        $checks = [
            'headline'    => (bool)($sp?->headline),
            'bio'         => (bool)($sp?->bio),
            'skills'      => !empty($sp?->skills),
            'rate'        => (bool)($sp?->hourly_rate),
            'avatar'      => (bool)($user->avatar_url),
            'portfolio'   => \App\Models\PortfolioItem::where('user_id', Auth::id())->exists(),
        ];

        $score = count(array_filter($checks)) / count($checks) * 100;

        return [
            'score'    => round($score),
            'checks'   => $checks,
            'rating'   => $sp?->avg_rating ?? 0,
            'reviews'  => $sp?->total_reviews ?? 0,
            'available'=> $sp?->availability_status,
        ];
    }

    // ── Computed: recent reviews ───────────────────────────────
    #[Computed]
    public function recentReviews()
    {
        return Review::where('reviewee_id', Auth::id())
            ->with('reviewer')
            ->latest()
            ->limit(3)
            ->get();
    }

    // ── Computed: unread notifications ──────────────────────────
    #[Computed]
    public function recentNotifications()
    {
        return Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.backend.freelancerDashboard');
    }
}
