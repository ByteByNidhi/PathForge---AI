<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    public function test_new_registration_is_sent_to_onboarding(): void
    {
        $email = 'onboard-'.uniqid().'@example.com';

        $this->post('/register', [
            'name' => 'Onboard User',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('onboarding.show'));

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasCompletedOnboarding());

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('onboarding.show'));

        $user->delete();
    }

    public function test_login_sends_incomplete_users_to_onboarding(): void
    {
        $user = User::factory()->needsOnboarding()->create([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('onboarding.show'));

        $user->delete();
    }

    public function test_completed_users_skip_onboarding_after_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'onboarding_completed' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('dashboard'));

        $user->delete();
    }

    public function test_onboarding_shows_the_seven_career_paths(): void
    {
        $user = User::factory()->needsOnboarding()->create();

        $paths = LearningPath::query()->orderBy('path_name')->pluck('path_name');
        $this->assertCount(7, $paths);

        $response = $this->actingAs($user)
            ->get('/onboarding')
            ->assertOk()
            ->assertSee('Choose your career path');

        foreach ($paths as $pathName) {
            $response->assertSee($pathName);
        }

        $user->delete();
    }

    public function test_user_can_complete_onboarding_and_reach_the_dashboard(): void
    {
        $user = User::factory()->needsOnboarding()->create();
        $path = LearningPath::query()->where('path_name', 'Web Development')->first()
            ?? LearningPath::query()->orderBy('path_name')->first();
        $this->assertNotNull($path, 'At least one career path is required.');

        $this->actingAs($user)
            ->post('/onboarding/path', ['path_id' => $path->id])
            ->assertRedirect(route('onboarding.skills'));

        $this->actingAs($user)
            ->post('/onboarding/skills', ['name' => 'Python'])
            ->assertRedirect(route('onboarding.skills'));

        $python = Skill::query()->whereRaw('LOWER(name) = ?', ['python'])->first();
        $this->assertNotNull($python);

        $this->actingAs($user)
            ->post('/onboarding/skills/continue', ['skill_ids' => [$python->id]])
            ->assertRedirect(route('onboarding.confirm'));

        $this->actingAs($user)
            ->get('/onboarding/confirm')
            ->assertOk()
            ->assertSee($path->path_name)
            ->assertSee('Python');

        $this->actingAs($user)
            ->post('/onboarding/confirm')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
        $this->assertSame((int) $path->id, (int) $user->path_id);
        $this->assertTrue($user->skills()->where('name', 'Python')->exists());

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($path->path_name)
            ->assertSee('Continue Roadmap')
            ->assertSee(route('roadmaps.show', $path), false);

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertRedirect(route('dashboard'));

        $user->skills()->sync([]);
        $user->delete();
    }

    public function test_skills_step_requires_a_selected_path(): void
    {
        $user = User::factory()->needsOnboarding()->create();

        $this->actingAs($user)
            ->get('/onboarding/skills')
            ->assertRedirect(route('onboarding.show'));

        $this->actingAs($user)
            ->get('/onboarding/confirm')
            ->assertRedirect(route('onboarding.show'));

        $user->delete();
    }
}
