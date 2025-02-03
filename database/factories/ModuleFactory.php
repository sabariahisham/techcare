<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Module;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // declare array of modules
        $modules = [
            'User Management',
            'Tax Management',
            'Fiscal Management',
        ];

        return [
          'module_name' => $modules[array_rand($modules)],
        ];
    }
}
