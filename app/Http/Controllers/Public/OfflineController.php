<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class OfflineController extends Controller
{
    /**
     * The page the service worker shows when a navigation fails. It is static: no database, no session, no user.
     */
    public function __invoke(): View
    {
        return view('pwa.offline');
    }
}
