@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Tasks</h1>
    <a href="{{ route('tasks.create') }}" class="btn btn-primary mb-3">Create New Task</a>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Related To</th>
                <th>Assigned To</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
            <tr>
                <td>{{ $task->name }}</td>
                <td>{{ $task->due_date->format('Y-m-d H:i') }}</td>
                <td>{{ ucfirst($task->status) }}</td>
                <td>
                    @if($task->contact)
                        Contact: {{ $task->contact->name }}
                    @elseif($task->lead)
                        Lead: {{ $task->lead->name }}
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $task->assignedTo->name }}</td>
                <td>
                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    @if($task->status !== 'completed')
                        <form action="{{ route('tasks.complete', $task->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">Complete</button>
                        </form>
                    @else
                        <form action="{{ route('tasks.incomplete', $task->id) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-warning">Mark Incomplete</button>
                        </form>
                    @endif
                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this task?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection