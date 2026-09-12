<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use App\Services\HimalayasJobService;
use Tests\TestCase;

class StudentOpportunityHubTest extends TestCase
{
    protected function tearDown(): void
    {
        Opportunity::query()->where('title', 'like', 'PF Hub Test %')->delete();
        parent::tearDown();
    }

    public function test_student_sees_approved_but_not_pending_rejected_or_expired_opportunities(): void
    {
        $student = User::factory()->create();

        $approved = $this->createHubOpportunity([
            'title' => 'PF Hub Test Approved Role',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'deadline' => now()->addMonth()->toDateString(),
        ]);
        $pending = $this->createHubOpportunity([
            'title' => 'PF Hub Test Pending Role',
            'approval_status' => Opportunity::APPROVAL_PENDING,
            'deadline' => now()->addMonth()->toDateString(),
        ]);
        $rejected = $this->createHubOpportunity([
            'title' => 'PF Hub Test Rejected Role',
            'approval_status' => Opportunity::APPROVAL_REJECTED,
            'deadline' => now()->addMonth()->toDateString(),
        ]);
        $expired = $this->createHubOpportunity([
            'title' => 'PF Hub Test Expired Role',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'deadline' => now()->subDay()->toDateString(),
            'source' => HimalayasJobService::SOURCE,
            'external_id' => 'pf-hub-test-expired',
        ]);

        try {
            $this->actingAs($student)
                ->get('/opportunities')
                ->assertOk()
                ->assertSee('PF Hub Test Approved Role')
                ->assertDontSee('PF Hub Test Pending Role')
                ->assertDontSee('PF Hub Test Rejected Role')
                ->assertDontSee('PF Hub Test Expired Role');

            $this->actingAs($student)->get('/opportunities/'.$approved->id)->assertOk();
            $this->actingAs($student)->get('/opportunities/'.$pending->id)->assertNotFound();
            $this->actingAs($student)->get('/opportunities/'.$rejected->id)->assertNotFound();
            $this->actingAs($student)->get('/opportunities/'.$expired->id)->assertNotFound();
        } finally {
            $student->delete();
        }
    }

    public function test_match_percentage_and_matched_skills_use_the_logged_in_students_skills(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $javascript = Skill::query()->where('name', 'JavaScript')->first();
        $html = Skill::query()->where('name', 'HTML')->first();
        $python = Skill::query()->where('name', 'Python')->first();
        $this->assertNotNull($javascript);
        $this->assertNotNull($html);
        $this->assertNotNull($python);

        $opportunity = $this->createHubOpportunity([
            'title' => 'PF Hub Test Match Role',
            'required_skills' => 'Python',
        ]);
        $opportunity->skills()->sync([$javascript->id, $html->id]);

        $student->skills()->sync([$javascript->id, $html->id]);
        $other->skills()->sync([$python->id]);

        try {
            $studentMatch = $opportunity->fresh()->skillMatch(['JavaScript', 'HTML']);
            $this->assertSame(100, $studentMatch['percent']);
            $this->assertEqualsCanonicalizing(['JavaScript', 'HTML'], $studentMatch['matched']);

            $otherMatch = $opportunity->fresh()->skillMatch(['Python']);
            $this->assertSame(0, $otherMatch['percent']);
            $this->assertSame([], $otherMatch['matched']);

            $this->actingAs($student)
                ->get('/opportunities/'.$opportunity->id)
                ->assertOk()
                ->assertSee('100% Skill Match')
                ->assertSee('✓ JavaScript')
                ->assertSee('✓ HTML');

            $this->actingAs($other)
                ->get('/opportunities/'.$opportunity->id)
                ->assertOk()
                ->assertSee('0% Skill Match')
                ->assertSee('No matching skills yet')
                ->assertDontSee('100% Skill Match');
        } finally {
            $student->delete();
            $other->delete();
        }
    }

    public function test_higher_match_opportunities_are_prioritized(): void
    {
        $student = User::factory()->create();
        $javascript = Skill::query()->where('name', 'JavaScript')->first();
        $html = Skill::query()->where('name', 'HTML')->first();
        $python = Skill::query()->where('name', 'Python')->first();
        $this->assertNotNull($javascript);
        $this->assertNotNull($html);
        $this->assertNotNull($python);

        $low = $this->createHubOpportunity([
            'title' => 'PF Hub Test Low Match',
            'created_at' => now()->addMinute(),
        ]);
        $high = $this->createHubOpportunity([
            'title' => 'PF Hub Test High Match',
            'created_at' => now(),
        ]);
        $low->skills()->sync([$html->id, $python->id]);
        $high->skills()->sync([$javascript->id, $html->id]);
        $student->skills()->sync([$javascript->id, $html->id]);

        try {
            $html = $this->actingAs($student)
                ->get('/opportunities')
                ->assertOk()
                ->assertSee('PF Hub Test High Match')
                ->assertSee('PF Hub Test Low Match')
                ->getContent();

            $this->assertLessThan(
                strpos($html, 'PF Hub Test Low Match'),
                strpos($html, 'PF Hub Test High Match')
            );
        } finally {
            $student->delete();
        }
    }

    public function test_student_can_save_and_unsave_without_duplicates_or_cross_user_changes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $opportunity = $this->createHubOpportunity([
            'title' => 'PF Hub Test Save Role',
        ]);

        try {
            $this->actingAs($owner)
                ->post(route('opportunities.save', $opportunity))
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertSame(1, $owner->savedOpportunities()->count());

            $this->actingAs($owner)
                ->post(route('opportunities.save', $opportunity))
                ->assertRedirect();

            $this->assertSame(1, $owner->savedOpportunities()->count());

            $this->actingAs($owner)
                ->get(route('opportunities.saved'))
                ->assertOk()
                ->assertSee('PF Hub Test Save Role');

            $this->actingAs($other)
                ->get(route('opportunities.saved'))
                ->assertOk()
                ->assertDontSee('PF Hub Test Save Role');

            $this->actingAs($other)
                ->delete(route('opportunities.unsave', $opportunity))
                ->assertRedirect();

            $this->assertTrue(
                $owner->savedOpportunities()->where('opportunities.id', $opportunity->id)->exists()
            );

            $this->actingAs($owner)
                ->delete(route('opportunities.unsave', $opportunity))
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertSame(0, $owner->savedOpportunities()->count());
        } finally {
            $owner->delete();
            $other->delete();
        }
    }

    public function test_student_cannot_save_a_missing_or_pending_opportunity(): void
    {
        $student = User::factory()->create();
        $pending = $this->createHubOpportunity([
            'title' => 'PF Hub Test Hidden Save',
            'approval_status' => Opportunity::APPROVAL_PENDING,
        ]);

        try {
            $this->actingAs($student)
                ->post('/opportunities/999999/save')
                ->assertNotFound();

            $this->actingAs($student)
                ->post(route('opportunities.save', $pending))
                ->assertNotFound();

            $this->assertSame(0, $student->savedOpportunities()->count());

            $this->actingAs($student)
                ->post(route('admin.opportunities.approve', $pending))
                ->assertForbidden();
        } finally {
            $student->delete();
        }
    }

    public function test_apply_uses_external_url_and_handles_missing_links(): void
    {
        $student = User::factory()->create();
        $withUrl = $this->createHubOpportunity([
            'title' => 'PF Hub Test Apply Role',
            'application_url' => 'https://himalayas.app/companies/acme/jobs/apply-role',
            'source' => HimalayasJobService::SOURCE,
            'external_id' => 'pf-hub-test-apply',
        ]);
        $withoutUrl = $this->createHubOpportunity([
            'title' => 'PF Hub Test No Apply Role',
            'application_url' => null,
        ]);
        $unsafe = $this->createHubOpportunity([
            'title' => 'PF Hub Test Unsafe Apply Role',
            'application_url' => 'javascript:alert(1)',
        ]);
        $manual = $this->createHubOpportunity([
            'title' => 'PF Hub Test Manual Role',
            'source' => null,
            'external_id' => null,
            'application_url' => 'https://www.sih.gov.in/',
        ]);

        try {
            $this->actingAs($student)
                ->get('/opportunities/'.$withUrl->id)
                ->assertOk()
                ->assertSee('https://himalayas.app/companies/acme/jobs/apply-role', false)
                ->assertSee('target="_blank"', false)
                ->assertSee('Himalayas');

            $this->actingAs($student)
                ->get('/opportunities/'.$withoutUrl->id)
                ->assertOk()
                ->assertSee('No application link is available yet.');

            $this->actingAs($student)
                ->get('/opportunities/'.$unsafe->id)
                ->assertOk()
                ->assertSee('No application link is available yet.')
                ->assertDontSee('javascript:alert(1)', false);

            $this->actingAs($student)
                ->get('/opportunities/'.$manual->id)
                ->assertOk()
                ->assertSee('PF Hub Test Manual Role')
                ->assertSee('https://www.sih.gov.in/', false);

            $this->actingAs($student)
                ->get('/opportunities?type=Internship')
                ->assertOk()
                ->assertSee('PF Hub Test Manual Role');
        } finally {
            $student->delete();
        }
    }

    public function test_opportunity_with_no_mapped_skills_does_not_fabricate_a_match(): void
    {
        $student = User::factory()->create();
        $javascript = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($javascript);
        $student->skills()->sync([$javascript->id]);

        $opportunity = $this->createHubOpportunity([
            'title' => 'PF Hub Test Unmapped Role',
            'required_skills' => null,
        ]);

        try {
            $match = $opportunity->skillMatch(['JavaScript']);
            $this->assertTrue($match['has_user_skills']);
            $this->assertNull($match['percent']);
            $this->assertSame([], $match['matched']);

            $this->actingAs($student)
                ->get('/opportunities/'.$opportunity->id)
                ->assertOk()
                ->assertSee('Skill match is not available for this opportunity.')
                ->assertDontSee('100% Skill Match');
        } finally {
            $student->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createHubOpportunity(array $overrides): Opportunity
    {
        $attrs = array_merge([
            'title' => 'PF Hub Test Role',
            'organization' => 'PathForge Test Org',
            'type' => 'Internship',
            'description' => 'Student hub fixture.',
            'required_skills' => 'JavaScript',
            'eligibility' => 'Students welcome',
            'deadline' => now()->addMonth()->toDateString(),
            'application_url' => 'https://example.com/apply',
            'location' => 'Remote',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
        ], $overrides);

        $opportunity = new Opportunity;
        $opportunity->fill($attrs);
        $opportunity->save();

        if (array_key_exists('created_at', $overrides)) {
            $opportunity->forceFill([
                'created_at' => $overrides['created_at'],
                'updated_at' => $overrides['created_at'],
            ])->save();
        }

        return $opportunity;
    }
}
