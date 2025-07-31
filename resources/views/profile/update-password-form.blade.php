<x-form-section submit="updatePassword">
  <x-slot name="title">
    {{ __('Update Password') }}
  </x-slot>

  <x-slot name="description">
    {{ __('Ensure your account is using a long, random password to stay secure.') }}
    <!-- Password validation indicator -->
    <div x-data="{
      password: '',
      get length() { return this.password.length >= 12 },
      get uppercase() { return /[A-Z]/.test(this.password) },
      get lowercase() { return /[a-z]/.test(this.password) },
      get number() { return /[0-9]/.test(this.password) },
      get symbol() { return /[^A-Za-z0-9]/.test(this.password) }
    }" class="mt-4 flex justify-center">
      <input type="hidden" x-model="password" x-effect="password = $refs.pwd.value" />
      <template x-if="$refs.pwd">
        <div style="display:none"></div>
      </template>
      <div class="w-full max-w-md bg-white border border-gray-200 rounded-lg shadow-sm p-4">
        <div class="font-semibold text-base mb-2 text-gray-700">Password Requirements</div>
        <ul class="space-y-2">
          <li class="flex items-center" :class="length ? 'text-success' : 'text-danger'">
            <span class="mr-2">
              <svg x-show="length" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!length" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Minimum 12 characters
          </li>
          <li class="flex items-center" :class="uppercase ? 'text-success' : 'text-danger'">
            <span class="mr-2">
              <svg x-show="uppercase" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!uppercase" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Uppercase letter
          </li>
          <li class="flex items-center" :class="lowercase ? 'text-success' : 'text-danger'">
            <span class="mr-2">
              <svg x-show="lowercase" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!lowercase" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Lowercase letter
          </li>
          <li class="flex items-center" :class="number ? 'text-success' : 'text-danger'">
            <span class="mr-2">
              <svg x-show="number" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!number" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Number
          </li>
          <li class="flex items-center" :class="symbol ? 'text-success' : 'text-danger'">
            <span class="mr-2">
              <svg x-show="symbol" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              <svg x-show="!symbol" xmlns="http://www.w3.org/2000/svg" width="20" height="20" style="width:20px;height:20px;" class="h-5 w-5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </span>
            Symbol
          </li>
        </ul>
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

  <x-slot name="form">
    <div class="mb-6">
      <x-label class="form-label" for="current_password" value="{{ __('Current Password') }}" />
      <x-input id="current_password" type="password"
        class="{{ $errors->has('current_password') ? 'is-invalid' : '' }}" wire:model="state.current_password"
        autocomplete="current-password" />
      <x-input-error for="current_password" />
    </div>

    <div class="mb-6">
      <x-label class="form-label" for="password" value="{{ __('New Password') }}" />
      <x-input id="password" type="password" class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
        wire:model="state.password" autocomplete="new-password" />
      <x-input-error for="password" />
    </div>

    <div class="mb-6">
      <x-label class="form-label" for="password_confirmation" value="{{ __('Confirm Password') }}" />
      <x-input id="password_confirmation" type="password"
        class="{{ $errors->has('password_confirmation') ? 'is-invalid' : '' }}"
        wire:model="state.password_confirmation" autocomplete="new-password" />
      <x-input-error for="password_confirmation" />
    </div>
  </x-slot>


  <x-slot name="actions">
    <x-button>
      {{ __('Save') }}
    </x-button>
  </x-slot>
</x-form-section>
