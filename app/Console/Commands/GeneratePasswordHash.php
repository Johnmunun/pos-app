<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class GeneratePasswordHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password:hash {password : Le mot de passe à hasher}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un hash bcrypt pour un mot de passe (pour modification directe en DB)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = $this->argument('password');
        
        $hash = Hash::make($password);
        
        $this->info('Hash généré avec succès:');
        $this->line('');
        $this->line($hash);
        $this->line('');
        $this->info('Vous pouvez maintenant copier ce hash et l\'utiliser dans votre requête SQL:');
        $this->line("UPDATE users SET password = '{$hash}' WHERE email = 'votre@email.com';");
        
        return Command::SUCCESS;
    }
}
