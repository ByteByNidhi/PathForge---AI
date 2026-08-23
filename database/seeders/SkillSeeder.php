<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'HTML',
            'CSS',
            'JavaScript',
            'Python',
            'PHP',
            'Laravel',
            'React',
            'MySQL',
            'Cybersecurity',
            'Git',
            'Communication',
            'UI/UX Design',
        ];

        foreach ($names as $name) {
            Skill::findOrCreateByName($name);
        }
    }
}
