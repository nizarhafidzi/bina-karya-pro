<?php

namespace App\Filament\App\Resources\UserResource\Pages;

use App\Filament\App\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // Hook yang dijalankan SETELAH user berhasil dibuat di database
    protected function handleRecordCreation(array $data): Model
    {
        // 1. Buat User seperti biasa
        $user = static::getModel()::create($data);

        // 2. Ambil Tenant/Team saat ini
        $currentTeam = Filament::getTenant();

        // 3. Attach User ke Team (Pivot Table)
        if ($currentTeam) {
            $currentTeam->members()->attach($user);
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}