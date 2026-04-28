<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\JobPost;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Find Jobs')]
class JobBoard extends Component
{
    use WithPagination;

    public string  $search       = '';
    public string  $category     = 'all';
    public string  $type         = 'all';      // fixed | hourly
    public string  $experience   = 'all';
    public int     $minBudget    = 0;
    public string  $duration     = 'all';
    public string  $sortBy       = 'newest';   // newest | budget_high | budget_low

    // Apply modal state
    public ?int    $applyJobId   = null;
    public string  $coverLetter  = '';
    public ?float  $proposedRate = null;
    public string  $availability = '';
    public bool    $applied      = false;
    public bool $showApplyModal = false;


    public function updatingSearch(): void
    {
        $this->resetPage();
    }
    public function updatingCategory(): void
    {
        $this->resetPage();
    }
    public function updatingType(): void
    {
        $this->resetPage();
    }
    public function updatingExperience(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'type', 'experience', 'minBudget', 'duration']);
        $this->resetPage();
    }

    // ── Apply flow ────────────────────────────────────────────
    public function openApply(int $jobId): void
    {
        $this->applyJobId    = $jobId;
        $this->coverLetter   = '';
        $this->proposedRate  = null;
        $this->availability  = '';
        $this->applied       = false;
        $this->showApplyModal = true;  // ← open AFTER setting the ID

    }

    public function submitApplication(): void
    {
        $job = JobPost::where('id', $this->applyJobId)
            ->where('status', 'open')
            ->firstOrFail();

        // Prevent duplicate applications
        $exists = JobApplication::where('job_post_id', $job->id)
            ->where('freelancer_id', Auth::id())
            ->exists();

        if ($exists) {
            $this->dispatch('toast', ['message' => 'You already applied to this job.', 'type' => 'error']);
            return;
        }

        // Check max applicants not exceeded
        $count = JobApplication::where('job_post_id', $job->id)->count();
        if ($count >= $job->max_applicants) {
            $this->addError('applyJobId', 'This job has reached its maximum number of applicants.');
            return;
        }

        $this->validate([
            'coverLetter'  => ['required', 'string', 'min:50', 'max:3000'],
            'proposedRate' => ['nullable', 'numeric', 'min:1'],
            'availability' => ['required', 'string'],
        ]);

        JobApplication::create([
            'job_post_id'   => $job->id,
            'freelancer_id' => Auth::id(),
            'cover_letter'  => $this->coverLetter,
            'proposed_rate' => $this->proposedRate,
            'availability'  => $this->availability,
            'status'        => 'pending',
        ]);

        // Notify client
        \App\Models\Notification::create([
            'user_id' => $job->client_id,
            'type'    => 'job_application',
            'title'   => Auth::user()->name . ' applied to: ' . $job->title,
            'body'    => substr($this->coverLetter, 0, 120),
            'url'     => route('backend.jobPostDetail', $job->id),
        ]);

        // If auto-close when max reached
        $newCount = JobApplication::where('job_post_id', $job->id)->count();
        if ($newCount >= $job->max_applicants) {
            $job->update(['status' => 'closed']);
        }

        $this->applied = true;
        $this->dispatch('toast', ['message' => 'Application submitted!', 'type' => 'success']);
    }

    public function closeApply(): void
    {
        $this->applyJobId = null;
        $this->applied = false;
    }

    // ── Computed ──────────────────────────────────────────────
    #[Computed]
    public function jobs()
    {
        return JobPost::where('status', 'open')
            ->where('visibility', 'public')
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhereJsonContains('skills_required', $this->search);
            }))
            ->when($this->category !== 'all', fn($q) => $q->where('category', $this->category))
            ->when($this->type !== 'all',     fn($q) => $q->where('type', $this->type))
            ->when($this->experience !== 'all', fn($q) => $q->where('experience_level', $this->experience))
            ->when($this->minBudget > 0,       fn($q) => $q->where('budget_max', '>=', $this->minBudget))
            ->when($this->duration !== 'all',  fn($q) => $q->where('duration', $this->duration))
            ->withCount('applications')
            ->with('client')
            ->orderByRaw(match ($this->sortBy) {
                'budget_high' => 'budget_max DESC NULLS LAST',
                'budget_low'  => 'budget_min ASC NULLS LAST',
                default       => 'created_at DESC',
            })
            ->paginate(15);
    }

    #[Computed]
    public function appliedJobIds(): array
    {
        return JobApplication::where('freelancer_id', Auth::id())
            ->pluck('job_post_id')
            ->toArray();
    }

    #[Computed]
    public function openJob(): ?JobPost
    {
        if (! $this->applyJobId) return null;
        return JobPost::with('client.serviceProfile')->find($this->applyJobId);
    }

    public function render()
    {
        return view('livewire.backend.jobBoard');
    }
}
