<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        // Dans cette app, `/register` est une route legacy qui redirige vers l'onboarding multi-étapes.
        $response->assertStatus(302);
        $response->assertRedirect(route('onboarding.step1', absolute: false));
    }

    public function test_new_users_can_register(): void
    {
        // L'inscription complète se fait en plusieurs étapes. On teste ici l'étape 1.
        $response = $this->post(route('onboarding.step1.process', absolute: false), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('onboarding.step2', absolute: false));
    }
}
