<?php

namespace Tests\Feature;

use App\Models\LearningPath;
use App\Models\Opportunity;
use App\Models\RoadmapStep;
use App\Models\User;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    public function test_admin_is_redirected_to_admin_after_login(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'password',
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/admin');

        $admin->delete();
    }

    public function test_normal_user_is_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'is_admin' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $user->delete();
    }

    public function test_guests_are_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_normal_users_cannot_access_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/opportunities')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/roadmaps')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();

        $user->delete();
    }

    public function test_admin_can_open_the_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('Total users')
            ->assertSee('Total opportunities')
            ->assertSee('Career paths')
            ->assertSee('Roadmap steps');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($admin->email);

        $this->actingAs($admin)
            ->get('/admin/users/'.$admin->id)
            ->assertOk()
            ->assertSee($admin->email)
            ->assertSee('XP')
            ->assertSee('Level');

        $admin->delete();
    }

    public function test_admin_can_manage_opportunities(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $payload = [
            'title' => 'Admin Test Opportunity '.uniqid(),
            'organization' => 'PathForge Test Org',
            'type' => 'Hackathon',
            'description' => 'Created from the admin panel test.',
            'required_skills' => 'Python, Git',
            'eligibility' => 'College students',
            'deadline' => now()->addMonth()->toDateString(),
            'application_url' => 'https://example.com/apply-admin-test',
            'location' => 'Remote',
        ];

        $this->actingAs($admin)
            ->post('/admin/opportunities', $payload)
            ->assertRedirect(route('admin.opportunities.index'));

        $opportunity = Opportunity::query()->where('title', $payload['title'])->first();
        $this->assertNotNull($opportunity);
        $this->assertSame('https://example.com/apply-admin-test', $opportunity->application_url);

        $this->actingAs($student)
            ->get('/opportunities')
            ->assertOk()
            ->assertSee($payload['title']);

        $this->actingAs($admin)
            ->from('/admin/opportunities/create')
            ->post('/admin/opportunities', [
                'title' => '',
                'organization' => '',
                'type' => 'Invalid',
                'application_url' => 'not-a-url',
            ])
            ->assertRedirect('/admin/opportunities/create')
            ->assertSessionHasErrors(['title', 'organization', 'type', 'application_url']);

        $this->actingAs($admin)
            ->put('/admin/opportunities/'.$opportunity->id, array_merge($payload, [
                'title' => $payload['title'].' Updated',
                'location' => 'Bengaluru',
            ]))
            ->assertRedirect(route('admin.opportunities.index'));

        $this->actingAs($student)
            ->get('/opportunities/'.$opportunity->id)
            ->assertOk()
            ->assertSee($payload['title'].' Updated')
            ->assertSee('Bengaluru');

        $this->actingAs($admin)
            ->delete('/admin/opportunities/'.$opportunity->id)
            ->assertRedirect(route('admin.opportunities.index'));

        $this->assertNull(Opportunity::query()->find($opportunity->id));

        $admin->delete();
        $student->delete();
    }

    public function test_admin_can_manage_roadmap_steps_without_breaking_the_user_roadmap(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();
        $path = LearningPath::query()->orderBy('path_name')->first();
        $this->assertNotNull($path, 'At least one career path is required.');

        $this->actingAs($admin)
            ->get('/admin/roadmaps')
            ->assertOk()
            ->assertSee($path->path_name);

        $stepNo = ((int) $path->roadmapSteps()->max('step_no')) + 50;
        $title = 'Admin Test Step '.uniqid();

        $this->actingAs($admin)
            ->post('/admin/roadmaps/'.$path->id.'/steps', [
                'step_no' => $stepNo,
                'title' => $title,
                'xp_reward' => 15,
            ])
            ->assertRedirect(route('admin.roadmaps.show', $path));

        $step = RoadmapStep::query()
            ->where('path_id', $path->id)
            ->where('title', $title)
            ->first();
        $this->assertNotNull($step);

        $this->actingAs($student)
            ->get('/roadmaps/'.$path->id)
            ->assertOk()
            ->assertSee($title);

        $this->actingAs($admin)
            ->put('/admin/roadmaps/'.$path->id.'/steps/'.$step->id, [
                'step_no' => $stepNo,
                'title' => $title.' Edited',
                'xp_reward' => 20,
            ])
            ->assertRedirect(route('admin.roadmaps.show', $path));

        $this->actingAs($student)
            ->get('/roadmaps/'.$path->id)
            ->assertOk()
            ->assertSee($title.' Edited');

        $this->actingAs($admin)
            ->delete('/admin/roadmaps/'.$path->id.'/steps/'.$step->id)
            ->assertRedirect(route('admin.roadmaps.show', $path));

        $this->assertNull(RoadmapStep::query()->find($step->id));

        $this->actingAs($student)
            ->get('/roadmaps')
            ->assertOk()
            ->assertSee('Learning roadmaps');

        $admin->delete();
        $student->delete();
    }
}
