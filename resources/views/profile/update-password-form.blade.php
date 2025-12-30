<x-form-section submit="updatePassword">
  <x-slot name="title">
    {{ __('Update Password') }}
  </x-slot>

  <x-slot name="description">
    {{ __('Ensure your account is using a long, random password to stay secure.') }}
  </x-slot>

  <x-slot name="form">
    <div x-data="{
      showCurrent: false,
      showNew: false,
      showConfirm: false,
      password: '',
      password_confirmation: '',
      get length() { return this.password.length >= 12 },
      get uppercase() { return /[A-Z]/.test(this.password) },
      get lowercase() { return /[a-z]/.test(this.password) },
      get number() { return /[0-9]/.test(this.password) },
      get symbol() { return /[^A-Za-z0-9]/.test(this.password) },
      get matched() { return this.password_confirmation.length > 0 && this.password === this.password_confirmation }
    }" x-init="
      password = $refs.pwd ? $refs.pwd.value : '';
      password_confirmation = $refs.pwdc ? $refs.pwdc.value : '';
      $refs.pwd && $refs.pwd.addEventListener('input', e => { password = e.target.value });
      $refs.pwdc && $refs.pwdc.addEventListener('input', e => { password_confirmation = e.target.value });
    ">
      <div class="mb-6">
        <x-label class="form-label" for="current_password" value="{{ __('Current Password') }}" />
        <div class="input-group input-group-merge">
          <input :type="showCurrent ? 'text' : 'password'" id="current_password"
            class="form-control {{ $errors->has('current_password') ? 'is-invalid' : '' }}" wire:model="state.current_password" />
          <span class="input-group-text cursor-pointer" @click="showCurrent = !showCurrent">
            <template x-if="!showCurrent">
              <i class="bx bx-hide"></i>
            </template>
            <template x-if="showCurrent">
              <i class="bx bx-show"></i>
            </template>
          </span>
        </div>
        <x-input-error for="current_password" />
        @if ($errors->get('current_password'))
          <div class="invalid-feedback d-block text-danger" style="font-size: 0.875em; margin-top: 0.25rem;">
            {{ $errors->get('current_password')[0] }}
          </div>
        @endif
      </div>
      <div class="mb-6">
        <x-label class="form-label" for="password" value="{{ __('New Password') }}" />
        <div class="input-group input-group-merge">
          <input :type="showNew ? 'text' : 'password'" id="password"
            class="form-control {{
              ($errors->has('password') && $errors->get('password')[0] !== 'The password field confirmation does not match.')
                ? 'is-invalid'
                : (
                  ($errors->has('password_confirmation') || ($errors->has('password') && $errors->get('password')[0] === 'The password field confirmation does not match.'))
                  ? 'is-invalid'
                  : ''
                )
            }}" wire:model="state.password" x-ref="pwd" @input="password = $event.target.value" />
          <span class="input-group-text cursor-pointer" @click="showNew = !showNew">
            <template x-if="!showNew">
              <i class="bx bx-hide"></i>
            </template>
            <template x-if="showNew">
              <i class="bx bx-show"></i>
            </template>
          </span>
        </div>
        <x-input-error for="password" />
        @if ($errors->get('password') && $errors->get('password')[0] !== 'The password field confirmation does not match.')
          <div class="invalid-feedback d-block text-danger" style="font-size: 0.875em; margin-top: 0.25rem;">
            Password does not meet all requirements.
          </div>
        @endif
      </div>
      <div class="mb-6">
        <x-label class="form-label" for="password_confirmation" value="{{ __('Confirm Password') }}" />
        <div class="input-group input-group-merge">
          <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation"
            class="form-control {{
              ($errors->has('password_confirmation') || ($errors->has('password') && $errors->get('password')[0] === 'The password field confirmation does not match.'))
                ? 'is-invalid'
                : ''
            }}"
            wire:model="state.password_confirmation" x-ref="pwdc" @input="password_confirmation = $event.target.value" />
          <span class="input-group-text cursor-pointer" @click="showConfirm = !showConfirm">
            <template x-if="!showConfirm">
              <i class="bx bx-hide"></i>
            </template>
            <template x-if="showConfirm">
              <i class="bx bx-show"></i>
            </template>
          </span>
        </div>
        <x-input-error for="password_confirmation" />
        @if ($errors->get('password_confirmation'))
          <span class="invalid-feedback d-block text-danger" style="font-size: 0.875em; margin-top: 0.25rem;" role="alert">
            <span class="fw-medium">{{ $errors->get('password_confirmation')[0] }}</span>
          </span>
        @elseif ($errors->get('password') && $errors->get('password')[0] === 'The password field confirmation does not match.')
          <span class="invalid-feedback d-block text-danger" style="font-size: 0.875em; margin-top: 0.25rem;" role="alert">
            <span class="fw-medium">{{ $errors->get('password')[0] }}</span>
          </span>
        @endif
      </div>
      <!-- Password requirements indicator (plain checklist, below Confirm Password) -->
      <div class="mt-2">
        <div class="text-sm text-gray-700 font-semibold mb-1">Password must contain:</div>
        <ul class="space-y-1 text-sm">
          <li :class="length ? 'text-success' : 'text-danger'">
            <span class="mr-1">
              <svg x-show="length" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!length" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Minimum 12 characters
          </li>
          <li :class="uppercase ? 'text-success' : 'text-danger'">
            <span class="mr-1">
              <svg x-show="uppercase" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!uppercase" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            At least one uppercase letter
          </li>
          <li :class="lowercase ? 'text-success' : 'text-danger'">
            <span class="mr-1">
              <svg x-show="lowercase" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!lowercase" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            At least one lowercase letter
          </li>
          <li :class="number ? 'text-success' : 'text-danger'">
            <span class="mr-1">
              <svg x-show="number" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!number" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            At least one number
          </li>
          <li :class="symbol ? 'text-success' : 'text-danger'">
            <span class="mr-1">
              <svg x-show="symbol" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!symbol" xmlns="http://www.w3.org/2000/svg" width="16" height="16" class="inline text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            At least one special character
          </li>
        </ul>
        <!-- ...existing code... -->
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var pwdInput = document.getElementById('password');
        var hiddenInput = document.querySelector('[x-model="password"]');
        if (pwdInput && hiddenInput) {
          pwdInput.addEventListener('input', function() {
            hiddenInput.value = pwdInput.value;
            hiddenInput.dispatchEvent(new Event('input'));
          });
        }
      });
    </script>
  </x-slot>

  <x-slot name="actions">
    <x-button>
      {{ __('Save') }}
    </x-button>
  </x-slot>
</x-form-section>
