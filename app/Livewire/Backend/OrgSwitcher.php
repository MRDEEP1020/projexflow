<?php

namespace App\Livewire\Backend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class OrgSwitcher extends Component
{
    public function switchOrg(int $orgId): void
    {
        $user = Auth::user();

        // Validate membership (ALG-AUTH-03 Step 2)
        $membership = $user->orgMemberships()
                           ->where('org_id', $orgId)
                           ->first();

        if (! $membership) {
            abort(403, 'You are not a member of this organization.');
        }

        Session::put('active_org_id', $orgId);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        // All orgs the user belongs to, with their role
        $memberships = $user->orgMemberships()
                            ->with('organization')
                            ->get()
                            ->map(fn ($m) => [
                                'org'  => $m->organization,
                                'role' => $m->role,
                            ]);

        $activeOrgId = Session::get('active_org_id');

        return view('livewire.backend.org-switcher', [
            'memberships'  => $memberships,
            'activeOrgId'  => $activeOrgId,
        ]);
    }
}
