<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->select('id', 'email')
            ->whereNotNull('deleted_at')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'email' => $this->deletedEmailPlaceholder($user),
                        'email_verified_at' => null,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function deletedEmailPlaceholder(object $user): string
    {
        $hash = substr(hash('sha256', $user->email.'|'.$user->id), 0, 16);

        return "deleted-user-{$user->id}-{$hash}@deleted.local";
    }
};
