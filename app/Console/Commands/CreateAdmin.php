<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'simpleview:create-admin {--force : Actualiza también un usuario existente}';
    protected $description = 'Crea el administrador configurado para Simple View';

    public function handle(): int
    {
        $email = (string) config('simpleview.admin_email');
        $username = (string) config('simpleview.admin_username');
        $existing = User::where('username', $username)->orWhere('email', $email)->first();
        if ($existing && ! $this->option('force')) {
            if ($existing->username === null) {
                $existing->update([
                    'username' => $username,
                    'password' => (string) config('simpleview.admin_password'),
                    'must_change_password' => false,
                ]);
            }
            $this->components->info('El administrador ya existe.');
            return self::SUCCESS;
        }
        User::updateOrCreate(['email' => $email], [
            'name' => 'Administrador',
            'username' => $username,
            'password' => (string) config('simpleview.admin_password'),
            'must_change_password' => (bool) config('simpleview.force_password_change'),
        ]);
        $this->components->info("Administrador preparado: {$username}");
        return self::SUCCESS;
    }
}
