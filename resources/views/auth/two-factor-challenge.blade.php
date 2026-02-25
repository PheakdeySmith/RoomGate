@extends('layouts.guest')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="authentication-wrapper authentication-cover">
  <div class="authentication-inner row m-0">
    <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-5">
      <div class="w-100 d-flex justify-content-center">
        <img src="{{ asset('assets/assets') }}/img/illustrations/auth-login-illustration-light.png" class="img-fluid" alt="auth">
      </div>
    </div>

    <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-12 mt-sm-0">
        <h4 class="mb-1">Two-Factor Authentication</h4>
        <p class="mb-6">Enter the 6-digit code sent to your email to continue.</p>

        @if(session('status'))
          <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('two-factor.challenge.verify') }}" class="mb-4">
          @csrf
          <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $email) }}" required>
            @error('email')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-4">
            <label class="form-label" for="code">Authentication Code</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" class="form-control" placeholder="123456" required>
            @error('code')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
          <button type="submit" class="btn btn-primary w-100">Verify and Sign In</button>
        </form>

        <form method="POST" action="{{ route('two-factor.challenge.resend') }}">
          @csrf
          <input type="hidden" name="email" value="{{ old('email', $email) }}">
          <button type="submit" class="btn btn-label-secondary w-100">Resend Code</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
