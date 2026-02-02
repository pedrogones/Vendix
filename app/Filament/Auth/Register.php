<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class Register extends BaseRegister
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        $user = parent::handleRegistration($data);

        $portalRole = Role::firstOrCreate([
            'name' => 'Portal',
            'guard_name' => 'web',
        ]);

        $user->assignRole($portalRole);

        return $user;
    }
}
