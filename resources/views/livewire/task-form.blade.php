<div>
    @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div>
            <label for="task-name">Name</label>
            <input id="task-name" type="text" wire:model="name">
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-description">Description</label>
            <textarea id="task-description" wire:model="description"></textarea>
            @error('description') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-due-date">Due date</label>
            <input id="task-due-date" type="datetime-local" wire:model="due_date">
            @error('due_date') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-reminder-date">Reminder date</label>
            <input id="task-reminder-date" type="datetime-local" wire:model="reminder_date">
            @error('reminder_date') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-contact">Contact</label>
            <select id="task-contact" wire:model="contact_id">
                <option value="">—</option>
                @foreach ($contacts as $contact)
                    <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                @endforeach
            </select>
            @error('contact_id') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-lead">Lead</label>
            <select id="task-lead" wire:model="lead_id">
                <option value="">—</option>
                @foreach ($leads as $lead)
                    <option value="{{ $lead->id }}">{{ $lead->name ?? $lead->id }}</option>
                @endforeach
            </select>
            @error('lead_id') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="task-assigned-to">Assigned to</label>
            <select id="task-assigned-to" wire:model="assigned_to">
                <option value="">—</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            @error('assigned_to') <span class="error">{{ $message }}</span> @enderror
        </div>

        <button type="submit">Save</button>
    </form>
</div>
