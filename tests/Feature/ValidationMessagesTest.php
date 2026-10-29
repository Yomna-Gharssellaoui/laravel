<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\ComplianceCheck;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The French error messages listed in docs/VALEURS-DE-TEST.md.
 */
class ValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('fr');
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@traceverte.test')->firstOrFail();
    }

    private function errorsFor(string $method, string $url, array $data): array
    {
        $response = $this->actingAs($this->admin)->from('/back')->{$method}($url, $data);

        return collect(session('errors')?->getBag('default')->all() ?? [])->all();
    }

    public function test_messages_are_readable(): void
    {
        $tomates = Product::where('sku', 'TV-TOM-008')->value('id');
        $check = ComplianceCheck::first();

        $cases = [
            ['post', '/admin/users', ['name' => 'X', 'email' => 'admin@traceverte.test', 'role' => 'producer', 'password' => 'abc', 'password_confirmation' => 'abc'],
                ['La valeur du champ adresse email est déjà utilisée.', 'Le champ mot de passe doit contenir au moins 8 caractères.']],
            ['post', '/admin/environmental-footprints', ['product_id' => $tomates, 'co2_emissions' => -5],
                ['La valeur du champ émissions CO₂ doit être au moins 0.']],
            ['post', '/admin/certificates', ['product_id' => $tomates, 'certificate_type' => 'Bio', 'issue_date' => '2026-10-01', 'expiry_date' => '2026-09-01', 'status' => 'verified'],
                ['Le champ date d’expiration doit être une date postérieure ou égale à date d’émission.']],
            ['put', "/admin/compliance-checks/{$check->id}", ['environmental_claim_id' => $check->environmental_claim_id, 'check_type' => 'lab_test', 'status' => 'pending', 'checked_at' => '2099-01-01'],
                ['Le champ date de vérification doit être une date antérieure ou égale à aujourd’hui.']],
            ['post', '/admin/supply-chain-traces', ['product_id' => $tomates, 'status' => 'in_transit', 'steps' => [['stage' => 'transport', 'lat' => 36.8]]],
                ['Le champ longitude de l’étape est obligatoire quand latitude de l’étape est renseigné.']],
            ['post', '/admin/producers', ['company_name' => 'Ferme', 'latitude' => 95],
                ['La valeur du champ latitude doit être comprise entre -90 et 90.']],
            ['post', '/admin/products', ['name' => '', 'barcode' => '6191234000011', 'status' => 'draft'],
                ['Le champ nom est obligatoire.', 'La valeur du champ code-barres est déjà utilisée.']],
        ];

        foreach ($cases as [$method, $url, $data, $expected]) {
            $errors = $this->errorsFor($method, $url, $data);
            foreach ($expected as $message) {
                $this->assertContains($message, $errors, "$url: ".implode(' | ', $errors));
            }
        }

        $this->assertSame(1, Certificate::where('certificate_number', 'ECO-TN-2025-0187')->count());
    }
}
