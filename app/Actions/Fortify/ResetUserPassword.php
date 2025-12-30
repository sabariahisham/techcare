<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        // Check if new password matches the old password
        if (Hash::check($input['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['New password cannot be the same as your current password.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
