@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 100%; border-radius: 1rem;">
        <div class="card-body">

            <h2 class="mb-4">Linked Google Accounts</h2>
        
            @if ($accounts->count() > 0)
                <div class="list-group">
                    @foreach ($accounts as $acc)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">{{ $acc->email }}</h5>
                                <small class="text-muted">Google ID: {{ $acc->google_id }}</small><br>
                                <small class="text-muted">
                                    Linked {{ $acc->created_at->diffForHumans() }}
                                </small>
                            </div>
        
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    No Google accounts linked yet.
                </div>
            @endif
        
        </div>
    </div>
</div>
@endsection
