<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetAdminPassword extends Command
{
    protected $signature = 'simpleview:reset-admin-password {password?}';
    protected $description = 'Restablece la contraseña del administrador';

    public function handle(): int
    {
        $user = User::where('username', config('simpleview.admin_username'))->first();
        if (! $user) {
            $this->components->error('No existe el administrador.');
            return self::FAILURE;
        }
        $password = $this->argument('password') ?: $this->secret('Nueva contraseña');
        if (! is_string($password) || strlen($password) < 8) {
            $this->components->error('La contraseña debe tener al menos 8 caracteres.');
            return self::INVALID;
        }
        $user->update(['password' => $password, 'must_change_password' => false]);
        $this->components->info('Contraseña actualizada.');
        return self::SUCCESS;
    }
}
