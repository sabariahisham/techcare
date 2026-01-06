<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Laravel\Jetstream\Features;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
      
        $grades = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'];
        $schemes = ['H', 'B', 'DS', 'F', 'J', 'N', 'G', 'S', 'A'];
        $scheme = $schemes[array_rand($schemes)];
        $grade = $grades[array_rand($grades)];
        $schemeAndGrade = $scheme . $grade;

        $roles = [
            'Administrator',
            'Pentadbir Sistem',
            'Penyelia Helpdesk', 
            'Helpdesk On Duty',
            'Pegawai Tugasan',
            'Pengurusan MOF',
            'Pengadu'

        ];

        return [
            'uuid' => Uuid::uuid4(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'ic_number' => fake()->numerify('############'),
            'tel_bimbit' => Str::limit(fake()->phoneNumber(), 15),   // phone_number -> tel_bimbit
            'tel_pejabat' => Str::limit(fake()->phoneNumber(), 15),  // office_phone_number -> tel_pejabat
            'gln_jawatan' => $schemeAndGrade,                        // scheme_and_grade -> gln_jawatan
            'agency' => fake()->company(),
            'jawatan' => fake()->jobTitle(),                         // position -> jawatan
            // 'phone_number' => Str::limit(fake()->phoneNumber(), 15),
            // 'office_phone_number' => Str::limit(fake()->phoneNumber(), 15),
            // 'scheme_and_grade' => $schemeAndGrade,
            // 'agency' => fake()->company(),
            // 'position' => fake()->jobTitle(),
            'password' => Hash::make('111111111111'),
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // return [
        //     'name' => fake()->name(),
        //     'email' => fake()->unique()->safeEmail(),
        //     'email_verified_at' => now(),
        //     'password' => static::$password ??= Hash::make('password'),
        //     'two_factor_secret' => null,
        //     'two_factor_recovery_codes' => null,
        //     'remember_token' => Str::random(10),
        //     'profile_photo_path' => null,
        //     'current_team_id' => null,
        // ];
    }

    public function configure()
    {
        // reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return $this->afterCreating(function (User $user) {
            $roles = [
            'Administrator',
            'Pentadbir Sistem',
            'Penyelia Helpdesk', 
            'Helpdesk On Duty',
            'Pegawai Tugasan',
            'Pengurusan MOF',
            'Pengadu'
        ];
            $user->assignRole($roles[array_rand($roles)]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    // public function unverified(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'email_verified_at' => null,
    //     ]);
    // }

    /**
     * Indicate that the user should have a personal team.
     */
    // public function withPersonalTeam(?callable $callback = null): static
    // {
    //     if (! Features::hasTeamFeatures()) {
    //         return $this->state([]);
    //     }

    //     return $this->has(
    //         Team::factory()
    //             ->state(fn (array $attributes, User $user) => [
    //                 'name' => $user->name.'\'s Team',
    //                 'user_id' => $user->id,
    //                 'personal_team' => true,
    //             ])
    //             ->when(is_callable($callback), $callback),
    //         'ownedTeams'
    //     );
    // }
}
