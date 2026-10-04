<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
}
