<?php

namespace App\Filament\Admin\Resources\ContactResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ContactStats extends Widget
{
    protected string $view = 'filament.admin.resources.contact-resource.widgets.contact-stats';

    #[\Override]
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
