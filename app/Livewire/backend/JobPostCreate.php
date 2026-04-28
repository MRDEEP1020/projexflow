<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\JobPost;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Post a Job')]
class JobPostCreate extends Component
{
    public string  $title           = '';
    public string  $description     = '';
    public string  $category        = '';
    public string  $type            = 'fixed'; // fixed | hourly
    public ?float  $budgetMin       = null;
    public ?float  $budgetMax       = null;
    public string  $currency        = 'USD';
    public string  $experienceLevel = 'mid';  // entry | mid | senior | expert
    public array   $skills          = [];
    public string  $newSkill        = '';
    public string  $duration        = '';     // e.g. "2 weeks", "1 month"
    public ?string $deadline        = null;
    public string  $visibility      = 'public'; // public | invite_only
    public int     $maxApplicants   = 20;

    public function addSkill(): void
    {
        $s = trim($this->newSkill);
        if ($s && !in_array($s, $this->skills) && count($this->skills) < 15) {
            $this->skills[] = $s;
        }
        $this->newSkill = '';
    }

    public function removeSkill(int $i): void
    {
        array_splice($this->skills, $i, 1);
    }

    public function publish(): void
    {
        $this->validate([
            'title'          => ['required', 'string', 'min:10', 'max:200'],
            'description'    => ['required', 'string', 'min:50', 'max:10000'],
            'category'       => ['required', 'string'],
            'type'           => ['required', 'in:fixed,hourly'],
            'budgetMin'      => ['nullable', 'numeric', 'min:1'],
            'budgetMax'      => ['nullable', 'numeric', 'min:1', 'gte:budgetMin'],
            'experienceLevel'=> ['required', 'in:entry,mid,senior,expert'],
            'deadline'       => ['nullable', 'date', 'after:today'],
            'maxApplicants'  => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $job = JobPost::create([
            'client_id'        => Auth::id(),
            'title'            => $this->title,
            'description'      => $this->description,
            'category'         => $this->category,
            'type'             => $this->type,
            'budget_min'       => $this->budgetMin,
            'budget_max'       => $this->budgetMax,
            'currency'         => $this->currency,
            'experience_level' => $this->experienceLevel,
            'skills_required'  => $this->skills,
            'duration'         => $this->duration ?: null,
            'deadline'         => $this->deadline,
            'visibility'       => $this->visibility,
            'max_applicants'   => $this->maxApplicants,
            'status'           => 'open',
        ]);

        $this->dispatch('toast', ['message' => 'Job posted successfully!', 'type' => 'success']);
        $this->redirect(route('backend.jobPostDetail', $job->id), navigate: true);
    }

    public function saveDraft(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'min:5', 'max:200'],
        ]);

        $job = JobPost::create([
            'client_id'        => Auth::id(),
            'title'            => $this->title,
            'description'      => $this->description ?: '',
            'category'         => $this->category ?: 'other',
            'type'             => $this->type,
            'budget_min'       => $this->budgetMin,
            'budget_max'       => $this->budgetMax,
            'currency'         => $this->currency,
            'experience_level' => $this->experienceLevel,
            'skills_required'  => $this->skills,
            'status'           => 'draft',
        ]);

        $this->dispatch('toast', ['message' => 'Draft saved.', 'type' => 'info']);
        $this->redirect(route('backend.myJobs'), navigate: true);
    }

    public function render()
    {
        return view('livewire.backend.jobPostCreate');
    }
}
