<?php

namespace App\Filament\App\Resources\ContactResource\Pages;

use App\Filament\App\Resources\ContactResource;
use App\Models\Contact;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\Request;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Handle the index request for the contacts list.
     */
    public function index(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        $query = Contact::query();

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone_number', 'like', '%'.$term.'%')
                    ->orWhere('company_size', 'like', '%'.$term.'%')
                    ->orWhere('industry', 'like', '%'.$term.'%');
            });
        }

        if ($request->filled('created_at')) {
            $query->where('created_at', '>=', $request->created_at);
        }

        $contacts = $query->get();

        return view('contacts.list', ['contacts' => $contacts]);
    }

    /**
     * Bulk delete contacts by IDs.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! empty($ids)) {
            Contact::whereIn('id', $ids)->delete();
        }

        return response()->json(['deleted' => count($ids)]);
    }

    /**
     * Autocomplete contacts by name or email.
     */
    public function autocomplete(Request $request)
    {
        $term = $request->input('query', '');

        $contacts = Contact::where(function ($q) use ($term): void {
            $q->where('name', 'like', $term.'%')
                ->orWhere('email', 'like', '%'.$term.'%');
        })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($contacts);
    }
}
