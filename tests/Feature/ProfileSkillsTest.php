<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class ProfileSkillsTest extends TestCase
{
    public function test_guests_cannot_open_the_profile_page(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile_and_manage_skills(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Profile')
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee('You have not added any skills yet');

        $this->actingAs($user)
            ->post('/profile/skills', ['name' => 'Python'])
            ->assertRedirect();

        $this->assertTrue(
            $user->fresh()->skills()->where('name', 'Python')->exists()
        );

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Python');

        $this->actingAs($user)
            ->from('/profile')
            ->post('/profile/skills', ['name' => 'python'])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('name');

        $this->assertSame(1, $user->fresh()->skills()->count());

        $skill = $user->skills()->where('name', 'Python')->first();
        $this->assertNotNull($skill);

        $this->actingAs($user)
            ->post('/profile/skills/'.$skill->id.'/remove')
            ->assertRedirect();

        $this->assertFalse(
            $user->fresh()->skills()->where('name', 'Python')->exists()
        );

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('You have not added any skills yet');

        $user->delete();
    }

    public function test_opportunity_hub_reads_skills_added_from_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/skills', ['name' => 'Git']);

        $gsoc = Opportunity::query()->where('title', 'like', '%Summer of Code%')->first();
        $this->assertNotNull($gsoc);

        $skillNames = $user->fresh()->skills()->pluck('name')->all();
        $this->assertContains('Git', $skillNames);

        $match = $gsoc->skillMatch($skillNames);
        $this->assertTrue($match['has_user_skills']);
        $this->assertContains('Git', $match['matched']);

        $this->actingAs($user)
            ->get('/opportunities/'.$gsoc->id)
            ->assertOk()
            ->assertSee($match['percent'].'%')
            ->assertDontSee('Add your skills to calculate your match');

        $user->skills()->sync([]);
        $user->delete();
    }
}
