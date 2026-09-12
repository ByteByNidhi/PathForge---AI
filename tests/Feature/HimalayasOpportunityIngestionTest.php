<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use App\Services\HimalayasJobService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HimalayasOpportunityIngestionTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->deleteTestImports();
        parent::tearDown();
    }

    public function test_successful_himalayas_fetch_imports_pending_jobs(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeHimalayasResponse([$this->sampleJob()]);

        try {
            $this->actingAs($admin)
                ->post(route('admin.opportunities.fetch'), [
                    'q' => 'javascript',
                ])
                ->assertRedirect(route('admin.opportunities.index'))
                ->assertSessionHas('success');

            $opportunity = Opportunity::query()
                ->where('external_id', 'pf-test-guid-js')
                ->first();

            $this->assertNotNull($opportunity);
            $this->assertSame('JavaScript Developer', $opportunity->title);
            $this->assertSame('Acme Labs', $opportunity->organization);
            $this->assertSame('https://himalayas.app/companies/acme/jobs/javascript-dev', $opportunity->application_url);
            $this->assertSame(HimalayasJobService::SOURCE, $opportunity->source);
            $this->assertSame('pf-test-guid-js', $opportunity->external_id);
            $this->assertSame(Opportunity::APPROVAL_PENDING, $opportunity->approval_status);

            $javascript = Skill::query()->where('name', 'JavaScript')->first();
            $this->assertNotNull($javascript);
            $this->assertTrue($opportunity->skills()->where('skills.id', $javascript->id)->exists());
            $this->assertStringContainsString('JavaScript', (string) $opportunity->required_skills);
            $this->assertFalse($opportunity->skills()->where('name', 'Cybersecurity')->exists());
        } finally {
            $admin->delete();
        }
    }

    public function test_duplicate_guid_is_not_imported_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeHimalayasResponse([$this->sampleJob()]);

        try {
            $this->actingAs($admin)->post(route('admin.opportunities.fetch'), ['q' => 'javascript']);
            $this->actingAs($admin)
                ->post(route('admin.opportunities.fetch'), ['q' => 'javascript'])
                ->assertRedirect(route('admin.opportunities.index'));

            $this->assertSame(1, Opportunity::query()->where('external_id', 'pf-test-guid-js')->count());
        } finally {
            $admin->delete();
        }
    }

    public function test_learning_path_search_uses_path_name_and_skills(): void
    {
        $admin = User::factory()->admin()->create();
        $path = LearningPath::query()->where('path_name', 'Web Development')->first();
        $this->assertNotNull($path);

        Http::fake(function ($request) {
            $this->assertStringContainsString('himalayas.app/jobs/api/search', $request->url());
            $this->assertStringContainsString('q=', $request->url());

            return Http::response([
                'jobs' => [$this->sampleJob('pf-test-guid-path', 'Frontend JavaScript Engineer')],
            ], 200);
        });

        try {
            $this->actingAs($admin)
                ->post(route('admin.opportunities.fetch'), [
                    'learning_path_id' => $path->id,
                ])
                ->assertRedirect(route('admin.opportunities.index'))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('opportunities', [
                'external_id' => 'pf-test-guid-path',
                'title' => 'Frontend JavaScript Engineer',
                'approval_status' => Opportunity::APPROVAL_PENDING,
            ]);
        } finally {
            $admin->delete();
        }
    }

    public function test_api_failure_returns_a_friendly_admin_error(): void
    {
        $admin = User::factory()->admin()->create();
        Http::fake([
            'himalayas.app/jobs/api/search*' => Http::response(['error' => 'nope'], 500),
        ]);

        try {
            $before = Opportunity::query()->count();

            $this->actingAs($admin)
                ->post(route('admin.opportunities.fetch'), ['q' => 'javascript'])
                ->assertRedirect(route('admin.opportunities.index'))
                ->assertSessionHas('error', 'Himalayas is unavailable right now. Please try again shortly.');

            $this->assertSame($before, Opportunity::query()->count());
        } finally {
            $admin->delete();
        }
    }

    public function test_rate_limit_returns_a_friendly_admin_error(): void
    {
        $admin = User::factory()->admin()->create();
        Http::fake([
            'himalayas.app/jobs/api/search*' => Http::response('Too Many Requests', 429),
        ]);

        try {
            $this->actingAs($admin)
                ->post(route('admin.opportunities.fetch'), ['q' => 'javascript'])
                ->assertRedirect(route('admin.opportunities.index'))
                ->assertSessionHas('error', 'Himalayas is rate-limiting requests right now. Please wait a minute and try again.');
        } finally {
            $admin->delete();
        }
    }

    public function test_students_see_approved_but_not_pending_or_rejected_or_expired_imports(): void
    {
        $student = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $javascript = Skill::query()->where('name', 'JavaScript')->first();
        $this->assertNotNull($javascript);

        $pending = Opportunity::query()->create($this->opportunityAttrs([
            'title' => 'PF Test Pending JS Role',
            'external_id' => 'pf-test-pending',
            'approval_status' => Opportunity::APPROVAL_PENDING,
            'deadline' => now()->addMonth()->toDateString(),
        ]));

        $rejected = Opportunity::query()->create($this->opportunityAttrs([
            'title' => 'PF Test Rejected JS Role',
            'external_id' => 'pf-test-rejected',
            'approval_status' => Opportunity::APPROVAL_REJECTED,
            'deadline' => now()->addMonth()->toDateString(),
        ]));

        $expired = Opportunity::query()->create($this->opportunityAttrs([
            'title' => 'PF Test Expired JS Role',
            'external_id' => 'pf-test-expired',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'deadline' => now()->subDay()->toDateString(),
        ]));

        $approved = Opportunity::query()->create($this->opportunityAttrs([
            'title' => 'PF Test Approved JS Role',
            'external_id' => 'pf-test-approved',
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'deadline' => now()->addMonth()->toDateString(),
        ]));

        try {
            $this->actingAs($student)
                ->get('/opportunities')
                ->assertOk()
                ->assertSee('PF Test Approved JS Role')
                ->assertSee('Himalayas')
                ->assertDontSee('PF Test Pending JS Role')
                ->assertDontSee('PF Test Rejected JS Role')
                ->assertDontSee('PF Test Expired JS Role');

            $this->actingAs($student)
                ->get('/opportunities/'.$pending->id)
                ->assertNotFound();

            $this->actingAs($student)
                ->get('/opportunities/'.$rejected->id)
                ->assertNotFound();

            $this->actingAs($student)
                ->get('/opportunities/'.$expired->id)
                ->assertNotFound();

            $this->actingAs($student)
                ->get('/opportunities/'.$approved->id)
                ->assertOk()
                ->assertSee('PF Test Approved JS Role')
                ->assertSee('Himalayas')
                ->assertSee($approved->application_url, false);

            $this->actingAs($admin)
                ->post(route('admin.opportunities.approve', $pending))
                ->assertRedirect(route('admin.opportunities.index'));

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertSee('PF Test Pending JS Role');

            $this->actingAs($admin)
                ->post(route('admin.opportunities.reject', $pending))
                ->assertRedirect(route('admin.opportunities.index'));

            $this->actingAs($student)
                ->get('/opportunities')
                ->assertDontSee('PF Test Pending JS Role');
        } finally {
            $student->delete();
            $admin->delete();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $jobs
     */
    private function fakeHimalayasResponse(array $jobs): void
    {
        Http::fake([
            'himalayas.app/jobs/api/search*' => Http::response([
                'jobs' => $jobs,
            ], 200),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleJob(string $guid = 'pf-test-guid-js', string $title = 'JavaScript Developer'): array
    {
        return [
            'guid' => $guid,
            'title' => $title,
            'excerpt' => 'A remote JavaScript role using React.',
            'companyName' => 'Acme Labs',
            'employmentType' => 'Full Time',
            'minSalary' => 50000,
            'maxSalary' => 70000,
            'salaryPeriod' => 'annual',
            'seniority' => ['Mid-level'],
            'currency' => 'USD',
            'locationRestrictions' => ['India'],
            'categories' => ['JavaScript-Developer', 'React'],
            'parentCategories' => ['Developer'],
            'description' => 'Build product features with JavaScript and React.',
            'pubDate' => now()->timestamp,
            'expiryDate' => now()->addMonth()->timestamp,
            'applicationLink' => 'https://himalayas.app/companies/acme/jobs/javascript-dev',
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function opportunityAttrs(array $overrides): array
    {
        return array_merge([
            'organization' => 'Acme Labs',
            'type' => 'Internship',
            'description' => 'Test imported role.',
            'required_skills' => 'JavaScript',
            'eligibility' => 'Students welcome',
            'application_url' => 'https://himalayas.app/companies/acme/jobs/'.$overrides['external_id'],
            'location' => 'Remote',
            'source' => HimalayasJobService::SOURCE,
            'source_url' => 'https://himalayas.app/companies/acme/jobs/'.$overrides['external_id'],
        ], $overrides);
    }

    private function deleteTestImports(): void
    {
        Opportunity::query()
            ->where('source', HimalayasJobService::SOURCE)
            ->where(function ($query) {
                $query->where('external_id', 'like', 'pf-test-%')
                    ->orWhere('title', 'like', 'PF Test %');
            })
            ->delete();
    }
}
