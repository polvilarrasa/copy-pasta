<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class PublishCopypastaController extends Controller
{
    /**
     * Any authenticated member reaches this page; the view itself shows the form or an email-verification
     * notice, so an unverified member sees why they cannot publish instead of a bare 403.
     */
    public function create(): Response
    {
        return response()->view('public.publish');
    }
}
