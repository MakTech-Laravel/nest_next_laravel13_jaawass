<?php

namespace App\Console\Commands;

use App\Actions\Api\V1\Auth\DeletePassportTokensForUserAction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizeScheduledAccountDeletionsCommand extends Command
{
    protected $signature = 'users:finalize-scheduled-deletions';

    protected $description = 'Permanently delete user accounts whose deletion grace period has ended.';

    public function handle(DeletePassportTokensForUserAction $deletePassportTokensForUserAction): int
    {
        $graceDays = config('account.deletion_grace_days');
        $cutoff = now()->subDays($graceDays);

        $query = User::query()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $cutoff);

        $count = 0;

        $query->chunkById(100, function ($users) use ($deletePassportTokensForUserAction, &$count): void {
            foreach ($users as $user) {
                DB::transaction(function () use ($user, $deletePassportTokensForUserAction, &$count): void {
                    $deletePassportTokensForUserAction->handle($user);
                    $user->loginHistories()->delete();
                    $user->factoryImages()->delete();
                    $user->company()->delete();
                    $user->delete();
                    $count++;
                });
            }
        });

        $this->info("Permanently deleted {$count} user(s).");

        return self::SUCCESS;
    }
}
