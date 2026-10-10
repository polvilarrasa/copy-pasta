<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\ComputeUserStats;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function show(ComputeUserStats $computeUserStats): Response
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return response()->view('public.stats', [
            'stats' => $computeUserStats->handle($user),
        ]);
    }
}
