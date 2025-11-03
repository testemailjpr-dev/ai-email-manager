@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-center align-items-center bg-light">
    <div class="card shadow p-4" style="width: 90%; border-radius: 1rem;">
        <div class="card-body show-email">
            @if($showAccount)
            <h4><strong>Gmail Account:</strong> {{ $email->account_email }}</h4><hr>
            @endif
            <h3>{{ $email['subject'] }}</h3>
            <p><strong>From:</strong> {{ $email['from'] }}</p>
            <p><strong>Date:</strong> {{ $email->created_at->format('Y-m-d H:i') }}</p>
            <p><strong>AI Summary:</strong> {{ $email['summary'] }}</p>
            <hr>
            <div class="email-body">
                <strong>Message:</strong>
                {!! $email['body'] !!}
            </div>
        
            <a href="{{ route('category.emails.index', $email->category_id) }}" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Back to list</a>
            
            <a href="{{ route('emails.unsubscribe', $email->gmail_id) }}" class="btn btn-secondary mt-3" style="float:right;"><i class="bi bi-box-arrow-right"></i> Unsubscribe</a>
            <a href="{{ route('emails.trash', $email->gmail_id) }}" class="btn btn-danger mt-3" style="float:right; margin-right: 15px;"><i class="bi bi-trash"></i> Move to Trash</a>
        </div>
    </div>
</div>
@endsection
