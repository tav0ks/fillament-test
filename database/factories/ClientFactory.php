<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use Faker\Generator as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('pt_BR');
        return [
            'cnpj' => $faker->unique()->cnpj,
            'name' => $faker->company,
            'fantasy_name' => $faker->companySuffix,
            'phone' => $faker->phoneNumber,
            'email' => $faker->unique()->safeEmail,
            'logo' => $faker->imageUrl(),
            'employee_quantity' => $faker->randomElement([1, 2, 3, 4, 5]),
            'company_size' => $faker->randomElement([1, 2, 3, 4]),
            'industry_segment' => $faker->randomElement([1, 2, 3]),
            'structured_hr_department' => 1,
            'company_profile' => $faker->paragraph,
            'responsible_name' => $faker->name,
            'responsible_email' => $faker->unique()->safeEmail,
            'responsible_phone' => $faker->phoneNumber,
            'responsible_whatsapp' => $faker->phoneNumber,
            'mission' => $faker->paragraph,
            'values' => $faker->paragraph,
            'pdi_program' => 1,
            'work_regimes' => $faker->word,
            'billing_email' => $faker->unique()->safeEmail,
            'billing_responsible' => $faker->name,
            'billing_district' => $faker->citySuffix,
            'billing_street' => $faker->streetName,
            'billing_zip_code' => $faker->postcode,
            'billing_city' => $faker->city,
            'billing_state' => $faker->state,
            'billing_number' => $faker->buildingNumber,
            'billing_complement' => $faker->secondaryAddress,
            'payment_methods' => 1,
            'payment_date' => $faker->date,
            'contract_type' => 1,
            'contract_package' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
