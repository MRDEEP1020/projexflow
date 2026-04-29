<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\User;
use App\Models\Contract;
use App\Models\PaymentTransaction;
use App\Models\Dispute;
use App\Models\WithdrawalRequest;
use App\Models\JobPost;
use App\Models\JobApplication;
use App\Models\Review;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.admin')]
#[Title('Admin Dashboard')]
class AdminDashboard extends Component
{
    public string $period = '30'; // days

    #[Computed]
    public function kpis(): array
    {
        $since = now()->subDays((int)$this->period);

        $totalRevenue = PaymentTransaction::where('type','platform_fee')
            ->where('status','completed')
            ->where('created_at', '>=', $since)
            ->sum('amount');

        $prevRevenue = PaymentTransaction::where('type','platform_fee')
            ->where('status','completed')
            ->whereBetween('created_at', [
                now()->subDays((int)$this->period * 2),
                now()->subDays((int)$this->period),
            ])
            ->sum('amount');

        $newUsers    = User::where('created_at', '>=', $since)->count();
        $prevUsers   = User::whereBetween('created_at', [now()->subDays((int)$this->period * 2), $since])->count();

        $contracts   = Contract::where('created_at', '>=', $since)->count();
        $gmv         = Contract::where('created_at', '>=', $since)->sum('total_amount');

        $openDisputes  = Dispute::where('status','open')->count();
        $pendingPayouts= WithdrawalRequest::where('status','pending')->count();

        return [
            'revenue'       => $totalRevenue,
            'revenue_delta' => $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0,
            'new_users'     => $newUsers,
            'user_delta'    => $prevUsers > 0 ? round((($newUsers - $prevUsers) / $prevUsers) * 100, 1) : 0,
            'contracts'     => $contracts,
            'gmv'           => $gmv,
            'open_disputes' => $openDisputes,
            'pending_payouts'=> $pendingPayouts,
            'total_users'   => User::count(),
            'total_freelancers' => User::where('is_marketplace_enabled', true)->count(),
            'total_orgs'    => Organization::count(),
            'total_jobs'    => JobPost::count(),
        ];
    }

    #[Computed]
    public function revenueChart(): array
    {
        return PaymentTransaction::where('type','platform_fee')
            ->where('status','completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->orderBy('date')
            ->pluck('total','date')
            ->toArray();
    }

    #[Computed]
    public function recentContracts()
    {
        return Contract::with(['client','freelancer'])
            ->latest()
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function openDisputesList()
    {
        return Dispute::where('status','open')
            ->with(['contract.client','contract.freelancer'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function pendingWithdrawals()
    {
        return WithdrawalRequest::where('status','pending')
            ->with('user')
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function topCategories(): array
    {
        return \App\Models\ServiceProfile::groupBy('profession_category')
            ->selectRaw('profession_category, COUNT(*) as count, AVG(hourly_rate) as avg_rate')
            ->orderByDesc('count')
            ->limit(6)
            ->get()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.backend.adminDashboard');
    }
}