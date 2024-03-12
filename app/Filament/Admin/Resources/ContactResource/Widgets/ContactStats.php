<?php

namespace App\Filament\Admin\Resources\ContactResource\Widgets;

use Filament\Widgets\Widget;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class ContactStats extends Widget
{
    protected static ?string $view = 'filament.widgets.contact-stats';

    public function render(): \Illuminate\View\View
    {
        $totalContacts = Contact::count();
        $recentContacts = Contact::where('created_at', '>', now()->subDays(7))->count();
        $categorizations = Contact::select('category', DB::raw('count(*) as total'))
                                  ->groupBy('category')
                                  ->get();

        return view(static::$view)
            ->with([
                'totalContacts' => $totalContacts,
                'recentContacts' => $recentContacts,
                'categorizations' => $categorizations,
            ]);
    }
}
