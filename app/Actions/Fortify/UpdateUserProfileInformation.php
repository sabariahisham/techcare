<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'ic_number' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'agency' => ['required', 'string', 'max:255'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'ic_number' => $input['ic_number'],
                'agency' => $input['agency'],
            ])->save();
        }

        // Update role using Spatie
        if (isset($input['role'])) {
            $user->syncRoles([$input['role']]);
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'ic_number' => $input['ic_number'],
            'agency' => $input['agency'],
            'email_verified_at' => null,
        ])->save();

        // Update role using Spatie
        if (isset($input['role'])) {
            $user->syncRoles([$input['role']]);
        }

        $user->sendEmailVerificationNotification();
    }
}
