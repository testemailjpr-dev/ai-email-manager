@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 400px; border-radius: 1rem;">
        <div class="card-body text-center">
            <h2>Welcome, {{ Auth::user()->name }}!</h2>
            <p class="text-muted">Your AI Email Manager dashboard</p>
        
            <a href="{{ route('categories.index') }}" class="btn btn-primary mt-3">
                Manage Categories
            </a>
        </div>
    </div>
</div>
@endsection
