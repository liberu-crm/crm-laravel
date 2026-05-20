@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Task</h1>
    <form action="{{ route('tasks.update', $task->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Task Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $task->name }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="description" name="description">{{ $task->description }}</textarea>
        </div>
        <div class="form-group">
            <label for="due_date">Due Date</label>
            <input type="datetime-local" class="form-control" id="due_date" name="due_date" value="{{ $task->due_date->format('Y-m-d\TH:i') }}" required>
        </div>
        <div class="form-group">
            <label for="contact_id">Related Contact</label>
            <select class="form-control" id="contact_id" name="contact_id">
                <option value="">None</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}" {{ $task->contact_id == $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="lead_id">Related Lead</label>
            <select class="form-control" id="lead_id" name="lead_id">
                <option value="">None</option>
                @foreach($leads as $lead)
                    <option value="{{ $lead->id }}" {{ $task->lead_id == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="assigned_to">Assign To</label>
            <select class="form-control" id="assigned_to" name="assigned_to" required>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $task->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="reminder_date">Reminder Date</label>
            <input type="datetime-local" class="form-control" id="reminder_date" name="reminder_date" value="{{ $task->reminder_date ? $task->reminder_date->format('Y-m-d\TH:i') : '' }}">
        </div>
        <button type="submit" class="btn btn-primary">Update Task</button>
    </form>
</div>
@endsection