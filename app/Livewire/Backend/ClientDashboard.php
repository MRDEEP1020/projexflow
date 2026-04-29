<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\Project;
use App\Models\Task;
use App\Models\Contract;
use App\Models\Booking;
use App\Models\JobPost;
use App\Models\PaymentTransaction;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Layout('components.layouts.app')]
#[Title('Client Dashboard')]
class ClientDashboard extends Component
{
    protected function getListeners(): array
    {
        return [
            'echo-private:user.' . Auth::id() . ',.notification' => '$refresh',
        ];
    }

    // ── Computed: projects I own ───────────────────────────────
#[Computed]
public function myProjects()
{
    // Get the authenticated user
    $user = Auth::user();
    
    // Get the current organization from the user's current_org_id relationship
    // or from session with fallback to user's first org
    $currentOrg = $user->currentOrg;
    
    if (!$currentOrg) {
        return collect(); // Return empty collection if no org is selected
    }
    
    // Now scope by the org_id and verify user belongs to this org
    return Project::where('org_id', $currentOrg->id)
        ->whereHas('org.users', function($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->whereNull('archived_at')
        ->whereIn('status', ['active', 'planning', 'on_hold'])
        ->withCount(['tasks', 'tasks as done_tasks_count' => fn($q) => $q->where('status','done')])
        ->with(['projectMembers.user'])
        ->orderByDesc('updated_at')
        ->limit(5)
        ->get()
        ->map(function ($p) {
            $p->progress = $p->tasks_count > 0
                ? round(($p->done_tasks_count / $p->tasks_count) * 100)
                : 0;
            return $p;
        });
}

    // ── Computed: tasks assigned to ME (as PM) ─────────────────
    #[Computed]
    public function urgentTasks()
    {
        return Task::where('assigned_to', Auth::id())
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(3))
            ->with('project:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    // ── Computed: active contracts where I am the CLIENT ────────
    #[Computed]
    public function activeContracts()
    {
        return Contract::where('client_id', Auth::id())
            ->whereIn('status', ['active', 'submitted', 'draft'])
            ->with('freelancer')
            ->latest()
            ->limit(5)
            ->get();
    }

    // ── Computed: my open job posts ─────────────────────────────
    #[Computed]
    public function openJobs()
    {
        return JobPost::where('client_id', Auth::id())
            ->where('status', 'open')
            ->withCount('applications')
            ->latest()
            ->limit(4)
            ->get();
    }

    // ── Computed: upcoming bookings I made ──────────────────────
    #[Computed]
    public function upcomingBookings()
    {
        return Booking::where('client_id', Auth::id())
            ->where('status', 'confirmed')
            ->where('start_at', '>=', now())
            ->with('provider')
            ->orderBy('start_at')
            ->limit(3)
            ->get();
    }

    // ── Computed: spending stats ────────────────────────────────
    #[Computed]
    public function spending(): array
    {
        $thisMonth = PaymentTransaction::where('payer_id', Auth::id())
            ->where('type', '!=', 'platform_fee')
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $totalSpent = PaymentTransaction::where('payer_id', Auth::id())
            ->where('status', 'completed')
            ->sum('amount');

        $activeContractValue = Contract::where('client_id', Auth::id())
            ->whereIn('status', ['active','submitted'])
            ->sum('total_amount');

        return [
            'this_month'     => $thisMonth,
            'total'          => $totalSpent,
            'active_escrow'  => $activeContractValue,
            'open_contracts' => Contract::where('client_id', Auth::id())->whereIn('status',['active','submitted'])->count(),
        ];
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
        return view('livewire.backend.clientDashboard');
    }
}
