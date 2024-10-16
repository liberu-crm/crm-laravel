<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Contact;
use App\Events\ContactUpdated;
use Illuminate\Support\Facades\Auth;

class ContactCollaboration extends Component
{
    use WithPagination;

    public $contact;
    public $name;
    public $email;
    public $phone_number;
    public $status;

    public $search = '';
    public $statusFilter = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';

    protected $queryString = ['search', 'statusFilter', 'sortField', 'sortDirection'];

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone_number' => 'nullable|string|max:20',
        'status' => 'required|string|in:active,inactive',
    ];

    public function mount(Contact $contact = null)
    {
        if ($contact) {
            $this->contact = $contact;
            $this->name = $contact->name;
            $this->email = $contact->email;
            $this->phone_number = $contact->phone_number;
            $this->status = $contact->status;
        }
    }

    public function updateContact()
    {
        $this->validate();

        $this->contact->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
        ]);

        event(new ContactUpdated($this->contact));

        $this->emit('contactUpdated');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function render()
    {
        $contacts = Contact::query()
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);

        return view('livewire.contact-collaboration', [
            'contacts' => $contacts,
        ]);
    }
}