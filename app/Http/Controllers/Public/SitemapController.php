<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Copypasta;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /** The sitemap protocol allows at most 50,000 URLs per file. */
    public const MAX_COPYPASTA_URLS = 50000;

    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn (): string => view('public.sitemap', [
            'staticUrls' => [route('home'), route('feed.top-week'), route('feed.newest')],
            'copypastas' => Copypasta::query()
                ->visible()
                ->where('is_nsfw', false)
                ->orderByDesc('published_at')
                ->limit(self::MAX_COPYPASTA_URLS)
                ->get(['id', 'slug', 'edited_at', 'published_at']),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
