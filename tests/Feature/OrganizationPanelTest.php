<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Skill;
use App\Models\User;
use Tests\TestCase;

class OrganizationPanelTest extends TestCase
{
    protected function tearDown(): void
    {
        Opportunity::query()->where('title', 'like', 'PF Org Test %')->delete();
        Organization::query()->where('name', 'like', 'PF Org Test %')->delete();
        User::query()->where('email', 'like', 'pf-org-test-%')->delete();
        parent::tearDown();
    }

    public function test_organization_user_can_access_dashboard_and_student_cannot(): void
    {
        [$organization, $owner] = $this->makeOrganizationOwner();
        $student = User::factory()->create(['email' => 'pf-org-test-student-'.$this->uid().'@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'pf-org-test-admin-'.$this->uid().'@example.com']);

        try {
            $this->actingAs($owner)
                ->get(route('organization.dashboard'))
                ->assertOk()
                ->assertSee($organization->name)
                ->assertSee('Total')
                ->assertSee('Draft');

            $this->actingAs($student)
                ->get(route('organization.dashboard'))
                ->assertForbidden();

            $this->actingAs($admin)
                ->get('/admin')
                ->assertOk()
                ->assertSee('Admin Dashboard');
        } finally {
            $this->cleanupUsers($student, $admin, $owner);
            $organization->delete();
        }
    }

    public function test_organization_can_create_edit_delete_and_submit_a_draft(): void
    {
        [$organization, $owner] = $this->makeOrganizationOwner();
        $student = User::factory()->create(['email' => 'pf-org-test-student-'.$this->uid().'@example.com']);
        $skill = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($skill);

        try {
            $this->actingAs($owner)
                ->post(route('organization.opportunities.store'), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Draft Role',
                    'intent' => 'draft',
                ]))
                ->assertRedirect(route('organization.opportunities.index'));

            $opportunity = Opportunity::query()->where('title', 'PF Org Test Draft Role')->first();
            $this->assertNotNull($opportunity);
            $this->assertSame(Opportunity::APPROVAL_DRAFT, $opportunity->approval_status);
            $this->assertSame($organization->id, $opportunity->organization_id);
            $this->assertSame(Opportunity::SOURCE_ORGANIZATION, $opportunity->source);
            $this->assertTrue($opportunity->skills()->where('skills.id', $skill->id)->exists());

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertOk()
                ->assertDontSee('PF Org Test Draft Role');

            $this->actingAs($owner)
                ->put(route('organization.opportunities.update', $opportunity), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Draft Role Edited',
                    'intent' => 'draft',
                ]))
                ->assertRedirect(route('organization.opportunities.index'));

            $this->assertSame('PF Org Test Draft Role Edited', $opportunity->fresh()->title);

            $this->actingAs($owner)
                ->post(route('organization.opportunities.submit', $opportunity->fresh()))
                ->assertRedirect(route('organization.opportunities.index'));

            $this->assertSame(Opportunity::APPROVAL_PENDING, $opportunity->fresh()->approval_status);

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertDontSee('PF Org Test Draft Role Edited');
        } finally {
            Opportunity::query()->where('title', 'like', 'PF Org Test Draft Role%')->delete();
            $this->cleanupUsers($student, $owner);
            $organization->delete();
        }
    }

    public function test_organization_can_delete_its_own_draft(): void
    {
        [$organization, $owner] = $this->makeOrganizationOwner();
        $skill = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($skill);

        try {
            $this->actingAs($owner)
                ->post(route('organization.opportunities.store'), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Delete Draft',
                    'intent' => 'draft',
                ]));

            $opportunity = Opportunity::query()->where('title', 'PF Org Test Delete Draft')->first();
            $this->assertNotNull($opportunity);

            $this->actingAs($owner)
                ->delete(route('organization.opportunities.destroy', $opportunity))
                ->assertRedirect(route('organization.opportunities.index'));

            $this->assertNull(Opportunity::query()->find($opportunity->id));
        } finally {
            $this->cleanupUsers($owner);
            $organization->delete();
        }
    }

    public function test_admin_can_approve_and_reject_organization_opportunities(): void
    {
        [$organization, $owner] = $this->makeOrganizationOwner();
        $student = User::factory()->create(['email' => 'pf-org-test-student-'.$this->uid().'@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'pf-org-test-admin-'.$this->uid().'@example.com']);
        $javascript = Skill::query()->where('name', 'JavaScript')->first();
        $html = Skill::query()->where('name', 'HTML')->first();
        $this->assertNotNull($javascript);
        $this->assertNotNull($html);
        $student->skills()->sync([$javascript->id]);

        try {
            $this->actingAs($owner)
                ->post(route('organization.opportunities.store'), $this->opportunityPayload($javascript->id, [
                    'title' => 'PF Org Test Pending Approve',
                    'intent' => 'submit',
                    'application_url' => 'https://example.com/org-apply-approve',
                    'skill_ids' => [$javascript->id, $javascript->id, $html->id],
                ]));

            $opportunity = Opportunity::query()->where('title', 'PF Org Test Pending Approve')->first();
            $this->assertNotNull($opportunity);
            $this->assertSame(Opportunity::APPROVAL_PENDING, $opportunity->approval_status);
            $this->assertSame(2, $opportunity->skills()->count());
            $this->assertSame('https://example.com/org-apply-approve', $opportunity->application_url);

            $this->actingAs($admin)
                ->get(route('admin.opportunities.index'))
                ->assertOk()
                ->assertSee('PF Org Test Pending Approve')
                ->assertSee('Organization');

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertDontSee('PF Org Test Pending Approve');

            $this->actingAs($admin)
                ->post(route('admin.opportunities.approve', $opportunity))
                ->assertRedirect(route('admin.opportunities.index'));

            $this->assertSame(Opportunity::APPROVAL_APPROVED, $opportunity->fresh()->approval_status);

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertSee('PF Org Test Pending Approve');

            $this->actingAs($student)
                ->get('/opportunities/'.$opportunity->id)
                ->assertOk()
                ->assertSee('https://example.com/org-apply-approve', false)
                ->assertSee('Skill Match');
        } finally {
            Opportunity::query()->where('title', 'PF Org Test Pending Approve')->delete();
            $this->cleanupUsers($student, $admin, $owner);
            $organization->delete();
        }
    }

    public function test_rejected_opportunity_is_hidden_and_can_be_resubmitted(): void
    {
        [$organization, $owner] = $this->makeOrganizationOwner();
        $student = User::factory()->create(['email' => 'pf-org-test-student-'.$this->uid().'@example.com']);
        $admin = User::factory()->admin()->create(['email' => 'pf-org-test-admin-'.$this->uid().'@example.com']);
        $skill = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($skill);

        try {
            $this->actingAs($owner)
                ->post(route('organization.opportunities.store'), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Rejected Role',
                    'intent' => 'submit',
                ]));

            $opportunity = Opportunity::query()->where('title', 'PF Org Test Rejected Role')->first();
            $this->assertNotNull($opportunity);

            $this->actingAs($admin)
                ->post(route('admin.opportunities.reject', $opportunity), [
                    'rejection_reason' => 'Needs a clearer description.',
                ])
                ->assertRedirect(route('admin.opportunities.index'));

            $this->assertSame(Opportunity::APPROVAL_REJECTED, $opportunity->fresh()->approval_status);
            $this->assertSame('Needs a clearer description.', $opportunity->fresh()->rejection_reason);

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertDontSee('PF Org Test Rejected Role');

            $this->actingAs($owner)
                ->get(route('organization.opportunities.show', $opportunity))
                ->assertOk()
                ->assertSee('Needs a clearer description.');

            $this->actingAs($owner)
                ->put(route('organization.opportunities.update', $opportunity), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Rejected Role',
                    'description' => 'Updated description after rejection.',
                    'intent' => 'submit',
                ]))
                ->assertRedirect(route('organization.opportunities.index'));

            $fresh = $opportunity->fresh();
            $this->assertSame(Opportunity::APPROVAL_PENDING, $fresh->approval_status);
            $this->assertNull($fresh->rejection_reason);
        } finally {
            Opportunity::query()->where('title', 'PF Org Test Rejected Role')->delete();
            $this->cleanupUsers($student, $admin, $owner);
            $organization->delete();
        }
    }

    public function test_organization_cannot_access_another_org_or_approve_its_own_listings(): void
    {
        [$orgA, $ownerA] = $this->makeOrganizationOwner('PF Org Test Alpha');
        [$orgB, $ownerB] = $this->makeOrganizationOwner('PF Org Test Beta');
        $skill = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($skill);

        try {
            $this->actingAs($ownerB)
                ->post(route('organization.opportunities.store'), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Beta Secret',
                    'intent' => 'draft',
                ]));

            $bOpportunity = Opportunity::query()->where('title', 'PF Org Test Beta Secret')->first();
            $this->assertNotNull($bOpportunity);

            $this->actingAs($ownerA)
                ->get(route('organization.opportunities.show', $bOpportunity))
                ->assertForbidden();

            $this->actingAs($ownerA)
                ->put(route('organization.opportunities.update', $bOpportunity), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Hijacked',
                ]))
                ->assertForbidden();

            $this->actingAs($ownerA)
                ->post(route('organization.opportunities.submit', $bOpportunity))
                ->assertForbidden();

            $this->actingAs($ownerA)
                ->delete(route('organization.opportunities.destroy', $bOpportunity))
                ->assertForbidden();

            $this->actingAs($ownerA)
                ->post(route('admin.opportunities.approve', $bOpportunity))
                ->assertForbidden();

            $this->actingAs($ownerB)
                ->put(route('organization.opportunities.update', $bOpportunity), $this->opportunityPayload($skill->id, [
                    'title' => 'PF Org Test Beta Secret',
                    'intent' => 'draft',
                    'approval_status' => Opportunity::APPROVAL_APPROVED,
                    'organization_id' => $orgA->id,
                ]))
                ->assertSessionHasErrors(['approval_status', 'organization_id']);

            $fresh = $bOpportunity->fresh();
            $this->assertSame(Opportunity::APPROVAL_DRAFT, $fresh->approval_status);
            $this->assertSame($orgB->id, $fresh->organization_id);
        } finally {
            Opportunity::query()->where('title', 'like', 'PF Org Test %')->delete();
            $this->cleanupUsers($ownerA, $ownerB);
            $orgA->delete();
            $orgB->delete();
        }
    }

    public function test_existing_manual_and_himalayas_style_opportunities_remain_visible(): void
    {
        $student = User::factory()->create(['email' => 'pf-org-test-student-'.$this->uid().'@example.com']);

        $manual = Opportunity::query()->create([
            'title' => 'PF Org Test Manual Listing',
            'organization' => 'PathForge Test Org',
            'type' => 'Internship',
            'description' => 'Manual listing still works.',
            'required_skills' => 'Git',
            'application_url' => 'https://example.com/manual-apply',
            'location' => 'Remote',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
        ]);

        $himalayas = Opportunity::query()->create([
            'title' => 'PF Org Test Himalayas Listing',
            'organization' => 'Acme Labs',
            'type' => 'Internship',
            'description' => 'Imported listing still works.',
            'required_skills' => 'JavaScript',
            'application_url' => 'https://himalayas.app/companies/acme/jobs/org-compat',
            'location' => 'Remote',
            'source' => Opportunity::SOURCE_HIMALAYAS,
            'external_id' => 'pf-org-test-himalayas-'.$this->uid(),
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'deadline' => now()->addMonth()->toDateString(),
        ]);

        try {
            $this->actingAs($student)
                ->get('/opportunities')
                ->assertOk()
                ->assertSee('PF Org Test Manual Listing')
                ->assertSee('PF Org Test Himalayas Listing');
        } finally {
            $manual->delete();
            $himalayas->delete();
            $this->cleanupUsers($student);
        }
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function makeOrganizationOwner(string $name = 'PF Org Test Collective'): array
    {
        $suffix = $this->uid();
        $organization = Organization::factory()->create([
            'name' => $name.' '.$suffix,
            'email' => 'pf-org-test-org-'.$suffix.'@example.com',
        ]);

        $owner = User::factory()->create([
            'email' => 'pf-org-test-owner-'.$suffix.'@example.com',
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
    private function opportunityPayload(int $skillId, array $overrides = []): array
    {
        return array_merge([
            'title' => 'PF Org Test Role',
            'type' => 'Internship',
            'description' => 'Organization-created listing for tests.',
            'location' => 'Remote',
            'deadline' => now()->addMonth()->toDateString(),
            'application_url' => 'https://example.com/org-apply',
            'eligibility' => 'Students welcome',
            'skill_ids' => [$skillId],
            'intent' => 'draft',
        ], $overrides);
    }

    private function uid(): string
    {
        return substr(uniqid(), -8);
    }

    private function cleanupUsers(User ...$users): void
    {
        foreach ($users as $user) {
            $user->organizations()->detach();
            $user->skills()->detach();
            $user->delete();
        }
    }
}
