<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:prune-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes users that have not verified their email address within the past 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = User::whereNull('email_verified_at')
        ->where('created_at', '<', now()->subHours(24))
        ->get();

        $users->map(function($user) {
            $user->wallet()->delete();
            $user->settings()->delete();
            $user->generalSettings()->delete();
            $user->log()->delete();
            $user->variables()->delete();
            $user->privacy()->delete();
            $user->delete();
        });
    }
}
