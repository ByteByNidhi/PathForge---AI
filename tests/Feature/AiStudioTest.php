<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiStudioTest extends TestCase
{
    public function test_authenticated_user_can_open_ai_studio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/ai-studio')
            ->assertOk()
            ->assertSee('AI Studio')
            ->assertSee($user->name)
            ->assertDontSee(config('services.gemini.api_key') ?: 'GEMINI_API_KEY');

        $user->delete();
    }

    public function test_guest_cannot_access_ai_studio(): void
    {
        $this->get('/ai-studio')->assertRedirect('/login');
        $this->postJson('/ai-studio/chat', ['message' => 'What should I learn next?'])
            ->assertUnauthorized();
    }

    public function test_ai_request_validation_works(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/ai-studio/chat', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        $this->actingAs($user)
            ->postJson('/ai-studio/chat', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        $this->actingAs($user)
            ->postJson('/ai-studio/chat', ['message' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        $user->delete();
    }

    public function test_gemini_is_called_through_the_backend_without_exposing_the_api_key(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key-not-for-clients',
            'services.gemini.model' => 'gemini-3.6-flash',
            'services.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Complete your next roadmap step and practise that skill on a small project.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['name' => 'Studio Tester']);

        $response = $this->actingAs($user)
            ->postJson('/ai-studio/chat', [
                'message' => 'What should I learn next?',
                'name' => 'Spoofed Name',
                'path' => 'Secret Agent',
                'api_key' => 'client-forged-key',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'reply' => 'Complete your next roadmap step and practise that skill on a small project.',
            ]);

        $payload = $response->getContent();
        $this->assertStringNotContainsString('test-gemini-key-not-for-clients', $payload);
        $this->assertStringNotContainsString('client-forged-key', $payload);
        $this->assertStringNotContainsString('x-goog-api-key', $payload);

        Http::assertSent(function ($request) use ($user) {
            $url = $request->url();
            $body = $request->data();
            $system = (string) data_get($body, 'systemInstruction.parts.0.text');
            $userText = (string) data_get($body, 'contents.0.parts.0.text');

            return str_contains($url, 'generativelanguage.googleapis.com')
                && str_contains($url, 'gemini-3.6-flash')
                && ! str_contains($url, 'test-gemini-key-not-for-clients')
                && ! str_contains($url, 'key=')
                && $request->hasHeader('x-goog-api-key')
                && $request->header('x-goog-api-key')[0] === 'test-gemini-key-not-for-clients'
                && $userText === 'What should I learn next?'
                && str_contains($system, $user->name)
                && ! str_contains($system, 'Spoofed Name')
                && ! str_contains($system, 'Secret Agent');
        });

        $user->delete();
    }

    public function test_api_failure_is_handled_gracefully(): void
    {
        config([
            'services.gemini.api_key' => 'test-gemini-key-not-for-clients',
            'services.gemini.model' => 'gemini-3.6-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => ['message' => 'internal provider error with secret details'],
            ], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/ai-studio/chat', [
                'message' => 'Review my current progress.',
            ]);

        $response->assertStatus(503)
            ->assertJsonFragment([
                'message' => 'The career assistant is busy or unavailable. Please try again shortly.',
            ]);

        $this->assertStringNotContainsString('internal provider error', $response->getContent());
        $this->assertStringNotContainsString('test-gemini-key-not-for-clients', $response->getContent());

        $user->delete();
    }

    public function test_missing_api_key_is_handled_gracefully(): void
    {
        config(['services.gemini.api_key' => '']);

        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/ai-studio/chat', ['message' => 'How can I improve my skills?'])
            ->assertStatus(503)
            ->assertJsonFragment([
                'message' => 'The career assistant is not available right now. Please try again later.',
            ]);

        Http::assertNothingSent();

        $user->delete();
    }
}
