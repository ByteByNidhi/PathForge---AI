<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const ROLE_OWNER = 'owner';
    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'website',
        'description',
        'logo_url',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (blank($organization->slug)) {
                $organization->slug = static::uniqueSlug((string) $organization->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationUser::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    public function owners(): BelongsToMany
    {
        return $this->users()->wherePivot('role', self::ROLE_OWNER);
    }

    public function isOwner(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('role', self::ROLE_OWNER)
            ->exists();
    }

    public function isMember(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function roleFor(User $user): ?string
    {
        $membership = $this->memberships()
            ->where('user_id', $user->id)
            ->first();

        return $membership?->role;
    }

    public function ownerCount(): int
    {
        return $this->memberships()
            ->where('role', self::ROLE_OWNER)
            ->count();
    }
}
