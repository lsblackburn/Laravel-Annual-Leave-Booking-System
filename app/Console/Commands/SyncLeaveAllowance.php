<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\LeaveSetting;
use App\Models\User;
use App\Services\LeaveAllowanceService;

#[Signature('leave:sync-allowances')]
#[Description('Sync leave allowances based on settings')]
class SyncLeaveAllowance extends Command
{
    /**
     * Execute the console command.
     */

    protected $signature = 'leave:sync-allowances';
    protected $description = 'Sync leave allowances based on settings';

    public function handle(LeaveAllowanceService $leaveAllowance)
    {
        $settings = LeaveSetting::first();

        if (! $settings) {
            $this->warn('No leave settings found');
            return Command::SUCCESS;
        }

        $today = now();

        if (! $leaveAllowance->isRefreshDate($settings, $today)) {
            return Command::SUCCESS; // exit early if not the right date
        }

        User::query()->chunk(100, function ($users) {
            foreach ($users as $user) {
                // Sync all users leave allowance on the refresh date
                $user->update([
                    'leave_allowance' => $user->calculateLeaveAllowance(),
                ]);
            }
        });

        $this->info('Leave allowances synced successfully');
    }
}
