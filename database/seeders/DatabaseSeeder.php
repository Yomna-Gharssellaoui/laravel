<?php

namespace Database\Seeders;

use App\Models\Consumer;
use App\Models\Distributor;
use App\Models\EnvironmentalFootprint;
use App\Models\Producer;
use App\Models\Transformer;
use App\Models\User;
use App\Services\RecommendationEngine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo accounts first (admin, producer, transformer, distributor, consumer)
        $this->call(UserSeeder::class);

        // The French demo seeder resets the business tables
        $this->call([
            FrenchDemoSeeder::class,
            M5DemoSeeder::class,
        ]);

        // Attach the demo accounts to a business profile so ownership rules can be demonstrated
        $this->linkDemoAccount('producer@traceverte.test', Producer::query()->oldest('id')->first());
        $this->linkDemoAccount('transformer@traceverte.test', Transformer::query()->oldest('id')->first());
        $this->linkDemoAccount('distributor@traceverte.test', Distributor::query()->oldest('id')->first());

        // Demo consumer profile, then computed recommendations for every consumer
        $consumer = User::where('email', 'consumer@traceverte.test')->first();
        if ($consumer) {
            Consumer::updateOrCreate(['user_id' => $consumer->id], [
                'preferences' => ['bio', 'faible_co2'],
                'sustainability_level' => 'engagé',
                'budget_range' => 'moyen',
                'dietary_restrictions' => [],
                'allergies' => [],
            ]);
        }

        // Model events are off while seeding: derive the footprint scores explicitly
        EnvironmentalFootprint::query()->each(
            fn (EnvironmentalFootprint $footprint) => $footprint->forceFill(['ai_score' => $footprint->computeScore()])->saveQuietly()
        );

        app(RecommendationEngine::class)->recomputeAll();
    }

    private function linkDemoAccount(string $email, Producer|Transformer|Distributor|null $profile): void
    {
        $user = User::where('email', $email)->first();

        if ($user && $profile) {
            $profile->update(['user_id' => $user->id]);
        }
    }
}
