<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\LeaveAllowanceService;

#[Fillable(['name', 'email', 'password', 'colour', 'leave_allowance', 'employment_start_date', 'department_id'])]
#[Hidden(['password', 'remember_token', 'google2fa_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! empty($user->colour)) {
                return;
            }

            $user->colour = static::generateUniqueColour();
        });

        static::saving(function (User $user): void {
            if ((! $user->exists && $user->employment_start_date) || ($user->exists && $user->isDirty('employment_start_date'))) {
                $user->leave_allowance = $user->calculateLeaveAllowance();
            } // On employment start date creation or update, sync leave allowance with rules
        });

        static::deleting(function (User $user): void {
            if ($user->isForceDeleting() || $user->deleted_at !== null) {
                return;
            }

            $user->forceFill([
                'email' => $user->deletedEmailPlaceholder(),
                'email_verified_at' => null,
            ])->saveQuietly();
        });
    }

    public static function generateUniqueColour(): string
    {
        do {
            $colour = sprintf('#%06X', random_int(0, 0xFFFFFF));
        } while (static::withTrashed()->where('colour', $colour)->exists());

        return $colour;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'google2fa_secret' => 'encrypted',
            'password' => 'hashed',
            'colour' => 'string',
        ];
    }

    public function isAdmin(): bool 
    {
        return $this->role === 'admin';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! empty($this->getRawOriginal('google2fa_secret'));
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(UserDepartment::class, 'department_id');
    }

    public function calculateLeaveAllowance(): float
    {
        return app(LeaveAllowanceService::class)->calculateAllowance($this);
    }

    public function approvedLeaveDaysUsed(): float
    {
        return $this->leaveDaysUsedForStatus('approved');
    }

    public function remainingLeaveAllowance(): float
    {
        return $this->leave_allowance - $this->approvedLeaveDaysUsed();
    }

    public function calculatePendingLeaveNumber(): float
    {
        return $this->leaveDaysUsedForStatus('pending');
    }

    private function leaveDaysUsedForStatus(string $status): float
    {
        return app(LeaveAllowanceService::class)->leaveDaysUsedForStatus($this, $status);
    }

    private function deletedEmailPlaceholder(): string
    {
        $hash = substr(hash('sha256', $this->email.'|'.$this->id.'|'.microtime(true)), 0, 16);

        return "deleted-user-{$this->id}-{$hash}@deleted.local";
    }

}
