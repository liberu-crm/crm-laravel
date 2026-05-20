@foreach ($contacts as $contact)
    <div>{{ $contact->name }} {{ $contact->email }}</div>
@endforeach
