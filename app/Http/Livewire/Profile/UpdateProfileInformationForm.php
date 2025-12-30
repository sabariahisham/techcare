<?php

namespace App\Http\Livewire\Profile;

use Spatie\Permission\Models\Role;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as BaseUpdateProfileInformationForm;

class UpdateProfileInformationForm extends BaseUpdateProfileInformationForm
{
    public $roles;

    public function mount()
    {
        $user = auth()->user();

        $this->roles = Role::all();

        // Only include fields we need
        $this->state = [
            'name' => $user->name,
            'email' => $user->email,
            'ic_number' => $user->ic_number,
            'agency' => $user->agency,
            'role' => $user->roles->pluck('name')->first() ?? null,
            // Add more fields as needed
        ];

        // $this->state = array_merge([
        //     // 'email' => $user->email,
        //     'role' => $user->ic_number,
        // ], $user->withoutRelations()->toArray());
    }
}
