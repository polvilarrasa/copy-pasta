<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        return response()->view('public.notifications');
    }
}
