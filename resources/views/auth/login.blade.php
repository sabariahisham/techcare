@php
use Illuminate\Support\Facades\Route;
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login')

@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/@form-validation/form-validation.scss'
])
@endsection

@section('page-style')
@vite([
  'resources/assets/vendor/scss/pages/page-auth.scss'
])
<style>
/* Full Screen Background */
.fullscreen-auth-bg {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: -1 !important;
  
  /* Beautiful gradient background - Active */
  background: linear-gradient(
    135deg,
    #667eea 0%,
    #764ba2 100%
  );
  
  /* Alternative 1: Professional blue gradient */
  /* background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); */
  
  /* Alternative 2: Government themed */
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  
  /* Alternative 3: Sunset theme */
  /* background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%); */
  
  /* Alternative 4: Ocean theme */
  /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
  
  /* Alternative 5: Forest theme */
  /* background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); */
  
  /* Alternative 6: Professional dark */
  /* background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); */
  
  /* Alternative: With image overlay (uncomment if you have image) */
  
  background: 
    linear-gradient(135deg, rgba(224, 213, 240, 0.8), rgba(56, 70, 147, 0.8)),
    url('{{ asset("assets/img/backgrounds/bg1.jpg") }}');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
 
}

/* Add subtle animated pattern */
.fullscreen-auth-bg::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: 
    radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
    radial-gradient(circle at 80% 50%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
    radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
  background-size: 50px 50px, 30px 30px, 70px 70px;
  background-position: 0 0, 20px 20px, 10px 10px;
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}

/* Reset body and html for full coverage */
html, body {
  margin: 0 !important;
  padding: 0 !important;
  overflow-x: hidden !important;
  height: 100% !important;
}

/* Container adjustments */
.container-xxl {
  position: relative !important;
  z-index: 1 !important;
  min-height: 100vh !important;
  max-width: 100% !important;
  padding: 0 15px !important;
}

/* Wrapper adjustments */
.authentication-wrapper.authentication-basic {
  position: relative !important;
  z-index: 1 !important;
  min-height: 100vh !important;
  background: none !important;
}

/* Glass effect for login card */
.auth-glass-card {
  background: rgba(235, 236, 242, 1) !important;
  backdrop-filter: blur(15px) !important;
  -webkit-backdrop-filter: blur(15px) !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
  box-shadow: 
    0 25px 45px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.05) inset !important;
  border-radius: 20px !important;
  position: relative !important;
  overflow: hidden !important;
}

/* Add subtle glow effect */
.auth-glass-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  /*background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent); */
}

/* Center the card better */
.authentication-wrapper.authentication-basic .authentication-inner {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  min-height: 100vh !important;
}

/* Override default decorative elements */
.custom-auth-background .authentication-inner:before,
.custom-auth-background .authentication-inner:after {
  display: none !important;
}

/* Enhanced form styling */
.auth-glass-card .form-control {
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(5px);
}

.auth-glass-card .form-control:focus {
  background: rgba(255, 255, 255, 1);
  border-color: #696cff;
  box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
}

/* Logo styling */
.auth-glass-card .app-brand {
  margin-bottom: 2rem;
}

/* Social buttons enhancement */
.auth-glass-card .btn-text-facebook,
.auth-glass-card .btn-text-twitter,
.auth-glass-card .btn-text-github,
.auth-glass-card .btn-text-google-plus {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(5px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.auth-glass-card .btn-text-facebook:hover,
.auth-glass-card .btn-text-twitter:hover,
.auth-glass-card .btn-text-github:hover,
.auth-glass-card .btn-text-google-plus:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .fullscreen-auth-bg {
    background-attachment: scroll !important;
  }
  
  .container-xxl {
    padding: 0 10px !important;
  }
  
  .auth-glass-card {
    margin: 20px 0 !important;
    border-radius: 15px !important;
  }
  
  /* Responsive logo sizing */
  .auth-logo {
    max-height: 50px !important;
  }
  
  .app-brand-logo-img {
    max-height: 35px !important;
  }
  
  /* .app-system-title {
    font-size: 0.9rem !important;
    letter-spacing: 0.5px !important;
  } */
}

@media (max-width: 480px) {
  .auth-glass-card .card-body {
    padding: 1.5rem 1rem !important;
  }
  
  .auth-glass-card .app-brand {
    margin-bottom: 1.5rem !important;
  }
}

/* Loading animation for better UX */
.auth-glass-card {
  animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Footer/Copyright styling */
.auth-footer {
  font-size: 0.75rem !important; /* 12px */
  color: #020b14ff !important;
  line-height: 1.4 !important;
}

/* App Brand Logo styling */
.app-brand-logo-img {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
  transition: all 0.3s ease;
  max-height: 50px;
  width: auto;
}

.app-brand-logo-img:hover {
  transform: scale(1.05);
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
}

/* System Title styling */
.app-system-title-login {
  font-size: 2rem;
  font-weight: 600;
  color: #2c3051ff;
  letter-spacing: 1px;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  margin-top: 5px;
  transition: all 0.3s ease;
}

/* Alternative styles (uncomment to use): */
.app-system-title {
  font-size: 0.5rem;
  font-weight: 700;
  color: #1976d2;
  letter-spacing: 2px;
  text-transform: uppercase;  
}


/* .app-system-title {
  font-size: 0.95rem;
  font-weight: 500;
  color: #555;
  letter-spacing: 0.5px;
  font-style: italic;
} */


/* Logo container hover effect */
.app-brand-link:hover .app-system-title {
  color: #1976d2;
  transform: translateY(-1px);
}

/* Footer logo styling */
.auth-logo {
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
  transition: all 0.3s ease;
  max-height: 80px;
  width: auto;
}

.auth-logo:hover {
  transform: scale(1.05);
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
}

/* Government logo container */
.auth-logo-container {
  background: rgba(255, 255, 255, 0.1);
  padding: 15px;
  border-radius: 50%;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Welcome Image styling */
.welcome-image {
  filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
  transition: all 0.3s ease;
  border-radius: 15px !important;
  max-width: 200px;
  height: auto;
}

.welcome-image:hover {
  transform: scale(1.02);
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.2));
}

/* Responsive welcome image */
@media (max-width: 768px) {
  .welcome-image {
    max-width: 150px !important;
  }
}

@media (max-width: 480px) {
  .welcome-image {
    max-width: 120px !important;
  }
}

/* Alternative sizes you can use:
.auth-footer { font-size: 0.625rem !important; } /* 10px - very small */
.auth-footer { font-size: 0.7rem !important; }   /* 11.2px - small */
.auth-footer { font-size: 0.75rem !important; }  /* 12px - default above */
.auth-footer { font-size: 0.8rem !important; }   /* 12.8px - slightly larger */
*/
</style>
@endsection

@section('content')
<!-- Full Screen Background -->
<div class="fullscreen-auth-bg"></div>

<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
      <!-- Register -->
      <div class="card px-sm-6 px-0 auth-glass-card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
            <a href="{{url('/')}}" class="app-brand-link d-flex flex-column align-items-center text-decoration-none">
              <!-- Custom Logo Image -->
              <img src="{{ asset('assets/img/logos/logo2.png') }}" 
                   alt="eHelp Logo" 
                   class="app-brand-logo-img mb-2"
                   style="height: 100px; width: auto;">
              
              <!-- System Title -->
              <span class="app-system-title text-heading fw-bold text-center">KEMENTERIAN KEWANGAN</span>
            </a>
          </div>
          <!-- /Logo -->
          {{-- <h4 class="mb-1">Welcome to {{config('variables.templateName')}}! 👋</h4> --}}
          
          <!-- Welcome Image -->
          {{-- <div class="text-center mb-4">
            <img src="{{ asset('assets/img/logos/logo1.png') }}" 
                 alt="Welcome Image" 
                 class="welcome-image"
                 style="max-width: 200px; height: auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
          </div> --}}

          <p class="app-system-title-login text-center">SISTEM HELPDESK</p>

          @if (session('status'))
          <div class="alert alert-success mb-1 rounded-0" role="alert">
            <div class="alert-body">
              {{ session('status') }}
            </div>
          </div>
          @endif

          <form id="formAuthentication" class="mb-6" action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="login-email" class="form-label">EMEL PENGGUNA</label>
              <input type="text" class="form-control @error('email') is-invalid @enderror" id="login-email" name="email" placeholder="john@example.com" autofocus value="{{ old('email') }}">
              @error('email')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
              @enderror
            </div>

            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">KATA LALUAN</label>

              <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                <input type="password" id="login-password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" aria-describedby="password" />
                {{-- <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span> --}}
              </div>

              @error('password')
              <span class="invalid-feedback" role="alert">
                <span class="fw-medium">{{ $message }}</span>
              </span>
              @enderror
            </div>

            <div class="mb-8">
              <div class="d-flex justify-content-between mt-8">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember" {{ old('remember') ? 'checked' : '' }}>
                  <label class="form-check-label" for="remember-me">
                    Ingat Saya
                  </label>
                </div>
                @if (Route::has('password.request'))
                  <a href="{{route('password.request')}}">
                    <p class="mb-0">Lupa Kata Laluan?</p>
                  </a>
                @endif
              </div>
            </div>
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">Log Masuk</button>
            </div>
          </form>

          

          <div class="divider my-6">
            <div class="divider-text"></div>
          </div>

          
            <div class="d-flex flex-column align-items-center">
              <div class="mb-3">
                <!-- Option 1: Single Government Logo -->
                <img src="{{ asset('assets/img/logos/logo2.png') }}" 
                     alt="Kementerian Kewangan Malaysia" 
                     class="auth-logo"
                     style="height: 60px; width: auto;">
          </div>
          <span class="mb-2 fw-semibold">Kementerian Kewangan Malaysia</span>
            <div class="auth-footer">Hak Cipta Terpelihara @ 2025</div>
            <div class="auth-footer">Bahagian Teknologi Maklumat</div>
      </div>
      <!-- /Register -->
    </div>
  </div>
</div>
@endsection
