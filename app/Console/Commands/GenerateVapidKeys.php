<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:generate-keys';

    protected $description = 'Génère une paire de clés VAPID pour Web Push (notifications navigateur)';

    public function handle(): int
    {
        $keys = $this->generateWithOpenSSL();
        if ($keys === null) {
            $keys = $this->generateWithNode();
        }
        if ($keys === null) {
            $this->error('Impossible de générer les clés. Vérifiez que PHP OpenSSL est configuré ou que Node.js est installé (npx web-push generate-vapid-keys).');
            return Command::FAILURE;
        }

        $this->info('Clés VAPID générées. Ajoutez-les à votre fichier .env :');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY=' . $keys['public']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['private']);
        $this->line('VAPID_SUBJECT=mailto:admin@example.com');
        $this->line('VITE_VAPID_PUBLIC_KEY=' . $keys['public']);
        $this->newLine();
        $this->comment('VITE_VAPID_PUBLIC_KEY = même valeur que VAPID_PUBLIC_KEY. Puis : npm run build ou npm run dev');

        return Command::SUCCESS;
    }

    private function generateWithOpenSSL(): ?array
    {
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ];
        $res = @openssl_pkey_new($config);
        if (!$res) {
            return null;
        }
        $details = openssl_pkey_get_details($res);
        if (!$details || !isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            return null;
        }
        $publicKeyBinary = "\04" . $details['ec']['x'] . $details['ec']['y'];
        return [
            'public' => $this->base64UrlEncode($publicKeyBinary),
            'private' => $this->base64UrlEncode($details['ec']['d']),
        ];
    }

    private function generateWithNode(): ?array
    {
        $process = new Process(['npx', '--yes', 'web-push', 'generate-vapid-keys'], base_path(), null, null, 30);
        $process->run();
        if (!$process->isSuccessful()) {
            return null;
        }
        $output = $process->getOutput();
        // Format: "Public Key:\n<key>" et "Private Key:\n<key>" (ou sur la même ligne)
        if (preg_match('/Public Key:\s*([A-Za-z0-9_-]+)/s', $output, $mPublic) && preg_match('/Private Key:\s*([A-Za-z0-9_-]+)/s', $output, $mPrivate)) {
            return [
                'public' => trim($mPublic[1]),
                'private' => trim($mPrivate[1]),
            ];
        }
        return null;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
