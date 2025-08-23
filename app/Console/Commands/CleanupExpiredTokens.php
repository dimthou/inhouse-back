<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:cleanup {--days=7 : Number of days to keep expired tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $expiredTokens = RefreshToken::where('expires_at', '<', $cutoffDate)->count();
        
        RefreshToken::where('expires_at', '<', $cutoffDate)->delete();

        $this->info("Cleaned up {$expiredTokens} expired refresh tokens older than {$days} days.");
        
        return Command::SUCCESS;
    }
}
