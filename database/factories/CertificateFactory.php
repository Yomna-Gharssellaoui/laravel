<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Certificate> */
class CertificateFactory extends Factory
{
    public function definition(): array
    {
        $issue = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'product_id' => Product::factory(),
            'certificate_type' => fake()->randomElement(['Bio AB', 'Fairtrade', 'Label Rouge', 'AOP', 'Rainforest Alliance']),
            'issuer' => fake()->company(),
            'certificate_number' => strtoupper(fake()->bothify('CERT-####-????')),
            'issue_date' => $issue,
            'expiry_date' => (clone $issue)->modify('+2 years'),
            'status' => fake()->randomElement(['pending', 'verified', 'expired', 'rejected']),
        ];
    }
}
