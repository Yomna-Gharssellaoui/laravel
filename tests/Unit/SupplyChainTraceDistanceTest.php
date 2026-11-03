<?php

namespace Tests\Unit;

use App\Models\SupplyChainTrace;
use PHPUnit\Framework\TestCase;

class SupplyChainTraceDistanceTest extends TestCase
{
    public function test_haversine_calculates_approximate_distance_between_paris_and_lyon(): void
    {
        // Paris: 48.8566 N, 2.3522 E
        // Lyon:  45.7640 N, 4.8357 E
        $distance = SupplyChainTrace::haversine(48.8566, 2.3522, 45.7640, 4.8357);

        // Expected distance is approximately 392 km
        $this->assertGreaterThan(385, $distance);
        $this->assertLessThan(400, $distance);
    }

    public function test_distance_from_steps_returns_null_with_less_than_two_points(): void
    {
        $this->assertNull(SupplyChainTrace::distanceFromSteps([]));
        $this->assertNull(SupplyChainTrace::distanceFromSteps([
            ['stage' => 'farm', 'lat' => 48.0, 'lon' => 2.0],
        ]));
        $this->assertNull(SupplyChainTrace::distanceFromSteps([
            ['stage' => 'step1'],
            ['stage' => 'step2'],
        ]));
    }

    public function test_distance_from_steps_sums_consecutive_points_and_filters_missing_coordinates(): void
    {
        $steps = [
            ['stage' => 'Départ', 'lat' => 48.8566, 'lon' => 2.3522], // Paris
            ['stage' => 'Étape sans coordonnées', 'location' => 'Inconnue'],
            ['stage' => 'Arrivée', 'lat' => 45.7640, 'lon' => 4.8357], // Lyon
        ];

        $totalKm = SupplyChainTrace::distanceFromSteps($steps);

        $this->assertNotNull($totalKm);
        $this->assertGreaterThan(385, $totalKm);
        $this->assertLessThan(400, $totalKm);
    }

    public function test_distance_from_steps_returns_zero_for_same_point(): void
    {
        $steps = [
            ['stage' => 'Départ', 'lat' => 48.8566, 'lon' => 2.3522],
            ['stage' => 'Même lieu', 'lat' => 48.8566, 'lon' => 2.3522],
        ];

        $totalKm = SupplyChainTrace::distanceFromSteps($steps);

        $this->assertSame(0.0, $totalKm);
    }
}
