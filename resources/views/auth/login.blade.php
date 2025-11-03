@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 400px; border-radius: 1rem;">
        <div class="card-body text-center">
            <h2 class="mb-4 text-primary">Welcome to AI Email Manager</h2>

            <p class="text-muted mb-4">
                Sign in with your Google account to manage and organize your emails intelligently.
            </p>

            <a href="{{ route('auth.google') }}" class="btn btn-danger btn-lg w-100">
                <i class="bi bi-google me-2"></i> Sign in with Google
            </a>
        </div>
    </div>
</div>
@endsection
