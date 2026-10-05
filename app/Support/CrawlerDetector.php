<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * Tells link-preview generators and crawlers apart from people. The library covers most platforms; the extra patterns
 * are the preview bots it does not list yet. A request without a user agent counts as a bot.
 */
final class CrawlerDetector
{
    /**
     * @var list<string>
     */
    private const EXTRA_BOT_NAMES = ['TelegramBot', 'Discordbot'];

    public function __construct(private CrawlerDetect $crawlerDetect) {}

    public function isBot(Request $request): bool
    {
        $userAgent = trim((string) $request->userAgent());

        if ($userAgent === '') {
            return true;
        }

        foreach (self::EXTRA_BOT_NAMES as $botName) {
            if (stripos($userAgent, $botName) !== false) {
                return true;
            }
        }

        return $this->crawlerDetect->isCrawler($userAgent);
    }
}
