<?php

namespace App\Livewire;

use App\Events\ContactUpdated;
use App\Models\Contact;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ContactCollaboration extends Component
{
    use WithPagination;

    public ?Contact $contact = null;

    public string $name = '';

    public string $email = '';

    public string $phone_number = '';

    public string $status = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $sortField = 'name';

    #[Url]
    public string $sortDirection = 'asc';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive',
        ];
    }

    public function mount(?Contact $contact = null): void
    {
        if ($contact instanceof \App\Models\Contact) {
            $this->contact = $contact;
            $this->name = $contact->name;
            $this->email = $contact->email;
            $this->phone_number = $contact->phone_number ?? '';
            $this->status = $contact->status;
        }
    }

    public function updateContact(): void
    {
        $this->validate();

        $this->contact->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
        ]);

        event(new ContactUpdated($this->contact));

        $this->dispatch('contactUpdated');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $contacts = Contact::query()
            ->when($this->search, fn ($query) => $query->where(function ($sub): void {
                $sub->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('phone_number', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.contact-collaboration', ['contacts' => $contacts]);
    }
}
