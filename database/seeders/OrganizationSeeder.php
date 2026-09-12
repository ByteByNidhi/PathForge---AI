<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrNew(['slug' => 'pathforge-demo-org']);
        $organization->fill([
            'name' => 'PathForge Demo Org',
            'email' => 'org@pathforge.test',
            'website' => 'https://example.com',
            'description' => 'Demo organization for the PathForge college project.',
            'status' => Organization::STATUS_ACTIVE,
        ]);
        $organization->save();

        $owner = User::query()->firstOrNew(['email' => 'org@pathforge.test']);
        $owner->name = $owner->exists ? $owner->name : 'Demo Organization';
        if (! $owner->exists) {
            $owner->password = 'password';
        }
        $owner->is_admin = false;
        $owner->onboarding_completed = true;
        $owner->save();

        if (! $organization->isMember($owner)) {
            $organization->users()->attach($owner->id, ['role' => Organization::ROLE_OWNER]);
        }
    }
}
