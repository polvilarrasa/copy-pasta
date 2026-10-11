<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublishCopypastaController extends Controller
{
    /**
     * Any authenticated member reaches this page; the view itself shows the form or an email-verification
     * notice, so an unverified member sees why they cannot publish instead of a bare 403.
     *
     * This is also the web share target of the installed app: `title` and `text` in the query string start the form.
     * Without a session the `auth` middleware keeps the whole address, so the login returns here with them.
     */
    public function create(Request $request): Response
    {
        $title = $request->query('title');
        $text = $request->query('text');

        return response()->view('public.publish', [
            'prefillTitle' => is_string($title) ? $title : null,
            'prefillBody' => is_string($text) ? $text : null,
        ]);
    }
}
