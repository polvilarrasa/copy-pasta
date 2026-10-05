<?php

declare(strict_types=1);

use App\Support\CrawlerDetector;
use Illuminate\Http\Request;

function requestWithUserAgent(?string $userAgent): Request
{
    $request = Request::create('/c/ejemplo', 'GET', server: ['HTTP_USER_AGENT' => $userAgent ?? '']);

    if ($userAgent === null) {
        // Request::create() fills in a default user agent; a real request without one has no such header at all.
        $request->headers->remove('User-Agent');
    }

    return $request;
}

test('reconoce los generadores de vista previa de cada plataforma', function (string $userAgent): void {
    expect(app(CrawlerDetector::class)->isBot(requestWithUserAgent($userAgent)))->toBeTrue();
})->with([
    'WhatsApp' => 'WhatsApp/2.23.20.0 A',
    'Telegram' => 'TelegramBot (like TwitterBot)',
    'Discord' => 'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
    'Slack' => 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
    'X' => 'Twitterbot/1.0',
    'Facebook' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    'Google' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
]);

test('no marca como bot a navegadores reales de móvil y escritorio', function (string $userAgent): void {
    expect(app(CrawlerDetector::class)->isBot(requestWithUserAgent($userAgent)))->toBeFalse();
})->with([
    'Chrome en Android' => 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Mobile Safari/537.36',
    'Safari en iPhone' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
    'Chrome en Windows' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
]);

test('una petición sin user agent cuenta como bot', function (): void {
    expect(app(CrawlerDetector::class)->isBot(requestWithUserAgent(null)))->toBeTrue()
        ->and(app(CrawlerDetector::class)->isBot(requestWithUserAgent('')))->toBeTrue();
});
