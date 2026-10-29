<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\ComplianceCheck;
use App\Models\Consumer;
use App\Models\Distributor;
use App\Models\EnvironmentalClaim;
use App\Models\EnvironmentalFootprint;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupplyChainTrace;
use App\Models\Transformer;
use App\Models\User;
use App\Services\GreenwashingAnalyzer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Coherent, deterministic demo dataset (Tunisian food products) covering every
 * CRUD module and every business rule worth demonstrating:
 * expired / expiring / pending certificates, failed compliance check, vague claims,
 * delayed shipment, high footprint, allergen, reviews to moderate, blocked and
 * unverified accounts. Every account uses the password "password".
 *
 * See docs/GUIDE-DE-TEST.md for the test scenarios built on this data.
 */
class TraceVerteDatasetSeeder extends Seeder
{
    private const CITIES = [
        'Sfax' => [34.7406, 10.7603],
        'Sousse' => [35.8256, 10.6084],
        'Tunis' => [36.8065, 10.1815],
        'Tozeur' => [33.9197, 8.1335],
        'Gabès' => [33.8815, 10.0982],
        'Nabeul' => [36.4561, 10.7376],
        'Béja' => [36.7256, 9.1817],
        'Bizerte' => [37.2744, 9.8739],
        'Mateur' => [37.0400, 9.6650],
    ];

    public function run(): void
    {
        $users = $this->users();
        [$producers, $transformers, $distributors] = $this->actors($users);
        $products = $this->products($producers, $transformers);

        $this->footprints($products);
        $this->certificates($products);
        $this->traces($products, $distributors);
        $claims = $this->claimsAndChecks($products);
        $this->consumersAndReviews($users, $products);

        // Greenwashing analyses with the local heuristic (no API call while seeding)
        $keys = ['services.anthropic.api_key' => config('services.anthropic.api_key'), 'services.gemini.api_key' => config('services.gemini.api_key')];
        config(array_fill_keys(array_keys($keys), null));
        foreach ($claims as $claim) {
            app(GreenwashingAnalyzer::class)->analyzeClaim($claim->fresh());
        }
        config($keys);
    }

    /**
     * @return array<string, User>
     */
    private function users(): array
    {
        $accounts = [
            'admin' => ['Amira Ben Salah (admin)', 'admin@traceverte.test', 'admin'],
            'producer' => ['Mohamed Trabelsi', 'producer@traceverte.test', 'producer'],
            'producer2' => ['Leila Haddad', 'producer2@traceverte.test', 'producer'],
            'transformer' => ['Sami Gharbi', 'transformer@traceverte.test', 'transformer'],
            'distributor' => ['Nour Jebali', 'distributor@traceverte.test', 'distributor'],
            'consumer' => ['Yasmine Consommatrice', 'consumer@traceverte.test', 'consumer'],
            'sara' => ['Sara Mansour', 'sara@traceverte.test', 'consumer'],
            'karim' => ['Karim Bouazizi', 'karim@traceverte.test', 'consumer'],
            'unverified' => ['Nouveau Compte', 'nouveau@traceverte.test', 'consumer'],
            'blocked' => ['Compte Suspendu', 'bloque@traceverte.test', 'consumer'],
        ];

        $password = Hash::make('password');
        $users = [];

        foreach ($accounts as $key => [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'email_verified_at' => $key === 'unverified' ? null : now(),
                'blocked_at' => $key === 'blocked' ? now()->subDays(3) : null,
            ])->save();

            $users[$key] = $user;
        }

        return $users;
    }

    private function actors(array $users): array
    {
        $producers = [
            'zitouna' => Producer::create($this->place('Sfax') + [
                'user_id' => $users['producer']->id,
                'company_name' => 'Domaine Zitouna',
                'location' => 'Sfax, Tunisie',
                'farming_method' => 'biologique',
                'crop_types' => ['olives', 'amandes'],
                'production_capacity' => '120 tonnes/an',
                'certifications' => ['Ecocert Bio'],
            ]),
            'oasis' => Producer::create($this->place('Tozeur') + [
                'user_id' => $users['producer2']->id,
                'company_name' => 'Oasis de Tozeur',
                'location' => 'Tozeur, Tunisie',
                'farming_method' => 'raisonnée',
                'crop_types' => ['dattes'],
                'production_capacity' => '80 tonnes/an',
                'certifications' => ['Bio Tunisie'],
            ]),
            'capbon' => Producer::create($this->place('Nabeul') + [
                'company_name' => 'Ferme du Cap Bon',
                'location' => 'Nabeul, Tunisie',
                'farming_method' => 'permaculture',
                'crop_types' => ['piments', 'tomates', 'agrumes', 'miel'],
                'production_capacity' => '45 tonnes/an',
                'certifications' => [],
            ]),
            'beja' => Producer::create($this->place('Béja') + [
                'company_name' => 'Coopérative céréalière de Béja',
                'location' => 'Béja, Tunisie',
                'farming_method' => 'conventionnelle',
                'crop_types' => ['blé dur', 'orge'],
                'production_capacity' => '2 000 tonnes/an',
                'certifications' => [],
            ]),
            'mateur' => Producer::create($this->place('Mateur') + [
                'company_name' => 'Laiterie de Mateur',
                'location' => 'Mateur, Bizerte, Tunisie',
                'farming_method' => 'conventionnelle',
                'crop_types' => ['lait'],
                'production_capacity' => '1,5 million L/an',
                'certifications' => [],
            ]),
        ];

        $transformers = [
            'huilerie' => Transformer::create($this->place('Sfax') + [
                'user_id' => $users['transformer']->id,
                'company_name' => 'Huilerie du Sahel',
                'location' => 'Sfax, Tunisie',
                'transformation_type' => 'pressage à froid',
                'process_description' => 'Extraction à froid en moins de 24 h après la récolte, filtration naturelle.',
                'production_capacity' => '300 tonnes d’olives/an',
                'certifications' => ['ISO 22000'],
            ]),
            'conserverie' => Transformer::create($this->place('Nabeul') + [
                'company_name' => 'Conserverie du Cap Bon',
                'location' => 'Nabeul, Tunisie',
                'transformation_type' => 'conserverie',
                'process_description' => 'Broyage, cuisson et mise en bocaux stérilisés.',
                'production_capacity' => '500 000 bocaux/an',
                'certifications' => [],
            ]),
            'moulins' => Transformer::create($this->place('Béja') + [
                'company_name' => 'Moulins de Béja',
                'location' => 'Béja, Tunisie',
                'transformation_type' => 'meunerie',
                'process_description' => 'Mouture, roulage et séchage du couscous.',
                'production_capacity' => '1 200 tonnes/an',
                'certifications' => ['HACCP'],
            ]),
        ];

        $distributors = [
            'nord' => Distributor::create($this->place('Tunis') + [
                'user_id' => $users['distributor']->id,
                'company_name' => 'Distri Nord',
                'distributor_type' => 'grossiste',
                'location' => 'Tunis, Tunisie',
                'coverage_area' => 'Grand Tunis, Nord',
            ]),
            'sahel' => Distributor::create($this->place('Sousse') + [
                'company_name' => 'Sahel Logistique',
                'distributor_type' => 'transport frigorifique',
                'location' => 'Sousse, Tunisie',
                'coverage_area' => 'Sahel, Sud',
            ]),
        ];

        return [$producers, $transformers, $distributors];
    }

    /**
     * @return array<string, Product>
     */
    private function products(array $producers, array $transformers): array
    {
        $rows = [
            'huile' => ['Huile d’olive extra vierge bio', 'TV-OLV-001', '6191234000011', 'huiles', 'Sfax, Tunisie', 'Olives Chemlali', 'zitouna', 'huilerie', 'published'],
            'dattes' => ['Dattes Deglet Nour', 'TV-DAT-002', '6191234000028', 'fruits secs', 'Tozeur, Tunisie', 'Dattes Deglet Nour', 'oasis', null, 'published'],
            'harissa' => ['Harissa artisanale du Cap Bon', 'TV-HAR-003', '6191234000035', 'condiments', 'Nabeul, Tunisie', 'Piments, ail, huile d’olive, sel, carvi', 'capbon', 'conserverie', 'published'],
            'couscous' => ['Couscous complet', 'TV-COU-004', '6191234000042', 'céréales', 'Béja, Tunisie', 'Semoule de blé dur complet (gluten), sel', 'beja', 'moulins', 'published'],
            'miel' => ['Miel de thym', 'TV-MIE-005', '6191234000059', 'épicerie sucrée', 'Nabeul, Tunisie', 'Miel de thym', 'capbon', null, 'published'],
            'leben' => ['Leben (lait fermenté)', 'TV-LEB-006', '6191234000066', 'produits laitiers', 'Mateur, Tunisie', 'Lait de vache, ferments lactiques', 'mateur', null, 'published'],
            'jus' => ['Jus d’orange maltaise', 'TV-JUS-007', '6191234000073', 'boissons', 'Nabeul, Tunisie', 'Oranges maltaises pressées', 'capbon', null, 'published'],
            'tomates' => ['Tomates séchées à l’huile', 'TV-TOM-008', '6191234000080', 'conserves', 'Nabeul, Tunisie', 'Tomates, huile d’olive, origan', 'capbon', 'conserverie', 'draft'],
            'amandes' => ['Amandes de Sfax', 'TV-AMA-009', '6191234000097', 'fruits secs', 'Sfax, Tunisie', 'Amandes (fruits à coque)', 'zitouna', null, 'archived'],
        ];

        $products = [];
        foreach ($rows as $key => [$name, $sku, $barcode, $category, $origin, $ingredients, $producer, $transformer, $status]) {
            $products[$key] = Product::create([
                'name' => $name,
                'sku' => $sku,
                'barcode' => $barcode,
                'category' => $category,
                'origin' => $origin,
                'ingredients' => $ingredients,
                'producer_id' => $producers[$producer]->id,
                'transformer_id' => $transformer ? $transformers[$transformer]->id : null,
                'status' => $status,
            ]);
        }

        return $products;
    }

    private function footprints(array $p): void
    {
        $data = [
            'huile' => [3.2, 40, 1.8],
            'dattes' => [1.1, 90, 1.2],
            'harissa' => [2.4, 120, 0.9],
            'couscous' => [1.6, 210, 2.5],
            'miel' => [0.8, 15, 0.3],
            'leben' => [18.5, 620, 8.4],   // above the CO2 alert threshold
            'jus' => [0.9, 140, 0.6],
            'amandes' => [4.1, 480, 3.2],
            // "tomates" has no footprint: it cannot be published yet
        ];

        foreach ($data as $key => [$co2, $water, $land]) {
            $footprint = new EnvironmentalFootprint([
                'product_id' => $p[$key]->id,
                'co2_emissions' => $co2,
                'water_usage' => $water,
                'land_usage' => $land,
            ]);
            $footprint->ai_score = $footprint->computeScore();
            $footprint->saveQuietly();
        }
    }

    private function certificates(array $p): void
    {
        $rows = [
            ['huile', 'Bio Ecocert', 'Ecocert', 'ECO-TN-2025-0187', -200, 165, 'verified'],
            ['huile', 'ISO 22000', 'Bureau Veritas', 'BV-22000-5521', -400, 700, 'verified'],
            ['dattes', 'Bio Tunisie', 'CNCB', 'CNCB-BIO-7742', -345, 20, 'verified'],      // expires in 20 days
            ['harissa', 'Label Produit du Terroir', 'APIA', 'APIA-TER-0091', -10, 1085, 'pending'],
            ['couscous', 'HACCP', 'SGS', 'SGS-HACCP-3310', -300, 430, 'verified'],
            ['miel', 'Label Rouge Miel', 'Certipaq', 'CQ-LR-1209', -760, -5, 'verified'],  // already past its expiry date
            ['leben', 'Bio AB', 'Ecocert', 'ECO-AB-9932', -60, 300, 'rejected'],
            ['jus', 'Fairtrade', 'Max Havelaar', 'FT-TN-4410', -120, 610, 'verified'],
        ];

        foreach ($rows as [$key, $type, $issuer, $number, $issuedDaysAgo, $expiresInDays, $status]) {
            Certificate::create([
                'product_id' => $p[$key]->id,
                'certificate_type' => $type,
                'issuer' => $issuer,
                'certificate_number' => $number,
                'issue_date' => now()->addDays($issuedDaysAgo)->toDateString(),
                'expiry_date' => now()->addDays($expiresInDays)->toDateString(),
                'status' => $status,
            ]);
        }
    }

    private function traces(array $p, array $d): void
    {
        $journeys = [
            ['huile', 'nord', 'delivered', [['production', 'Sfax', 12], ['transformation', 'Sfax', 11], ['transport', 'Sousse', 9], ['distribution', 'Tunis', 8]]],
            ['dattes', 'sahel', 'in_transit', [['production', 'Tozeur', 6], ['stockage', 'Gabès', 4], ['transport', 'Sousse', 1]]],
            ['harissa', 'sahel', 'in_transit', [['production', 'Nabeul', 5], ['transformation', 'Nabeul', 4], ['transport', 'Sousse', 2]]],
            ['couscous', 'nord', 'delivered', [['production', 'Béja', 20], ['transformation', 'Béja', 15], ['distribution', 'Tunis', 10]]],
            ['jus', 'nord', 'delayed', [['production', 'Nabeul', 7], ['transport', 'Nabeul', 6]]],
            ['leben', 'nord', 'completed', [['production', 'Mateur', 3], ['transport', 'Bizerte', 2], ['vente', 'Tunis', 1]]],
        ];

        foreach ($journeys as [$key, $distributor, $status, $steps]) {
            $path = array_map(fn ($s) => [
                'stage' => $s[0],
                'location' => $s[1].', Tunisie',
                'at' => now()->subDays($s[2])->setTime(9, 0)->toIso8601String(),
                'lat' => self::CITIES[$s[1]][0],
                'lon' => self::CITIES[$s[1]][1],
            ], $steps);
            $last = end($path);

            SupplyChainTrace::create([
                'product_id' => $p[$key]->id,
                'distributor_id' => $d[$distributor]->id,
                'current_stage' => $last['stage'],
                'current_location_lat' => $last['lat'],
                'current_location_lon' => $last['lon'],
                'path_history' => $path,
                'status' => $status,
                'total_distance_km' => SupplyChainTrace::distanceFromSteps($path),
            ]);
        }
    }

    /**
     * @return list<EnvironmentalClaim>
     */
    private function claimsAndChecks(array $p): array
    {
        $huile = EnvironmentalClaim::create([
            'product_id' => $p['huile']->id,
            'claim_type' => 'Agriculture biologique',
            'title' => 'Huile issue de l’agriculture biologique certifiée Ecocert',
            'description' => 'Olives cultivées sans pesticides de synthèse, certification Ecocert n° ECO-TN-2025-0187 contrôlée chaque année.',
            'source_document' => 'certificat-ecocert-2025.pdf',
            'status' => 'verified',
        ]);
        ComplianceCheck::create([
            'environmental_claim_id' => $huile->id,
            'product_id' => $p['huile']->id,
            'check_type' => 'certificate_check',
            'status' => 'passed',
            'result_summary' => 'Certificat Ecocert valide, numéro vérifié auprès de l’organisme.',
            'checked_at' => now()->subDays(30)->toDateString(),
        ]);

        $dattes = EnvironmentalClaim::create([
            'product_id' => $p['dattes']->id,
            'claim_type' => 'Neutre en carbone',
            'title' => 'Dattes 100% neutres en carbone',
            'description' => 'Un produit naturel et respectueux de la planète.',
            'status' => 'rejected',
        ]);
        $failed = ComplianceCheck::create([
            'environmental_claim_id' => $dattes->id,
            'product_id' => $p['dattes']->id,
            'check_type' => 'document_review',
            'status' => 'failed',
            'result_summary' => 'Aucun bilan carbone ni programme de compensation fourni.',
            'checked_at' => now()->subDays(5)->toDateString(),
        ]);
        // Model events are off while seeding: create the alert the observer would have raised
        Alert::create([
            'product_id' => $p['dattes']->id,
            'compliance_check_id' => $failed->id,
            'alert_type' => 'compliance',
            'severity' => 'high',
            'title' => 'Vérification échouée : Dattes 100% neutres en carbone',
            'description' => $failed->result_summary,
            'detected_at' => now()->subDays(5),
            'status' => 'open',
        ]);

        $leben = EnvironmentalClaim::create([
            'product_id' => $p['leben']->id,
            'claim_type' => '100% écologique',
            'title' => 'Leben 100% naturel et écologique',
            'description' => 'Vert, durable et responsable.',
            'status' => 'pending',
        ]);
        ComplianceCheck::create([
            'environmental_claim_id' => $leben->id,
            'product_id' => $p['leben']->id,
            'check_type' => 'site_audit',
            'status' => 'needs_review',
            'notes' => 'Audit sur site à planifier.',
        ]);

        $harissa = EnvironmentalClaim::create([
            'product_id' => $p['harissa']->id,
            'claim_type' => 'Produit local',
            'title' => 'Piments cultivés à moins de 15 km de la conserverie',
            'description' => 'Piments récoltés à Korba et transformés à Nabeul (12 km).',
            'source_document' => 'attestation-provenance-korba.pdf',
            'status' => 'pending',
        ]);

        return [$huile, $dattes, $leben, $harissa];
    }

    private function consumersAndReviews(array $u, array $p): void
    {
        $profiles = [
            'consumer' => [['bio', 'faible_co2'], 'engagé', 'moyen', [], []],
            'sara' => [['local'], 'intermédiaire', 'éco', ['végétarien'], ['gluten']],
            'karim' => [['faible_eau', 'bio'], 'débutant', 'premium', [], []],
            'unverified' => [[], 'débutant', null, [], []],
        ];

        foreach ($profiles as $key => [$preferences, $level, $budget, $diet, $allergies]) {
            Consumer::create([
                'user_id' => $u[$key]->id,
                'preferences' => $preferences,
                'sustainability_level' => $level,
                'budget_range' => $budget,
                'dietary_restrictions' => $diet,
                'allergies' => $allergies,
            ]);
        }

        $reviews = [
            ['consumer', 'huile', 5, 'Excellente huile', 'Goût fruité, on sent la qualité. La page de traçabilité est rassurante.', 'approved', true],
            ['sara', 'dattes', 4, 'Très bonnes dattes', 'Moelleuses et sucrées, emballage un peu fragile.', 'approved', false],
            ['consumer', 'couscous', 4, 'Bon couscous', 'Grain régulier, cuisson facile.', 'approved', true],
            ['karim', 'harissa', 5, 'La meilleure harissa', 'Bien relevée sans être agressive.', 'pending', false],
            ['sara', 'miel', 3, 'Correct', 'Bon goût mais le pot est petit pour le prix.', 'pending', false],
            ['karim', 'leben', 1, 'PROMO !!! cliquez ici', 'Achetez des followers sur www.exemple-spam.test', 'rejected', false],
        ];

        foreach ($reviews as [$user, $product, $rating, $title, $comment, $status, $verified]) {
            Review::create([
                'user_id' => $u[$user]->id,
                'product_id' => $p[$product]->id,
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment,
                'status' => $status,
                'verified_purchase' => $verified,
            ]);
        }
    }

    private function place(string $city): array
    {
        return ['latitude' => self::CITIES[$city][0], 'longitude' => self::CITIES[$city][1]];
    }
}
