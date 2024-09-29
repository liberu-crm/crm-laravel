@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Unified Helpdesk</h1>
    
    <div class="row">
        <div class="col-md-4">
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
        
        <div class="col-md-4">
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
        
        <div class="col-md-4">
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
    </div>
</div>
@endsection