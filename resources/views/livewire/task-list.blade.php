<div>
    <div class="mb-4 flex gap-4">
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search tasks..."
            class="border rounded px-3 py-2 w-full"
        />
        <select wire:model.live="status" class="border rounded px-3 py-2">
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
        </select>
        <select wire:model.live="sortField" class="border rounded px-3 py-2">
            <option value="name">Name</option>
            <option value="due_date">Due Date</option>
            <option value="status">Status</option>
        </select>
    </div>

    <div class="mb-4">
        <select wire:model.live="leadFilter" class="border rounded px-3 py-2">
            <option value="">All Leads</option>
            @foreach($leads as $lead)
                <option value="{{ $lead->id }}">{{ $lead->name ?? $lead->id }}</option>
            @endforeach
        </select>
    </div>

    <table class="w-full border-collapse">
        <thead>
            <tr>
                <th class="border px-4 py-2 text-left cursor-pointer" wire:click="sortBy('name')">Name</th>
                <th class="border px-4 py-2 text-left cursor-pointer" wire:click="sortBy('due_date')">Due Date</th>
                <th class="border px-4 py-2 text-left cursor-pointer" wire:click="sortBy('status')">Status</th>
                <th class="border px-4 py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $task)
                <tr wire:key="task-{{ $task->id }}">
                    <td class="border px-4 py-2">{{ $task->name }}</td>
                    <td class="border px-4 py-2">{{ $task->due_date ? $task->due_date->format('Y-m-d') : '-' }}</td>
                    <td class="border px-4 py-2">{{ $task->status }}</td>
                    <td class="border px-4 py-2">
                        <button wire:click="deleteTask({{ $task->id }})" class="text-red-500 hover:text-red-700">Delete</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="border px-4 py-2 text-center text-gray-500">No tasks found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">
        {{ $tasks->links() }}
    </div>
</div>
