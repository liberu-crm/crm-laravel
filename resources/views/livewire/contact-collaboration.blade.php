<div>
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
</div>