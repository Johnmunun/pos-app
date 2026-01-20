<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetRootPassword extends Command
{
    protected $signature = 'root:reset-password {--password=}';
    protected $description = 'Réinitialise le mot de passe du ROOT user';

    public function handle()
    {
        $rootUser = User::where('type', 'ROOT')->first();

        if (!$rootUser) {
            $this->error('ROOT user not found!');
            return 1;
        }

        $this->info('ROOT user found:');
        $this->line('Email: ' . $rootUser->email);
        $this->line('Name: ' . $rootUser->name);
        $this->line('Type: ' . $rootUser->type);
        $this->line('Active: ' . ($rootUser->is_active ? 'Yes' : 'No'));

        // Nouveau mot de passe
        $password = $this->option('password') ?: 'RootPassword123';

        $rootUser->password = Hash::make($password);
        $rootUser->save();

        $this->info('');
        $this->info('✅ Password reset successfully!');
        $this->info('');
        $this->line('Email: ' . $rootUser->email);
        $this->line('Password: ' . $password);
        $this->warn('⚠️  Change this password in production!');

        return 0;
    }
}



