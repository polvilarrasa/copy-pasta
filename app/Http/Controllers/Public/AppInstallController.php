<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\RecordAppInstall;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppInstallController extends Controller
{
    public function __invoke(Request $request, RecordAppInstall $recordAppInstall): Response
    {
        $user = $request->user();

        $recordAppInstall->handle($user instanceof User ? $user : null);

        return response()->noContent();
    }
}
