<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name'   => $this->faker->firstName(),
            'middle_name'  => $this->faker->optional()->firstName(),
            'surname'      => $this->faker->lastName(),
            'email'        => $this->faker->unique()->safeEmail(),
            'phone'        => $this->faker->phoneNumber(),
            'country'      => $this->faker->countryCode(),
            'gender'       => $this->faker->randomElement(['male', 'female']),
            'password'     => static::$password ??= Hash::make('password'),
            'referral_code' => strtoupper(Str::random(8)),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
