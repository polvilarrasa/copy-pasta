<?php

use App\Actions\PrepareEventPartition;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Pest\Browser\Execution;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Browser tests teardown
|--------------------------------------------------------------------------
|
| Pest Browser starts `playwright run-server` through `sh -c`, and when the run ends it only terminates that
| shell: the Node server survives as an orphan of init, one more per run. The servers started by this run are
| remembered while the tests execute and terminated when the process shuts down.
|
*/

pest()->afterEach(fn () => rememberPlaywrightServers())->in('Browser');

/**
 * Remembers the Playwright servers that descend from this process, and stops them when it shuts down. The graceful
 * SIGTERM is not enough on its own: a server whose parent shell is already gone can keep running after it.
 */
function rememberPlaywrightServers(): void
{
    static $servers = [];
    static $registered = false;

    if (! $registered) {
        $registered = true;

        register_shutdown_function(function () use (&$servers): void {
            if ($servers !== []) {
                $pids = implode(' ', $servers);

                shell_exec("kill -TERM {$pids} 2>/dev/null; sleep 0.3; kill -KILL {$pids} 2>/dev/null");
            }
        });
    }

    $children = [];
    $commands = [];

    foreach (explode("\n", (string) shell_exec('ps -eo pid=,ppid=,args=')) as $line) {
        if (preg_match('/^\s*(\d+)\s+(\d+)\s+(.*)$/', $line, $matches) === 1) {
            $children[(int) $matches[2]][] = (int) $matches[1];
            $commands[(int) $matches[1]] = $matches[3];
        }
    }

    $pending = [getmypid()];

    while ($pending !== []) {
        foreach ($children[array_pop($pending)] ?? [] as $pid) {
            $pending[] = $pid;

            if (str_contains($commands[$pid], 'playwright run-server') && ! str_starts_with($commands[$pid], 'sh ')) {
                $servers[$pid] = $pid;
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Signs in through the login form in the browser, answering the two-factor challenge with a real TOTP code
 * generated from the account's own secret when it has one enabled.
 */
function signInInBrowser(User $user): void
{
    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->press('@login-button');

    if ($user->hasEnabledTwoFactorAuthentication()) {
        $otp = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

        $page->assertPathIs('/two-factor-challenge')
            ->fill('code', $otp)
            ->press(__('auth.two_factor_challenge.continue'));
    }

    $page->assertPathIsNot('/login')->assertPathIsNot('/two-factor-challenge');
}

/**
 * The browser-side condition behind visitInteractive(): Alpine has bound every component on the page, and Livewire
 * has booted each of its own. Use it with assertScript() after a step that loads a new page, such as a redirect.
 */
function interactivePageScript(): string
{
    return <<<'JS'
        () => !!window.Alpine
            && [...document.querySelectorAll('[x-data]')].every((element) => element._x_dataStack !== undefined)
            && [...document.querySelectorAll('[wire\\:id]')].every((element) => !!window.Livewire?.find(element.getAttribute('wire:id')))
        JS;
}

/**
 * Opens a page and returns once it is interactive. Playwright only waits for an element to be actionable, not for
 * the scripts that give it behaviour, so a click or a keypress sent earlier can land on markup that does nothing yet.
 * The check retries until the browser timeout, so there is no fixed pause.
 */
function visitInteractive(string $url): mixed
{
    return visit($url)->assertScript(interactivePageScript());
}

/**
 * Retries the expectations until they hold or the browser timeout runs out, for server state that a page action
 * changes after the page has already updated, such as a row written by a request that is still in flight. It must
 * not block: the application served to the browser runs in this same process.
 *
 * It depends on Pest\Browser\Execution::waitForExpectation(), a class the plugin marks @internal, so a plugin update
 * can change it without notice. If it breaks, check in vendor/pestphp/pest-plugin-browser/src/Execution.php that the
 * method still exists, takes a callable and retries on PHPUnit's ExpectationFailedException without blocking the
 * event loop; if not, adapt this wrapper (the tests only call eventually()).
 */
function eventually(callable $expectations): void
{
    Execution::instance()->waitForExpectation($expectations);
}

/**
 * Creates the monthly `events` partitions around today, so tests can write events on any date near now.
 */
function prepareEventPartitions(): void
{
    foreach (range(-2, 1) as $offset) {
        app(PrepareEventPartition::class)->handle(now()->startOfMonth()->addMonths($offset));
    }
}

/**
 * The running value of an achievement metric for the member, as stored in user_achievement_progress.
 */
function achievementProgress(User $user, AchievementMetric $metric): int
{
    return (int) DB::table('user_achievement_progress')
        ->where('user_id', $user->getKey())
        ->where('metric', $metric->value)
        ->value('value');
}

/**
 * Whether the member holds the achievement and it has not been revoked.
 */
function holdsAchievement(User $user, Achievement $achievement): bool
{
    return $user->achievements()->where('achievement_key', $achievement->value)->whereNull('revoked_at')->exists();
}

/**
 * The stored affinity score of the member with the tag, or null when there is no row.
 */
function storedAffinity(User $user, Tag $tag): ?float
{
    $score = DB::table('user_tag_affinities')->where('user_id', $user->getKey())->where('tag_id', $tag->getKey())->value('score');

    return $score === null ? null : (float) $score;
}
