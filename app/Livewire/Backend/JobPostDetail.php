<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\JobPost;
use App\Models\JobApplication;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Job Details')]
class JobPostDetail extends Component
{
    public JobPost $job;
    public bool $isOwner    = false;
    public bool $hasApplied = false;

    // Client: applicant management
    public string $appFilter   = 'all'; // all | pending | shortlisted | rejected
    public ?int   $viewAppId   = null;

    public function mount(int $id): void
    {
        $this->job = JobPost::with(['client', 'applications.freelancer.serviceProfile'])->findOrFail($id);
        $this->isOwner = $this->job->client_id === Auth::id();

        if (! $this->isOwner) {
            // Check visibility
            abort_if(
                $this->job->visibility === 'invite_only' &&
                $this->job->status !== 'open',
                404
            );

            $this->hasApplied = JobApplication::where('job_post_id', $this->job->id)
                ->where('freelancer_id', Auth::id())
                ->exists();
        }
    }

    // ── Client actions ────────────────────────────────────────
    public function shortlist(int $appId): void
    {
        $this->authorizeOwner();
        JobApplication::where('id', $appId)
            ->where('job_post_id', $this->job->id)
            ->update(['status' => 'shortlisted']);

        $app = JobApplication::with('freelancer')->find($appId);
        Notification::create([
            'user_id' => $app->freelancer_id,
            'type'    => 'application_shortlisted',
            'title'   => 'You\'ve been shortlisted!',
            'body'    => 'The client shortlisted you for: ' . $this->job->title,
            'url'     => route('backend.myApplications'),
        ]);
        $this->dispatch('toast', ['message' => 'Applicant shortlisted.', 'type' => 'success']);
    }

    public function reject(int $appId): void
    {
        $this->authorizeOwner();
        JobApplication::where('id', $appId)
            ->where('job_post_id', $this->job->id)
            ->update(['status' => 'rejected']);

        $app = JobApplication::find($appId);
        Notification::create([
            'user_id' => $app->freelancer_id,
            'type'    => 'application_rejected',
            'title'   => 'Application update',
            'body'    => 'Your application for "' . $this->job->title . '" was not selected.',
            'url'     => route('backend.myApplications'),
        ]);
        $this->dispatch('toast', ['message' => 'Applicant rejected.', 'type' => 'info']);
    }

    public function hire(int $appId): void
    {
        $this->authorizeOwner();
        $app = JobApplication::with('freelancer')->findOrFail($appId);

        // Mark job as filled
        $this->job->update(['status' => 'filled', 'hired_freelancer_id' => $app->freelancer_id]);
        $app->update(['status' => 'hired']);

        // Notify hired freelancer
        Notification::create([
            'user_id' => $app->freelancer_id,
            'type'    => 'job_hired',
            'title'   => '🎉 You got hired!',
            'body'    => 'You\'ve been selected for: ' . $this->job->title,
            'url'     => route('backend.myApplications'),
        ]);

        $this->dispatch('toast', ['message' => $app->freelancer->name . ' hired!', 'type' => 'success']);
        $this->redirect(route('backend.jobPostCreate'), navigate: true);
    }

    public function closeJob(): void
    {
        $this->authorizeOwner();
        $this->job->update(['status' => 'closed']);
        $this->dispatch('toast', ['message' => 'Job closed.', 'type' => 'info']);
    }

    public function reopenJob(): void
    {
        $this->authorizeOwner();
        $this->job->update(['status' => 'open']);
        $this->dispatch('toast', ['message' => 'Job reopened.', 'type' => 'success']);
    }

    protected function authorizeOwner(): void
    {
        abort_unless($this->job->client_id === Auth::id(), 403);
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function applications()
    {
        return JobApplication::where('job_post_id', $this->job->id)
            ->when($this->appFilter !== 'all', fn($q) => $q->where('status', $this->appFilter))
            ->with(['freelancer.serviceProfile'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function appCounts(): array
    {
        $base = JobApplication::where('job_post_id', $this->job->id);
        return [
            'all'         => (clone $base)->count(),
            'pending'     => (clone $base)->where('status','pending')->count(),
            'shortlisted' => (clone $base)->where('status','shortlisted')->count(),
            'rejected'    => (clone $base)->where('status','rejected')->count(),
            'hired'       => (clone $base)->where('status','hired')->count(),
        ];
    }

    #[Computed]
    public function viewApp(): ?JobApplication
    {
        if (! $this->viewAppId) return null;
        return JobApplication::with(['freelancer.serviceProfile','freelancer.portfolioItems'])
            ->find($this->viewAppId);
    }

    public function render()
    {
        return view('livewire.backend.jobPostDetail');
    }
}
