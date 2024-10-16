@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create New Task</h1>
    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Task Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description"></textarea>
        </div>
        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="datetime-local" class="form-control" id="due_date" name="due_date" required>
        </div>
        <div class="form-group">
            <label for="contact_id">Related Contact</label>
            <select class="form-control" id="contact_id" name="contact_id">
                <option value="">None</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="lead_id">Related Lead</label>
            <select class="form-control" id="lead_id" name="lead_id">
                <option value="">None</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead->id }}">{{ $lead->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="assigned_to">Assign To</label>
            <select class="form-control" id="assigned_to" name="assigned_to" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="reminder_date">Reminder Date</label>
            <input type="datetime-local" class="form-control" id="reminder_date" name="reminder_date">
        </div>
        <button type="submit" class="btn btn-primary">Create Task</button>
    </form>
</div>
@endsection