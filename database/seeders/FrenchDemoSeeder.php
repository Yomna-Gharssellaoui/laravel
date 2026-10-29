<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Certificate;
use App\Models\Consumer;
use App\Models\Distributor;
use App\Models\EnvironmentalFootprint;
use App\Models\Producer;
use App\Models\Product;
use App\Models\Review;
use App\Models\SupplyChainTrace;
use App\Models\Transformer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FrenchDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Clean selected tables (safe for demo db)
        $tables = [
            'personal_ratings', 'ai_analyses', 'alerts', 'reviews', 'environmental_footprints', 'products', 'supply_chain_traces', 'certificates', 'producers', 'transformers', 'distributors', 'consumers',
        ];

        foreach ($tables as $t) {
            DB::table($t)->delete();
        }

        // Simple French producers
        $producersData = [
            ['company_name' => 'Ferme du Moulin', 'location' => 'Normandie, France'],
            ['company_name' => 'Les Vergers du Sud', 'location' => 'Occitanie, France'],
            ['company_name' => 'La Ferme Bio du Parc', 'location' => 'Bretagne, France'],
        ];

        $producers = [];
        foreach ($producersData as $p) {
            $producers[] = Producer::factory()->state($p)->create();
        }

        // Transformers
        $transformers = [];
        $transformers[] = Transformer::factory()->state(['company_name' => 'Moulin et Fils'])->create();
        $transformers[] = Transformer::factory()->state(['company_name' => 'Atelier Conserves du Sud'])->create();

        // Distributors
        Distributor::factory()->count(2)->state(['company_name' => 'Réseau Bio Local'])->create();

        // Products (French readable)
        $productsData = [
            ['name' => 'Compote de pommes artisanale', 'category' => 'conserves', 'ingredients' => 'Pommes, sucre de canne', 'producer' => $producers[0]],
            ['name' => 'Farine de blé semi-complète', 'category' => 'céréales', 'ingredients' => 'Blé', 'producer' => $producers[1]],
            ['name' => 'Confiture de fraises', 'category' => 'conserves', 'ingredients' => 'Fraises, sucre', 'producer' => $producers[2]],
        ];

        $products = [];
        foreach ($productsData as $i => $pd) {
            $prod = Product::factory()->for($pd['producer'], 'producer')->state([
                'name' => $pd['name'],
                'category' => $pd['category'],
                'ingredients' => $pd['ingredients'],
                'status' => 'published',
            ])->create();
            $products[] = $prod;

            // footprints
            EnvironmentalFootprint::factory()->for($prod, 'product')->state([
                'co2_emissions' => rand(1, 10),
                'water_usage' => rand(10, 200),
                'land_usage' => rand(1, 5),
                'ai_score' => rand(30, 90),
            ])->create();
        }

        // Certificates
        Certificate::factory()->count(3)->create();

        // Reviews in French
        $reviews = [
            ['title' => 'Très bon produit', 'comment' => 'Goût équilibré, texture agréable.'],
            ['title' => 'Saveur naturelle', 'comment' => 'Bonne qualité, ingrédients simples et locaux.'],
        ];
        foreach ($reviews as $r) {
            Review::factory()->state(['title' => $r['title'], 'comment' => $r['comment']])->for($products[array_rand($products)], 'product')->create();
        }

        // Alerts (example French text)
        Alert::factory()->state(['title' => 'Rappel qualité: contrôle de lot', 'description' => 'Contrôle qualité renforcé sur un lot récent.'])->for($products[array_rand($products)], 'product')->create();

        // Consumers
        Consumer::factory()->count(6)->create();

        // Supply chain traces
        foreach ($products as $prod) {
            SupplyChainTrace::factory()->for($prod, 'product')->count(3)->create();
        }
    }
}
