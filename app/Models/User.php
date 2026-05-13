<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\LeaveSetting;
use Carbon\Carbon;

#[Fillable(['name', 'email', 'password', 'colour', 'leave_allowance', 'employment_start_date'])]
#[Hidden(['password', 'remember_token', 'google2fa_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (! empty($user->colour)) {
                return;
            }

            $user->colour = static::generateUniqueColour();
        });

        static::saving(function (User $user): void {
            if (! $user->exists || $user->isDirty('employment_start_date')) {
                $user->leave_allowance = $user->calculateLeaveAllowance();
            }
        }); // On employment start date creation or update, sync leave allowance with rules
    }

    public static function generateUniqueColour(): string
    {
        do {
            $colour = sprintf('#%06X', random_int(0, 0xFFFFFF));
        } while (static::where('colour', $colour)->exists());

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

    public function calculateLeaveAllowance(): float
    {
        $settings = LeaveSetting::first();

        if (! $settings || ! $settings->base_allowance) {
            // If no settings are found, or base allowance is not set, return the user's leave_allowance or default to 20
            return (float) $this->leave_allowance;
        }

        if (! $this->employment_start_date) {
            return (float) $this->leave_allowance;
        }

        $yearsWorked = (int) floor(Carbon::parse($this->employment_start_date)->diffInYears(now()));

        if ($yearsWorked < $settings->increase_after_years) { 
            // If user hasn't reached the threshold for increase, return base allowance
            return (float) $settings->base_allowance;
        }

        $extraYears = $yearsWorked - $settings->increase_after_years + 1;
        // Calculate the allowance based on the base allowance and the increase for extra years, ensuring it does not exceed the maximum allowance

        $allowance = $settings->base_allowance + ($extraYears * $settings->increase_by_days);

        return min($allowance, $settings->maximum_allowance);
    }

    public function approvedLeaveDaysUsed(): float
    {
        $settings = LeaveSetting::first();
        $today = now();
        $allowanceYearStart = $this->leaveAllowanceYearStart($settings, $today);
        $allowanceYearEnd = $allowanceYearStart
            ? $this->leaveRefreshDateForYear($settings, (int) $allowanceYearStart->year + 1)
            : null;

        $query = $this->leaves()->where('status', 'approved');

        if ($allowanceYearStart && $allowanceYearEnd) {
            $query
                ->where('end_date', '>=', $allowanceYearStart->toDateString())
                ->where('start_date', '<', $allowanceYearEnd->toDateString());
        }

        return $query
            ->get()
            ->sum(function ($leave) use ($allowanceYearStart, $allowanceYearEnd) {
                if ($leave->is_half_day) {
                    return 0.5;
                }

                $startDate = Carbon::parse($leave->start_date);
                $endDate = Carbon::parse($leave->end_date);

                if ($allowanceYearStart && $startDate->lt($allowanceYearStart)) {
                    $startDate = $allowanceYearStart->copy();
                }

                if ($allowanceYearEnd && $endDate->gte($allowanceYearEnd)) {
                    $endDate = $allowanceYearEnd->copy()->subDay();
                }

                return $startDate->diffInDays($endDate) + 1;
            });
    }

    public function remainingLeaveAllowance(): float
    {
        return $this->leave_allowance - $this->approvedLeaveDaysUsed();
    }

    private function leaveAllowanceYearStart(?LeaveSetting $settings, Carbon $today): ?Carbon
    {
        if (! $settings?->leave_refresh_day || ! $settings?->leave_refresh_month) {
            return null;
        }

        $refreshDate = $this->leaveRefreshDateForYear($settings, (int) $today->year);

        if ($today->lt($refreshDate)) {
            return $this->leaveRefreshDateForYear($settings, (int) $today->year - 1);
        }

        return $refreshDate;
    }

    private function leaveRefreshDateForYear(LeaveSetting $settings, int $year): Carbon
    {
        $month = (int) $settings->leave_refresh_month;
        $day = min((int) $settings->leave_refresh_day, Carbon::create($year, $month, 1)->daysInMonth);

        return Carbon::create($year, $month, $day)->startOfDay();
    }

}
