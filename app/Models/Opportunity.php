<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Opportunity extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSING_SOON = 'closing_soon';
    public const STATUS_CLOSED = 'closed';

    public const CLOSING_SOON_DAYS = 14;

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    public const SOURCE_HIMALAYAS = 'himalayas';

    protected $fillable = [
        'title',
        'organization',
        'type',
        'description',
        'required_skills',
        'eligibility',
        'deadline',
        'application_url',
        'location',
        'source',
        'external_id',
        'source_url',
        'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
        ];
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'opportunity_skills')
            ->withTimestamps();
    }

    public function isApproved(): bool
    {
        return ($this->approval_status ?: self::APPROVAL_APPROVED) === self::APPROVAL_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function isHimalayasSourced(): bool
    {
        return $this->source === self::SOURCE_HIMALAYAS;
    }

    public function isExpired(?Carbon $today = null): bool
    {
        if ($this->deadline === null) {
            return false;
        }

        $today = ($today ?? now())->copy()->startOfDay();

        return $this->deadline->copy()->startOfDay()->lt($today);
    }

    public function isVisibleToStudents(?Carbon $today = null): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        if ($this->source && $this->isExpired($today)) {
            return false;
        }

        return true;
    }

    public function scopeVisibleToStudents(Builder $query): Builder
    {
        $today = now()->toDateString();

        return $query
            ->where(function (Builder $builder) {
                $builder->where('approval_status', self::APPROVAL_APPROVED)
                    ->orWhereNull('approval_status');
            })
            ->where(function (Builder $builder) use ($today) {
                $builder->whereNull('source')
                    ->orWhere('source', '')
                    ->orWhere(function (Builder $imported) use ($today) {
                        $imported->whereNotNull('source')
                            ->where('source', '!=', '')
                            ->where(function (Builder $open) use ($today) {
                                $open->whereNull('deadline')
                                    ->orWhereDate('deadline', '>=', $today);
                            });
                    });
            });
    }

    /**
     * @return list<string>
     */
    public static function parseSkillList(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $part) => trim($part), $parts),
            static fn (string $part) => $part !== ''
        ));
    }

    /**
     * @return list<string>
     */
    public function parsedRequiredSkills(): array
    {
        return self::parseSkillList($this->required_skills);
    }

    public function deadlineStatus(?Carbon $today = null): string
    {
        if ($this->deadline === null) {
            return self::STATUS_OPEN;
        }

        $today = ($today ?? now())->copy()->startOfDay();
        $deadline = $this->deadline->copy()->startOfDay();

        if ($deadline->lt($today)) {
            return self::STATUS_CLOSED;
        }

        if ($deadline->lte($today->copy()->addDays(self::CLOSING_SOON_DAYS))) {
            return self::STATUS_CLOSING_SOON;
        }

        return self::STATUS_OPEN;
    }

    public function deadlineStatusLabel(?Carbon $today = null): string
    {
        return match ($this->deadlineStatus($today)) {
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CLOSING_SOON => 'Closing Soon',
            default => 'Open',
        };
    }

    /**
     * @param  list<string>  $userSkillNames
     * @return array{has_user_skills: bool, percent: int|null, matched: list<string>, missing: list<string>}
     */
    public function skillMatch(array $userSkillNames): array
    {
        $required = $this->parsedRequiredSkills();
        $userSkills = array_values(array_filter(array_map(
            static fn (string $name) => mb_strtolower(trim($name)),
            $userSkillNames
        )));

        if ($userSkills === []) {
            return [
                'has_user_skills' => false,
                'percent' => null,
                'matched' => [],
                'missing' => $required,
            ];
        }

        if ($required === []) {
            return [
                'has_user_skills' => true,
                'percent' => null,
                'matched' => [],
                'missing' => [],
            ];
        }

        $matched = [];
        $missing = [];

        foreach ($required as $skill) {
            if ($this->requiredSkillMatchesUser($skill, $userSkills)) {
                $matched[] = $skill;
            } else {
                $missing[] = $skill;
            }
        }

        return [
            'has_user_skills' => true,
            'percent' => (int) round((count($matched) / count($required)) * 100),
            'matched' => $matched,
            'missing' => $missing,
        ];
    }

    /**
     * @param  list<string>  $normalizedUserSkills
     */
    private function requiredSkillMatchesUser(string $requiredToken, array $normalizedUserSkills): bool
    {
        $required = mb_strtolower($requiredToken);

        foreach ($normalizedUserSkills as $userSkill) {
            if ($userSkill === $required) {
                return true;
            }

            $pattern = '/(^|[^a-z0-9])'.preg_quote($userSkill, '/').'([^a-z0-9]|$)/i';

            if (preg_match($pattern, $requiredToken) === 1) {
                return true;
            }
        }

        return false;
    }
}
