<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetRootPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'root:reset-password {--password= : Le nouveau mot de passe (optionnel, sera demandé si non fourni)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise le mot de passe de l\'utilisateur ROOT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Trouver l'utilisateur ROOT
        $rootUser = User::where('type', 'ROOT')->first();

        if (!$rootUser) {
            $this->error('❌ Aucun utilisateur ROOT trouvé dans la base de données.');
            $this->info('💡 Créez d\'abord l\'utilisateur ROOT avec: php artisan db:seed --class=CreateRootUserSeeder');
            return 1;
        }

        $this->info('✅ Utilisateur ROOT trouvé: ' . $rootUser->email);

        // Demander le nouveau mot de passe
        $password = $this->option('password');

        if (!$password) {
            $password = $this->secret('🔐 Entrez le nouveau mot de passe:');
            
            if (empty($password)) {
                $this->error('❌ Le mot de passe ne peut pas être vide.');
                return 1;
            }

            $confirmPassword = $this->secret('🔐 Confirmez le nouveau mot de passe:');

            if ($password !== $confirmPassword) {
                $this->error('❌ Les mots de passe ne correspondent pas.');
                return 1;
            }
        }

        // Vérifier la force du mot de passe
        if (strlen($password) < 8) {
            $this->warn('⚠️  Le mot de passe est trop court (minimum 8 caractères recommandé).');
            if (!$this->confirm('Continuer quand même?')) {
                return 1;
            }
        }

        // Mettre à jour le mot de passe
        try {
            $rootUser->password = Hash::make($password);
            $rootUser->save();

            $this->info('✅ Mot de passe ROOT mis à jour avec succès!');
            $this->line('');
            $this->line('📧 Email: ' . $rootUser->email);
            $this->line('🔑 Nouveau mot de passe: ' . str_repeat('*', strlen($password)));
            $this->line('');
            $this->info('💡 Vous pouvez maintenant vous connecter avec ces identifiants.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la mise à jour du mot de passe: ' . $e->getMessage());
            return 1;
        }
    }
}
