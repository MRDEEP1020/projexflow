<?php

// tests/Feature/Client/ClientDashboardTest.php
uses(Tests\TestCase::class);

use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectMember;
use App\Livewire\Backend\ClientDashboard;
use App\Livewire\Backend\ProjectCreate;
use App\Livewire\Backend\ProjectBoard;
use App\Livewire\Backend\ProjectSettings;
use App\Livewire\Backend\ProjectList;
use App\Livewire\Tasks\TaskDetail;
use Livewire\Livewire;

// ── CLIENT DASHBOARD ──────────────────────────────────────────────

describe('Client Dashboard', function () {

    it('renders for a client user', function () {
        $user = clientUser();

        Livewire::actingAs($user)
            ->test(ClientDashboard::class)
            ->assertOk()
            ->assertSee('Active projects');
    });

    it('shows spending KPIs', function () {
        $user = clientUser();

        Livewire::actingAs($user)
            ->test(ClientDashboard::class)
            ->assertSee('Spent this month')
            ->assertSee('In active escrow')
            ->assertSee('Active contracts');
    });

    it('shows projects belonging to active org', function () {
        $user    = clientUser();
        $project = projectFor($user, ['name' => 'My Dashboard Project']);

        Livewire::actingAs($user)
            ->test(ClientDashboard::class)
            ->assertSee('My Dashboard Project');
    });

    it('does not show projects from other orgs', function () {
        $user       = clientUser();
        $otherUser  = clientUser();
        $otherProj  = projectFor($otherUser, ['name' => 'Other Org Project']);

        Livewire::actingAs($user)
            ->test(ClientDashboard::class)
            ->assertDontSee('Other Org Project');
    });
});

// ── PROJECTS ──────────────────────────────────────────────────────

describe('Project creation', function () {

    it('creates a project with valid data', function () {
        $user = clientUser();

        Livewire::actingAs($user)
            ->test(ProjectCreate::class)
            ->set('name', 'New SaaS Platform')
            ->set('description', 'A platform for managing things')
            ->set('status', 'planning')
            ->set('priority', 'high')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        expect(Project::where('name', 'New SaaS Platform')->exists())->toBeTrue();
    });

    it('fails with missing project name', function () {
        $user = clientUser();

        Livewire::actingAs($user)
            ->test(ProjectCreate::class)
            ->set('name', '')
            ->call('create')
            ->assertHasErrors(['name' => 'required']);
    });

    it('fails when due date is before start date', function () {
        $user = clientUser();

        Livewire::actingAs($user)
            ->test(ProjectCreate::class)
            ->set('name', 'Test Project')
            ->set('start_date', '2025-12-01')
            ->set('due_date', '2025-11-01')
            ->call('create')
            ->assertHasErrors(['due_date']);
    });
});

describe('Project board', function () {

    it('renders the kanban board', function () {
        $user    = clientUser();
        $project = projectFor($user);

        Livewire::actingAs($user)
            ->test(ProjectBoard::class, ['project' => $project])
            ->assertOk()
            ->assertSee($project->name);
    });

    it('creates a task from the board', function () {
        $user    = clientUser();
        $project = projectFor($user);

        Livewire::actingAs($user)
            ->test(ProjectBoard::class, ['project' => $project])
            ->set('newTaskTitle', 'Design the homepage')
            ->set('newTaskStatus', 'planning')
            ->call('quickAddTask')
            ->assertHasNoErrors();

        expect(Task::where('title', 'Design the homepage')
            ->where('project_id', $project->id)
            ->exists())->toBeTrue();
    });
});

describe('Project settings', function () {

    it('saves general settings', function () {
        $user    = clientUser();
        $project = projectFor($user);

        // Make user a project manager
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => 'manager',
        ]);

        Livewire::actingAs($user)
            ->test(ProjectSettings::class, ['project' => $project])
            ->set('name', 'Updated Project Name')
            ->set('status', 'active')
            ->set('priority', 'critical')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        expect($project->fresh()->name)->toBe('Updated Project Name');
        expect($project->fresh()->priority)->toBe('critical');
    });

    it('blocks non-members from accessing settings', function () {
        $owner   = clientUser();
        $project = projectFor($owner);

        $outsider = clientUser();

        Livewire::actingAs($outsider)
            ->test(ProjectSettings::class, ['project' => $project])
            ->assertForbidden();
    });

    it('enables client portal and saves', function () {
        $user    = clientUser();
        $project = projectFor($user);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => 'manager',
        ]);

        Livewire::actingAs($user)
            ->test(ProjectSettings::class, ['project' => $project])
            ->set('portal_enabled', true)
            ->set('client_name', 'Acme Corp')
            ->set('client_email', 'client@acme.com')
            ->call('savePortal')
            ->assertHasNoErrors();

        expect($project->fresh()->client_portal_enabled)->toBeTrue();
        expect($project->fresh()->client_name)->toBe('Acme Corp');
    });

    it('archives a project with correct name confirmation', function () {
        $user    = clientUser();
        $project = projectFor($user, ['name' => 'Archive Me']);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => 'manager',
        ]);

        Livewire::actingAs($user)
            ->test(ProjectSettings::class, ['project' => $project])
            ->set('archiveConfirm', 'Archive Me')
            ->call('archiveProject')
            ->assertHasNoErrors();

        expect($project->fresh()->archived_at)->not->toBeNull();
    });

    it('blocks archiving with wrong name confirmation', function () {
        $user    = clientUser();
        $project = projectFor($user, ['name' => 'Real Name']);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id'    => $user->id,
            'role'       => 'manager',
        ]);

        Livewire::actingAs($user)
            ->test(ProjectSettings::class, ['project' => $project])
            ->set('archiveConfirm', 'Wrong Name')
            ->call('archiveProject')
            ->assertHasErrors(['archiveConfirm']);
    });
});

// ── TASKS ─────────────────────────────────────────────────────────

describe('Task detail', function () {

    it('marks a task as done', function () {
        $user    = clientUser();
        $project = projectFor($user);
        $task    = Task::factory()->create([
            'project_id'  => $project->id,
            'assigned_to' => $user->id,
            'status'      => 'in_progress',
        ]);

        Livewire::actingAs($user)
            ->test(TaskDetail::class, ['taskId' => $task->id])
            ->call('updateStatus', 'done')
            ->assertHasNoErrors();

        expect($task->fresh()->status)->toBe('done');
        expect($task->fresh()->completed_at)->not->toBeNull();
    });

    it('submits a deliverable and auto-transitions to in_review', function () {
        $user = clientUser();
        $project = projectFor($user);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'status'     => 'in_progress',
        ]);

        Livewire::actingAs($user)
            ->test(TaskDetail::class, ['taskId' => $task->id])
            ->set('deliverableType', 'url')
            ->set('deliverableUrl', 'https://example.com/design')
            ->set('deliverableNote', 'Here is the design')
            ->call('submitDeliverable')
            ->assertHasNoErrors();

        // ALG-TASK-03: auto-transition to in_review on deliverable submit
        expect($task->fresh()->status)->toBe('in_review');
        expect($task->fresh()->deliverable_url)->toBe('https://example.com/design');
    });

    it('adds a comment to a task', function () {
        $user = clientUser();
        $project = projectFor($user);
        $task = Task::factory()->create(['project_id' => $project->id]);

        Livewire::actingAs($user)
            ->test(TaskDetail::class, ['taskId' => $task->id])
            ->set('comment', 'This looks great!')
            ->call('addComment')
            ->assertHasNoErrors();

        expect($task->comments()->where('body', 'This looks great!')->exists())->toBeTrue();
    });
});
