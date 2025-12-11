<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class BlockOwnerFromPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (Filament::getCurrentPanel()->getId() === 'app' && $user) {
            if ($user->hasRole(['project_owner', 'site_manager'])) {
                
                $project = $user->ownedProjects()->first() ?? $user->managedProjects()->first();
                
                if ($project) {
                    return redirect()->route('project.dashboard', ['project' => $project->id]);
                }

                abort(403, 'Akses ke Admin Panel Ditolak.');
            }
        }

        return $next($request);
    }
}