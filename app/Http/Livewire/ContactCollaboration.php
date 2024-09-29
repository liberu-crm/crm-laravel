<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Contact;
use App\Events\ContactUpdated;
use Illuminate\Support\Facades\Auth;

class ContactCollaboration extends Component
{
    public $contact;
    public $name;
    public $email;
    public $phone_number;
    public $status;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'phone_number' => 'nullable|string|max:20',
        'status' => 'required|string|in:active,inactive',
    ];

    public function mount(Contact $contact)
    {
        $this->contact = $contact;
        $this->name = $contact->name;
        $this->email = $contact->email;
        $this->phone_number = $contact->phone_number;
        $this->status = $contact->status;
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

    public function render()
    {
        return view('livewire.contact-collaboration');
    }
}