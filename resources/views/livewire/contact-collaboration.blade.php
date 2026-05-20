<div>
    @if($contact)
    <form wire:submit.prevent="updateContact">
        <div>
            <label for="name">Name</label>
            <input type="text" id="name" wire:model="name">
            @error('name') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" wire:model="email">
            @error('email') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="phone_number">Phone Number</label>
            <input type="text" id="phone_number" wire:model="phone_number">
            @error('phone_number') <span class="error">{{ $message }}</span> @enderror
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" wire:model="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            @error('status') <span class="error">{{ $message }}</span> @enderror
        </div>

        <button type="submit">Update Contact</button>
    </form>
    @endif

    <div>
        <input type="text" wire:model.live="search" placeholder="Search contacts..." />
        <select wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th wire:click="sortBy('name')" class="cursor-pointer">Name</th>
                <th wire:click="sortBy('email')" class="cursor-pointer">Email</th>
                <th wire:click="sortBy('status')" class="cursor-pointer">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contacts as $c)
                <tr wire:key="contact-{{ $c->id }}">
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->email }}</td>
                    <td>{{ $c->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $contacts->links() }}
</div>
