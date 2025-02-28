<?php

namespace App\Filament\Admin\Resources\ContactResource\Widgets;

use App\Models\Contact;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;

class ContactStats extends Widget
{
    protected static string $view = 'filament.admin.resources.contact-resource.widgets.contact-stats';

    public function render(): View
    {
        $totalContacts = DB::table('contacts')->count();
        $recentContacts = DB::table('contacts')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $categorizations = DB::table('contacts')
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->get();

        return view(static::$view, [
            'totalContacts' => $totalContacts,
            'recentContacts' => $recentContacts,
            'categorizations' => $categorizations,
        ]);
    }
}