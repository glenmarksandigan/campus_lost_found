<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('role:admin,superadmin')
     * Role names map to type_id values:
     *   student=1, guard=2, staff=3, admin=4, superadmin=5, organizer=6
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roleMap = [
            'student'    => 1,
            'guard'      => 2,
            'staff'      => 3,
            'admin'      => 4,
            'superadmin' => 5,
            'organizer'  => 6,
        ];

        $allowedTypeIds = array_map(fn($r) => $roleMap[$r] ?? 0, $roles);

        if (! in_array((int) $request->user()->type_id, $allowedTypeIds)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
