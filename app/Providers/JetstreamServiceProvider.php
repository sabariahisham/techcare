<?php

namespace App\Providers;

use Livewire\Livewire;
use Laravel\Jetstream\Jetstream;
use Illuminate\Support\Facades\Vite;
use App\Actions\Jetstream\DeleteUser;
use Illuminate\Support\ServiceProvider;
use App\Http\Livewire\Profile\UpdateProfileInformationForm;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);

        Livewire::component('profile.update-profile-information-form', UpdateProfileInformationForm::class);

        Vite::prefetch(concurrency: 3);
    }

    /**
     * Configure the permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
