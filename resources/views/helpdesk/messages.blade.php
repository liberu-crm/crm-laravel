@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Unified Helpdesk</h1>

    <div class="row">
        <div class="col-md-3">
            <h2>Email Messages</h2>
            @foreach($messages['email'] as $message)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $message->from }}</h5>
                        <p class="card-text">{{ $message->snippet }}</p>
                        <a href="{{ route('messages.show', ['id' => $message->id, 'type' => 'email']) }}" class="btn btn-primary">View</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-md-3">
            <h2>WhatsApp Messages</h2>
            @foreach($messages['whatsapp'] as $message)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $message['from'] }}</h5>
                        <p class="card-text">{{ $message['body'] }}</p>
                        <a href="{{ route('messages.show', ['id' => $message['id'], 'type' => 'whatsapp']) }}" class="btn btn-primary">View</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-md-3">
            <h2>Facebook Messenger</h2>
            @foreach($messages['facebook'] as $message)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $message['from'] }}</h5>
                        <p class="card-text">{{ $message['message'] }}</p>
                        <a href="{{ route('messages.show', ['id' => $message['id'], 'type' => 'facebook']) }}" class="btn btn-primary">View</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="col-md-3">
            <h2>Tracked Emails</h2>
            @foreach($trackedEmails as $email)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">{{ $email->subject }}</h5>
                        <p class="card-text">From: {{ $email->sender }}</p>
                        <p class="card-text">To: {{ $email->recipient }}</p>
                        <p class="card-text">{{ \Illuminate\Support\Str::limit($email->content, 100) }}</p>
                        <p class="card-text">
                            <small class="text-muted">{{ $email->timestamp->diffForHumans() }}</small>
                            <span class="badge {{ $email->is_sent ? 'bg-success' : 'bg-primary' }}">{{ $email->is_sent ? 'Sent' : 'Received' }}</span>
                        </p>
                        <a href="{{ route('emails.show', $email->id) }}" class="btn btn-primary">View</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection