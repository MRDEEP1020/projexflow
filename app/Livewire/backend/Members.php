<?php

namespace App\Livewire\Backend;

use App\Jobs\SendInvitationEmail;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Members')]
class Members extends Component
{
    use AuthorizesRequests;

    public Organization $org;

    // Invite form
    #[Validate('required|email|max:191')]
    public string $inviteEmail = '';

    #[Validate('required|in:admin,project_manager,member,viewer')]
    public string $inviteRole = 'member';

    public bool $showInviteForm = false;

    // Confirm remove modal
    public ?int $removingMemberId = null;
    public string $removingMemberName = '';

    public function mount(Organization $org): void
    {
        $this->authorize('update', $org);
        $this->org = $org;
    }

    public function sendInvite(): void
    {
        $this->authorize('update', $this->org);
        $this->validate();

        $email = strtolower(trim($this->inviteEmail));

        // Check existing membership
        $alreadyMember = $this->org->members()
                                   ->where('email', $email)
                                   ->exists();
        if ($alreadyMember) {
            $this->addError('inviteEmail', 'This person is already a member of this organization.');
            return;
        }

        // Check pending invite
        $pendingInvite = Invitation::where('org_id', $this->org->id)
                                   ->where('email', $email)
                                   ->whereNull('accepted_at')
                                   ->where('expires_at', '>', now())
                                   ->exists();
        if ($pendingInvite) {
            $this->addError('inviteEmail', 'An invitation has already been sent to this email address.');
            return;
        }

        $token = Invitation::generateToken();

        $invitation = Invitation::create([
            'org_id'     => $this->org->id,
            'email'      => $email,
            'role'       => $this->inviteRole,
            'token'      => $token,
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        SendInvitationEmail::dispatch($invitation);

        $this->inviteEmail = '';
        $this->inviteRole  = 'member';
        $this->showInviteForm = false;

        $this->dispatch('toast', ['message' => "Invitation sent to {$email}", 'type' => 'success']);
    }

    public function changeRole(int $memberId, string $newRole): void
    {
        $this->authorize('update', $this->org);

        $validRoles = ['admin', 'project_manager', 'member', 'viewer'];
        if (! in_array($newRole, $validRoles)) {
            return;
        }

        $member = OrganizationMember::where('id', $memberId)
                                    ->where('org_id', $this->org->id)
                                    ->first();

        if (! $member || $member->role === 'owner') {
            return; // Cannot change owner role
        }

        $member->update(['role' => $newRole]);
        $this->dispatch('toast', ['message' => 'Role updated.', 'type' => 'success']);
    }

    public function confirmRemove(int $memberId, string $memberName): void
    {
        $this->removingMemberId   = $memberId;
        $this->removingMemberName = $memberName;
    }

    public function removeMember(): void
    {
        $this->authorize('update', $this->org);

        if (! $this->removingMemberId) return;

        $member = OrganizationMember::where('id', $this->removingMemberId)
                                    ->where('org_id', $this->org->id)
                                    ->first();

        if (! $member || $member->role === 'owner') {
            $this->dispatch('toast', ['message' => 'Cannot remove the organization owner.', 'type' => 'error']);
            return;
        }

        // Prevent removing last admin
        if ($member->role === 'admin') {
            $adminCount = OrganizationMember::where('org_id', $this->org->id)
                                            ->whereIn('role', ['admin', 'owner'])
                                            ->count();
            if ($adminCount <= 1) {
                $this->dispatch('toast', ['message' => 'Cannot remove the last admin.', 'type' => 'error']);
                return;
            }
        }

        $userId = $member->user_id;

        DB::transaction(function () use ($userId) {
            // Delete from all projects in this org
            \App\Models\ProjectMember::whereHas('project', function ($q) {
                $q->where('org_id', $this->org->id);
            })->where('user_id', $userId)->delete();

            // Unassign all tasks in this org's projects
            Task::whereHas('project', function ($q) {
                $q->where('org_id', $this->org->id);
            })->where('assigned_to', $userId)
              ->update(['assigned_to' => null]);

            // Remove org membership
            OrganizationMember::where('id', $this->removingMemberId)->delete();
        });

        $name = $this->removingMemberName;
        $this->removingMemberId   = null;
        $this->removingMemberName = '';

        $this->dispatch('toast', ['message' => "{$name} has been removed.", 'type' => 'info']);
    }

    public function cancelInvite(int $invitationId): void
    {
        $this->authorize('update', $this->org);

        Invitation::where('id', $invitationId)
                  ->where('org_id', $this->org->id)
                  ->whereNull('accepted_at')
                  ->delete();

        $this->dispatch('toast', ['message' => 'Invitation cancelled.', 'type' => 'info']);
    }

    public function resendInvite(int $invitationId): void
    {
        $this->authorize('update', $this->org);

        $invitation = Invitation::where('id', $invitationId)
                                ->where('org_id', $this->org->id)
                                ->whereNull('accepted_at')
                                ->first();

        if ($invitation) {
            // Extend expiry and resend
            $invitation->update(['expires_at' => now()->addDays(7)]);
            SendInvitationEmail::dispatch($invitation);
            $this->dispatch('toast', ['message' => 'Invitation resent.', 'type' => 'success']);
        }
    }

    public function render()
    {
        $members = OrganizationMember::where('org_id', $this->org->id)
                                     ->with('user')
                                     ->get();

        $pendingInvites = Invitation::where('org_id', $this->org->id)
                                    ->whereNull('accepted_at')
                                    ->where('expires_at', '>', now())
                                    ->with('invitedBy')
                                    ->latest('created_at')
                                    ->get();

        return view('livewire.backend.members', [
            'members'        => $members,
            'pendingInvites' => $pendingInvites,
        ]);
    }
}
