<?php

namespace Tests\Feature;

use App\Models\CareerPathRequest;
use App\Models\LearningPath;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\RoadmapStep;
use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class WfsPolishPassTest extends TestCase
{
    protected function tearDown(): void
    {
        CareerPathRequest::query()->where('requested_path', 'like', 'PF Test %')->delete();
        Opportunity::query()->where('title', 'like', 'PF WFS %')->delete();
        Organization::query()->where('name', 'like', 'PF WFS %')->delete();
        User::query()->where('email', 'like', 'pf-wfs-%')->each(function (User $user) {
            $user->userProgress()->delete();
            $user->skills()->detach();
            $user->organizations()->detach();
            $user->delete();
        });
        parent::tearDown();
    }

    public function test_registration_rejects_numeric_names(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Nidhi123',
            'email' => 'pf-wfs-numeric-'.uniqid().'@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/register')->assertSessionHasErrors('name');

        $this->from('/register')->post('/register', [
            'name' => '123Nidhi',
            'email' => 'pf-wfs-leading-'.uniqid().'@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('name');

        $this->from('/register')->post('/register', [
            'name' => 'Nidhi@Nair',
            'email' => 'pf-wfs-symbol-'.uniqid().'@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('name');
    }

    public function test_registration_accepts_alphabetic_names_with_spaces(): void
    {
        $email = 'pf-wfs-name-'.uniqid().'@example.com';

        $this->post('/register', [
            'name' => 'Nidhi Nair',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('onboarding.show'));

        $user = User::query()->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Nidhi Nair', $user->name);
        $user->delete();
    }

    public function test_organization_opportunity_rejects_deadline_beyond_one_year(): void
    {
        [$organization, $owner] = $this->makeOrgOwner();
        $skill = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($skill);

        $this->actingAs($owner)
            ->from(route('organization.opportunities.create'))
            ->post(route('organization.opportunities.store'), $this->orgPayload($skill->id, [
                'title' => 'PF WFS Far Deadline',
                'deadline' => now()->addYears(1)->addDay()->toDateString(),
            ]))
            ->assertRedirect(route('organization.opportunities.create'))
            ->assertSessionHasErrors('deadline');
    }

    public function test_admin_manual_opportunity_rejects_deadline_beyond_two_years(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'pf-wfs-admin-'.uniqid().'@example.com']);

        $this->actingAs($admin)
            ->from('/admin/opportunities/create')
            ->post('/admin/opportunities', [
                'title' => 'PF WFS Admin Far Deadline',
                'organization' => 'PathForge',
                'type' => 'Hackathon',
                'description' => 'Too far.',
                'deadline' => now()->addYears(2)->addDay()->toDateString(),
                'application_url' => 'https://example.com/wfs-far',
            ])
            ->assertRedirect('/admin/opportunities/create')
            ->assertSessionHasErrors('deadline');

        $admin->delete();
    }

    public function test_organization_opportunity_accepts_valid_future_deadline(): void
    {
        [$organization, $owner] = $this->makeOrgOwner();
        $skill = Skill::query()->where('name', 'JavaScript')->first();

        $this->actingAs($owner)
            ->post(route('organization.opportunities.store'), $this->orgPayload($skill->id, [
                'title' => 'PF WFS Valid Deadline',
                'deadline' => now()->addMonths(6)->toDateString(),
                'intent' => 'submit',
            ]))
            ->assertRedirect(route('organization.opportunities.index'));

        $opportunity = Opportunity::query()->where('title', 'PF WFS Valid Deadline')->first();
        $this->assertNotNull($opportunity);
        $this->assertSame(Opportunity::APPROVAL_PENDING, $opportunity->approval_status);
    }

    public function test_organization_opportunity_can_have_zero_required_skills(): void
    {
        [$organization, $owner] = $this->makeOrgOwner();

        $this->actingAs($owner)
            ->post(route('organization.opportunities.store'), $this->orgPayload(0, [
                'title' => 'PF WFS Open Hackathon',
                'skill_ids' => [],
                'intent' => 'submit',
            ]))
            ->assertRedirect(route('organization.opportunities.index'))
            ->assertSessionDoesntHaveErrors();

        $opportunity = Opportunity::query()->where('title', 'PF WFS Open Hackathon')->first();
        $this->assertNotNull($opportunity);
        $this->assertSame(0, $opportunity->skills()->count());
        $this->assertTrue(blank($opportunity->required_skills));
    }

    public function test_no_specific_skill_required_option_stores_zero_skills(): void
    {
        [$organization, $owner] = $this->makeOrgOwner();
        $skill = Skill::query()->where('name', 'JavaScript')->first();

        $this->actingAs($owner)
            ->post(route('organization.opportunities.store'), $this->orgPayload($skill->id, [
                'title' => 'PF WFS Open Event',
                'no_specific_skill' => '1',
                'skill_ids' => [$skill->id],
                'intent' => 'submit',
            ]))
            ->assertRedirect(route('organization.opportunities.index'));

        $opportunity = Opportunity::query()->where('title', 'PF WFS Open Event')->first();
        $this->assertNotNull($opportunity);
        $this->assertSame(0, $opportunity->skills()->count());
    }

    public function test_student_can_submit_other_career_path_request(): void
    {
        $user = User::factory()->needsOnboarding()->create([
            'email' => 'pf-wfs-other-'.uniqid().'@example.com',
        ]);

        $this->actingAs($user)
            ->post('/onboarding/path', [
                'path_id' => 'other',
                'requested_path' => 'PF Test Machine Learning',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
        $this->assertNull($user->path_id);
        $this->assertDatabaseHas('career_path_requests', [
            'user_id' => $user->id,
            'requested_path' => 'PF Test Machine Learning',
            'status' => CareerPathRequest::STATUS_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('noted your career interest');
    }

    public function test_career_path_request_appears_in_admin(): void
    {
        $user = User::factory()->create(['email' => 'pf-wfs-req-'.uniqid().'@example.com']);
        CareerPathRequest::query()->create([
            'user_id' => $user->id,
            'requested_path' => 'PF Test Accounting',
            'status' => CareerPathRequest::STATUS_PENDING,
        ]);
        $admin = User::factory()->admin()->create(['email' => 'pf-wfs-admin2-'.uniqid().'@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.career-path-requests.index'))
            ->assertOk()
            ->assertSee('PF Test Accounting')
            ->assertSee($user->name);

        $this->actingAs($admin)
            ->post(route('admin.career-path-requests.review'), [
                'requested_path' => 'PF Test Accounting',
            ])
            ->assertRedirect(route('admin.career-path-requests.index'));

        $this->assertSame(
            CareerPathRequest::STATUS_REVIEWED,
            CareerPathRequest::query()->where('requested_path', 'PF Test Accounting')->value('status')
        );

        $admin->delete();
    }

    public function test_existing_onboarding_paths_still_work(): void
    {
        $user = User::factory()->needsOnboarding()->create([
            'email' => 'pf-wfs-onboard-'.uniqid().'@example.com',
        ]);
        $path = LearningPath::query()->where('path_name', 'Web Development')->first()
            ?? LearningPath::query()->orderBy('path_name')->first();
        $skill = Skill::query()->where('name', 'HTML')->first()
            ?? Skill::query()->orderBy('name')->first();
        $this->assertNotNull($path);
        $this->assertNotNull($skill);

        $this->actingAs($user)
            ->post('/onboarding/path', ['path_id' => $path->id])
            ->assertRedirect(route('onboarding.skills'));

        $this->actingAs($user)
            ->post('/onboarding/skills/starting', ['starting_as' => 'experienced'])
            ->assertRedirect(route('onboarding.skills'));

        $this->actingAs($user)
            ->post('/onboarding/skills/continue', ['skill_ids' => [$skill->id]])
            ->assertRedirect(route('onboarding.confirm'));

        $this->actingAs($user)
            ->post('/onboarding/confirm')
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertTrue($user->hasCompletedOnboarding());
        $this->assertSame((int) $path->id, (int) $user->path_id);
        $user->skills()->sync([]);
    }

    public function test_existing_roadmap_functionality_still_works(): void
    {
        $path = LearningPath::query()->has('roadmapSteps')->orderBy('path_name')->first();
        $this->assertNotNull($path);
        $step = $path->roadmapSteps()->orderBy('step_no')->first();
        $this->assertNotNull($step);

        $user = User::factory()->create([
            'email' => 'pf-wfs-roadmap-'.uniqid().'@example.com',
            'path_id' => $path->id,
            'onboarding_completed' => true,
        ]);

        $this->actingAs($user)
            ->get(route('roadmaps.show', $path))
            ->assertOk()
            ->assertSee($step->title);

        $this->actingAs($user)
            ->post(route('roadmaps.complete', [$path, $step]))
            ->assertRedirect();

        $this->assertDatabaseHas('user_progress', [
            'user_id' => $user->id,
            'roadmap_step_id' => $step->id,
            'status' => 'completed',
        ]);
    }

    public function test_existing_organization_workflow_still_works(): void
    {
        [$organization, $owner] = $this->makeOrgOwner();
        $admin = User::factory()->admin()->create(['email' => 'pf-wfs-org-admin-'.uniqid().'@example.com']);
        $student = User::factory()->create(['email' => 'pf-wfs-org-student-'.uniqid().'@example.com']);
        $skill = Skill::query()->where('name', 'JavaScript')->first();

        $this->actingAs($owner)
            ->post(route('organization.opportunities.store'), $this->orgPayload($skill->id, [
                'title' => 'PF WFS Org Workflow',
                'intent' => 'submit',
            ]))
            ->assertRedirect(route('organization.opportunities.index'));

        $opportunity = Opportunity::query()->where('title', 'PF WFS Org Workflow')->first();
        $this->assertNotNull($opportunity);

        $this->actingAs($admin)
            ->post(route('admin.opportunities.approve', $opportunity))
            ->assertRedirect(route('admin.opportunities.index'));

        $this->actingAs($student)
            ->get('/opportunities')
            ->assertSee('PF WFS Org Workflow');

        $admin->delete();
        $student->delete();
    }

    public function test_existing_ai_functionality_still_works(): void
    {
        $user = User::factory()->create(['email' => 'pf-wfs-ai-'.uniqid().'@example.com']);

        $this->actingAs($user)
            ->get('/ai-studio')
            ->assertOk()
            ->assertSee('AI Studio');

        $this->actingAs($user)
            ->postJson('/ai-studio/chat', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_admin_subscription_demo_section_is_available(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'pf-wfs-sub-'.uniqid().'@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.index'))
            ->assertOk()
            ->assertSee('Demonstration')
            ->assertSee('Total Subscribers')
            ->assertSee('Pro');

        $this->actingAs($admin)
            ->get(route('admin.subscriptions.show', 'demo-sub-1'))
            ->assertOk()
            ->assertSee('Payment Method')
            ->assertSee('Demo / Not Connected');

        $this->actingAs($admin)
            ->post(route('admin.subscriptions.upgrade', 'demo-sub-1'))
            ->assertRedirect(route('admin.subscriptions.show', 'demo-sub-1'))
            ->assertSessionHas('success');

        $admin->delete();
    }

    public function test_himalayas_deadline_beyond_manual_limit_is_not_rejected_on_admin_edit(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'pf-wfs-him-'.uniqid().'@example.com']);
        $far = now()->addYears(4)->toDateString();

        $opportunity = Opportunity::query()->create([
            'title' => 'PF WFS Himalayas Far',
            'organization' => 'Remote Co',
            'type' => 'Internship',
            'description' => 'Imported.',
            'application_url' => 'https://himalayas.app/jobs/wfs-far',
            'source' => Opportunity::SOURCE_HIMALAYAS,
            'external_id' => 'pf-wfs-'.uniqid(),
            'deadline' => $far,
            'approval_status' => Opportunity::APPROVAL_PENDING,
        ]);

        $this->actingAs($admin)
            ->put('/admin/opportunities/'.$opportunity->id, [
                'title' => 'PF WFS Himalayas Far',
                'organization' => 'Remote Co',
                'type' => 'Internship',
                'description' => 'Imported.',
                'application_url' => 'https://himalayas.app/jobs/wfs-far',
                'deadline' => $far,
            ])
            ->assertRedirect(route('admin.opportunities.index'))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($far, $opportunity->fresh()->deadline->toDateString());
        $admin->delete();
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function makeOrgOwner(): array
    {
        $suffix = substr(uniqid(), -8);
        $organization = Organization::factory()->create([
            'name' => 'PF WFS Org '.$suffix,
            'email' => 'pf-wfs-org-'.$suffix.'@example.com',
        ]);
        $owner = User::factory()->create([
            'email' => 'pf-wfs-owner-'.$suffix.'@example.com',
            'onboarding_completed' => true,
            'is_admin' => false,
        ]);
        $organization->users()->attach($owner->id, ['role' => Organization::ROLE_OWNER]);

        return [$organization, $owner->fresh()];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function orgPayload(int $skillId, array $overrides = []): array
    {
        $payload = [
            'title' => 'PF WFS Role',
            'type' => 'Internship',
            'description' => 'WFS polish listing.',
            'location' => 'Remote',
            'deadline' => now()->addMonth()->toDateString(),
            'application_url' => 'https://example.com/wfs-apply',
            'eligibility' => 'Students welcome',
            'intent' => 'draft',
        ];

        if ($skillId > 0 && ! array_key_exists('skill_ids', $overrides)) {
            $payload['skill_ids'] = [$skillId];
        }

        return array_merge($payload, $overrides);
    }
}
