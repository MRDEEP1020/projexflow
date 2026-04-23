<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SetActiveOrg
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user      = Auth::user();
        $activeId  = Session::get('active_org_id');

        // Validate stored org — user must still be a member
        if ($activeId) {
            $isMember = $user->orgMemberships()
                             ->where('org_id', $activeId)
                             ->exists();

            if (! $isMember) {
                // Org no longer valid — fall back to personal workspace
                $activeId = null;
                Session::forget('active_org_id');
            }
        }

        // No active org in session — default to personal workspace
        if (! $activeId) {
            $personal = Organization::where('owner_id', $user->id)
                                    ->where('type', 'personal')
                                    ->first();

            if ($personal) {
                $activeId = $personal->id;
                Session::put('active_org_id', $activeId);
            }
        }

        // Load and share the active org with every view
        $org = $activeId
            ? Organization::find($activeId)
            : null;

        $request->merge(['active_org' => $org]);
        view()->share('activeOrg', $org);

        return $next($request);
    }
}
