<?php

namespace App\Livewire\Backend;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /** View the org settings / members page */
    public function view(User $user, Organization $org): bool
    {
        return $org->hasUser($user->id);
    }

    /** Update name, logo, settings */
    public function update(User $user, Organization $org): bool
    {
        $role = $org->userRole($user->id);
        return in_array($role, ['owner', 'admin']);
    }

    /** Delete the organization — owner only */
    public function delete(User $user, Organization $org): bool
    {
        return $org->owner_id === $user->id;
    }

    /** Invite members */
    public function invite(User $user, Organization $org): bool
    {
        $role = $org->userRole($user->id);
        return in_array($role, ['owner', 'admin']);
    }

    /** Manage members (change roles, remove) */
    public function manageMembers(User $user, Organization $org): bool
    {
        $role = $org->userRole($user->id);
        return in_array($role, ['owner', 'admin']);
    }
}
