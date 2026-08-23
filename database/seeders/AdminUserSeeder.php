<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['email' => 'admin@pathforge.test']);
        $admin->name = $admin->exists ? $admin->name : 'PathForge Admin';

        if (! $admin->exists) {
            $admin->password = 'password';
        }

        $admin->is_admin = true;
        $admin->onboarding_completed = true;
        $admin->save();
    }
}
