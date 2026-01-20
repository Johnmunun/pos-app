<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestPermissionSync extends Command
{
    protected $signature = 'test:permission-sync';
    protected $description = 'Test la synchronisation des permissions';

    public function handle(PermissionSyncService $service)
    {
        $this->info('Testing permission sync...');
        
        // Vérifier si le fichier existe
        $exists = Storage::disk('local')->exists('permissions.yaml');
        $this->info('File exists: ' . ($exists ? 'YES' : 'NO'));
        
        if ($exists) {
            $content = Storage::disk('local')->get('permissions.yaml');
            $this->info('File size: ' . strlen($content) . ' bytes');
            $this->info('First 200 chars:');
            $this->line(substr($content, 0, 200));
        }
        
        // Tester la synchronisation
        $result = $service->syncFromDefaultFile();
        
        $this->info('Result:');
        $this->table(
            ['Created', 'Updated', 'Marked Old'],
            [[$result['created'], $result['updated'], $result['marked_old']]]
        );
        
        return 0;
    }
}



