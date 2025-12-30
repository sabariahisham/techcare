@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Register Page')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
])
@endsection

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/@form-validation/popular.js',
  'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('page-script')
@vite([
  'resources/assets/js/pages-auth.js'
])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">

      <!-- Register Card -->
      <div class="card px-sm-6 px-0">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{url('/')}}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">@include('_partials.macros',["width"=>25,"withbg"=>'var(--bs-primary)'])</span>
              <span class="app-brand-text demo text-heading fw-bold">{{config('variables.templateName')}}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1">Adventure starts here 🚀</h4>
          <p class="mb-6">Make your app management easy and fun!</p>

          <form id="formAuthentication" class="mb-6" action="{{route('register')}}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="username" name="username" placeholder="johndoe" autofocus value="{{ old('name') }}" />
            @error('name')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
            @enderror
            </div>
            <div class="mb-6">
              <label for="email" class="form-label">Email</label>
              <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="john@example.com" value="{{ old('email') }}" />
              @error('email')
                <span class="invalid-feedback" role="alert">
                  <span class="fw-medium">{{ $message }}</span>
                </span>
              @enderror

            </div>
            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">Password</label>
              <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
              @error('password')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
              @enderror
            </div>

            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password-confirm">Confirm Password</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password-confirm" class="form-control" name="password_confirmation" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())

            <div class="my-8">
              <div class="form-check mb-0 ms-2 @error('terms') is-invalid @enderror">
                <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" id="terms" name="terms" />
                <label class="form-check-label" for="terms">
                  I agree to
                  <a href="{{ route('policy.show') }}" target="_blank">privacy policy</a> &
                  <a href="{{ route('terms.show') }}" target="_blank">terms</a>
                </label>
              </div>
              @error('terms')
                <div class="invalid-feedback" role="alert">
                    <span class="fw-medium">{{ $message }}</span>
                </div>
              @enderror
            </div>
            @endif

            <button type="submit" class="btn btn-primary d-grid w-100">
              Sign up
            </button>
          </form>

          <p class="text-center">
            <span>Already have an account?</span>
            <a href="{{url('auth/login-basic')}}">
              <span>Sign in instead</span>
            </a>
          </p>

          <div class="divider my-6">
            <div class="divider-text">or</div>
          </div>

          <div class="d-flex justify-content-center">
            <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-facebook me-1_5">
              <i class='bx bxl-facebook-circle'></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-twitter me-1_5">
              <i class="tf-icons bx bxl-twitter"></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-github me-1_5">
              <i class='bx bxl-github'></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-google-plus">
              <i class="tf-icons bx bxl-google"></i>
            </a>
          </div>
        </div>
      </div>
      <!-- Register Card -->
    </div>
  </div>
</div>
@endsection
