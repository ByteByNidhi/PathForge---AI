<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class OpportunityHubTest extends TestCase
{
    public function test_opportunity_hub_flow(): void
    {
        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user, 'An authenticated user is required in the local database.');
        assert($user instanceof User);
        $this->assertSame(10, Opportunity::query()->count());

        $nasa = Opportunity::query()->where('title', 'like', '%NASA%')->first();
        $smartIndia = Opportunity::query()->where('title', 'like', '%Smart India%')->first();
        $microsoft = Opportunity::query()->where('title', 'like', '%Microsoft%')->first();
        $gsoc = Opportunity::query()->where('title', 'like', '%Google Summer of Code%')->orWhere('title', 'like', '%Summer of Code%')->first();
        $inlaks = Opportunity::query()->where('title', 'like', '%Inlaks%')->orWhere('title', 'like', '%Inlaks%')->first();
        $cern = Opportunity::query()->where('title', 'like', '%CERN%')->first();
        $internship = Opportunity::query()->where('type', 'Internship')->first();

        $this->assertNotNull($nasa);
        $this->assertNotNull($smartIndia);
        $this->assertNotNull($microsoft);
        $this->assertNotNull($gsoc);
        $this->assertNotNull($inlaks);
        $this->assertNotNull($cern);
        $this->assertNotNull($internship);

        $this->actingAs($user)
            ->get('/opportunities')
            ->assertOk()
            ->assertSee('Opportunity Hub')
            ->assertSee('View Details')
            ->assertSee('Add your skills to calculate your match');

        $this->actingAs($user)
            ->get('/opportunities?type=Hackathon')
            ->assertOk()
            ->assertSee($nasa->title)
            ->assertDontSee($internship->title);

        $this->actingAs($user)
            ->get('/opportunities?q=NASA')
            ->assertOk()
            ->assertSee($nasa->title)
            ->assertDontSee($smartIndia->title);

        $this->actingAs($user)
            ->get('/opportunities?q=Python')
            ->assertOk()
            ->assertSee($smartIndia->title)
            ->assertSee($microsoft->title);

        $this->actingAs($user)
            ->get('/opportunities?status=closed')
            ->assertOk()
            ->assertSee($gsoc->title)
            ->assertSee('Closed');

        $this->actingAs($user)
            ->get('/opportunities?location='.urlencode($gsoc->location))
            ->assertOk()
            ->assertSee($gsoc->title);

        $this->actingAs($user)
            ->get('/opportunities?sort=latest')
            ->assertOk()
            ->assertSee($inlaks->title);

        $this->actingAs($user)
            ->get('/opportunities/'.$cern->id)
            ->assertOk()
            ->assertSee($cern->title)
            ->assertSee($cern->organization)
            ->assertSee('Research')
            ->assertSee('Apply')
            ->assertSee($cern->application_url, false)
            ->assertSee('target="_blank"', false)
            ->assertSee('Add your skills to calculate your match');
    }

    public function test_skill_match_uses_the_logged_in_users_skills(): void
    {
        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user);
        assert($user instanceof User);

        $python = Skill::query()->where('name', 'Python')->first();
        $git = Skill::query()->where('name', 'Git')->first();
        $this->assertNotNull($python);
        $this->assertNotNull($git);

        $gsoc = Opportunity::query()->where('title', 'like', '%Summer of Code%')->first();
        $this->assertNotNull($gsoc);

        $user->skills()->sync([$python->id, $git->id]);

        try {
            $match = $gsoc->skillMatch(['Python', 'Git']);
            $this->assertTrue($match['has_user_skills']);
            $this->assertContains('Git', $match['matched']);
            $this->assertNotEmpty($match['missing']);

            $this->actingAs($user)
                ->get('/opportunities/'.$gsoc->id)
                ->assertOk()
                ->assertSee($match['percent'].'%')
                ->assertSee('Git')
                ->assertDontSee('Add your skills to calculate your match');

            $this->actingAs($user)
                ->get('/opportunities?sort=match')
                ->assertOk()
                ->assertSee($match['percent'].'%');
        } finally {
            $user->skills()->sync([]);
        }
    }

    public function test_roadmap_index_still_works(): void
    {
        $user = User::query()->first();
        $this->assertInstanceOf(User::class, $user);
        assert($user instanceof User);

        $this->actingAs($user)
            ->get('/roadmaps')
            ->assertOk()
            ->assertSee('Learning roadmaps');
    }
}
