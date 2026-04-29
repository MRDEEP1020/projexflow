<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\JobPost;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('My Jobs & Applications')]
class MyJobs extends Component
{
    public string $mode = 'client';  // client | freelancer — auto-detected
    public string $filter = 'all';   // job status filter (client) or app status filter (freelancer)
    public bool $showDeleteModal = false;
    public ?int $deleteJobId = null;
    public bool $showWithdrawModal = false;
    public ?int $withdrawAppId = null;

    public function mount(): void
    {
        // Auto-detect mode based on user activity
        $hasPosts = JobPost::where('client_id', Auth::id())->exists();
        $hasApps = JobApplication::where('freelancer_id', Auth::id())->exists();

        // If URL has mode parameter, use that
        if (request()->has('mode') && in_array(request()->get('mode'), ['client', 'freelancer'])) {
            $this->mode = request()->get('mode');
        } elseif (!$hasPosts && $hasApps) {
            $this->mode = 'freelancer';
        }
    }

    public function switchMode(string $mode): void
    {
        $this->mode = $mode;
        $this->filter = 'all';
    }

    public function confirmDelete(int $jobId): void
    {
        $job = JobPost::where('id', $jobId)
            ->where('client_id', Auth::id())
            ->where('status', 'draft')
            ->first();

        if ($job) {
            $this->deleteJobId = $jobId;
            $this->showDeleteModal = true;
        }
    }

    public function delete(): void
    {
        if ($this->deleteJobId) {
            JobPost::where('id', $this->deleteJobId)
                ->where('client_id', Auth::id())
                ->where('status', 'draft')
                ->delete();

            $this->dispatch('toast', ['message' => 'Draft deleted successfully.', 'type' => 'success']);
        }

        $this->showDeleteModal = false;
        $this->deleteJobId = null;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deleteJobId = null;
    }

    public function confirmWithdraw(int $appId): void
    {
        $application = JobApplication::where('id', $appId)
            ->where('freelancer_id', Auth::id())
            ->whereIn('status', ['pending'])
            ->first();

        if ($application) {
            $this->withdrawAppId = $appId;
            $this->showWithdrawModal = true;
        }
    }

    public function withdraw(): void
    {
        if ($this->withdrawAppId) {
            JobApplication::where('id', $this->withdrawAppId)
                ->where('freelancer_id', Auth::id())
                ->whereIn('status', ['pending'])
                ->delete();

            $this->dispatch('toast', ['message' => 'Application withdrawn successfully.', 'type' => 'success']);
        }

        $this->showWithdrawModal = false;
        $this->withdrawAppId = null;
    }

    public function cancelWithdraw(): void
    {
        $this->showWithdrawModal = false;
        $this->withdrawAppId = null;
    }

    #[Computed]
    public function myJobs()
    {
        $query = JobPost::where('client_id', Auth::id());

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query->withCount('applications')
            ->with(['hiredFreelancer'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function myApplications()
    {
        $query = JobApplication::where('freelancer_id', Auth::id());

        if ($this->filter !== 'all') {
            $query->where('status', $this->filter);
        }

        return $query->with(['jobPost' => function($q) {
            $q->with('client');
        }])->latest()
        ->get();
    }

    #[Computed]
    public function jobCounts(): array
    {
        $base = JobPost::where('client_id', Auth::id());
        return [
            'all' => (clone $base)->count(),
            'open' => (clone $base)->where('status', 'open')->count(),
            'draft' => (clone $base)->where('status', 'draft')->count(),
            'filled' => (clone $base)->where('status', 'filled')->count(),
            'closed' => (clone $base)->where('status', 'closed')->count(),
        ];
    }

    #[Computed]
    public function appCounts(): array
    {
        $base = JobApplication::where('freelancer_id', Auth::id());
        return [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'shortlisted' => (clone $base)->where('status', 'shortlisted')->count(),
            'hired' => (clone $base)->where('status', 'hired')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.backend.myJobs');
    }
}