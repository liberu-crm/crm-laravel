<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\SocialMediaPostResource\Pages;

use App\Filament\App\Resources\SocialMediaPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSocialMediaPost extends CreateRecord
{
    protected static string $resource = SocialMediaPostResource::class;
}
