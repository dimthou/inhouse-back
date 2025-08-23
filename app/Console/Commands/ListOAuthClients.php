<?php

namespace App\Console\Commands;

use App\Models\OAuthClient;
use Illuminate\Console\Command;

class ListOAuthClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:list-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all OAuth clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clients = OAuthClient::all();

        if ($clients->isEmpty()) {
            $this->info('No OAuth clients found.');
            return Command::SUCCESS;
        }

        $this->info('OAuth Clients:');
        $this->newLine();

        $headers = ['ID', 'Name', 'Type', 'Redirect URI', 'Password Client', 'Revoked'];
        $rows = [];

        foreach ($clients as $client) {
            $type = $client->confidential() ? 'Confidential' : 'Public';
            $rows[] = [
                $client->id,
                $client->name,
                $type,
                $client->redirect,
                $client->password_client ? 'Yes' : 'No',
                $client->revoked ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Client Secrets (for confidential clients):');
        foreach ($clients as $client) {
            if ($client->confidential()) {
                $this->line("  {$client->name}: {$client->secret}");
            }
        }

        return Command::SUCCESS;
    }
}
