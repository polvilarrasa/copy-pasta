<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\FeedSort;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function home(): Response
    {
        return $this->feed();
    }

    public function topWeek(): Response
    {
        return $this->feed(FeedSort::TopWeek);
    }

    public function topMonth(): Response
    {
        return $this->feed(FeedSort::TopMonth);
    }

    public function topAll(): Response
    {
        return $this->feed(FeedSort::TopAll);
    }

    public function newest(): Response
    {
        return $this->feed(FeedSort::Newest);
    }

    public function tag(string $slug): Response
    {
        $tag = Tag::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return $this->feed(tagSlug: $tag->slug);
    }

    private function feed(?FeedSort $fixedSort = null, ?string $tagSlug = null): Response
    {
        return response()->view('public.feed', [
            'fixedSort' => $fixedSort?->value,
            'tagSlug' => $tagSlug,
        ]);
    }
}
