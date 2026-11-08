<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Les réglages de l'agent suivent son compte d'un appareil à l'autre (étape 3 du portail),
 * et l'adresse du serveur reste à l'appareil.
 */
class ApiPreferenceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_null_for_an_account_that_changed_nothing(): void
    {
        $user = $this->actingAsNewUser();

        $this->getJson('/api/preferences')
            ->assertOk()
            ->assertJson(['preferences' => null]);

        $this->assertNull($user->fresh()->preferences);
    }

    public function test_it_stores_and_returns_the_sent_snapshot(): void
    {
        $user = $this->actingAsNewUser();

        $payload = ['lang' => 'en', 'font_scale' => 'lg', 'font_family' => 'verdana', 'theme' => 'dark'];

        $this->putJson('/api/preferences', ['preferences' => $payload])
            ->assertOk()
            ->assertJson(['preferences' => $payload]);

        $this->assertSame($payload, $user->fresh()->preferences);

        $this->getJson('/api/preferences')
            ->assertOk()
            ->assertJson(['preferences' => $payload]);
    }

    public function test_it_replaces_instead_of_merging(): void
    {
        $user = $this->actingAsNewUser();

        $this->putJson('/api/preferences', [
            'preferences' => ['lang' => 'en', 'font_scale' => 'lg', 'font_family' => 'arial'],
        ])->assertOk();

        // Un réglage retiré du portail ne doit pas ressurgir à la connexion suivante.
        $this->putJson('/api/preferences', [
            'preferences' => ['lang' => 'fr'],
        ])->assertOk()->assertJson(['preferences' => ['lang' => 'fr']]);

        $this->assertSame(['lang' => 'fr'], $user->fresh()->preferences);
    }

    public function test_it_refuses_a_value_outside_the_allowed_list(): void
    {
        $user = $this->actingAsNewUser();

        $this->putJson('/api/preferences', [
            'preferences' => ['lang' => 'fr', 'font_scale' => 'huge'],
        ])->assertStatus(422)->assertJsonValidationErrors('preferences.font_scale');

        $this->putJson('/api/preferences', [
            'preferences' => ['lang' => 'fr', 'theme' => 'night'],
        ])->assertStatus(422)->assertJsonValidationErrors('preferences.theme');

        $this->assertNull($user->fresh()->preferences);
    }

    public function test_it_ignores_keys_it_does_not_know(): void
    {
        $user = $this->actingAsNewUser();

        // Une clé que l'API ne déclare pas — ici un réglage qui n'existe pas encore de son
        // côté — ne doit pas faire échouer la requête ni être stockée pour autant.
        $this->putJson('/api/preferences', [
            'preferences' => [
                'lang' => 'en',
                'theme' => 'dark',
                'dashboard_density' => 'compact',
                'api_base' => 'http://116.72.30.12:8084/api',
            ],
        ])->assertOk()->assertJson(['preferences' => ['lang' => 'en', 'theme' => 'dark']]);

        $stored = $user->fresh()->preferences;

        $this->assertSame(['lang' => 'en', 'theme' => 'dark'], $stored);
        $this->assertArrayNotHasKey('dashboard_density', $stored);
        // L'adresse du serveur dépend du réseau de l'appareil, jamais du compte.
        $this->assertArrayNotHasKey('api_base', $stored);
    }

    public function test_it_requires_a_signed_in_account(): void
    {
        $this->getJson('/api/preferences')->assertUnauthorized();
        $this->putJson('/api/preferences', ['preferences' => ['lang' => 'fr']])->assertUnauthorized();
    }

    /**
     * Un changement de langue ou de police n'est pas un événement RH : le journal d'audit
     * serait rempli de tracasseries d'apparence.
     */
    public function test_it_does_not_fill_the_audit_journal(): void
    {
        $user = $this->actingAsNewUser();

        // La création du compte est elle-même consignée : on ne regarde que les mises à jour.
        $updated = fn() => DB::table('activity_logs')
            ->where('subject_type', $user->getMorphClass())
            ->where('action', 'updated')
            ->count();

        $this->putJson('/api/preferences', ['preferences' => ['lang' => 'en', 'font_scale' => 'sm']])->assertOk();

        $this->assertSame(0, $updated());

        // Une vraie modification du compte reste consignée.
        $user->update(['name' => 'NOM CHANGÉ']);

        $this->assertSame(1, $updated());
    }

    private function actingAsNewUser(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
