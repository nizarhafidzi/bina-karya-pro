<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = auth()->user();

        // 1. Jika User adalah Owner atau Site Manager
        // Kita cari proyek mereka dan lempar ke Custom Dashboard
        if ($user->hasRole(['Project Owner', 'Site Manager'])) {
            // Ambil satu project yang terkait (bisa dikembangkan jika punya banyak project)
            $project = $user->ownedProjects()->first() ?? $user->managedProjects()->first();
            
            if ($project) {
                return redirect()->route('project.dashboard', ['project' => $project->id]);
            }
            
            // Jika tidak punya project, mungkin tampilkan error atau halaman kosong
            abort(403, 'Anda tidak memiliki proyek aktif.');
        }

        // 2. Jika Tenant Admin atau Super Admin -> Masuk ke Panel Filament Biasa
        return redirect()->intended(filament()->getUrl());
    }
}