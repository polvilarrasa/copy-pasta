<?php

use App\Actions\PrepareEventPartition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * Creates the monthly `events` partitions around today, so tests can write events on any date near now.
 */
function prepareEventPartitions(): void
{
    foreach (range(-2, 1) as $offset) {
        app(PrepareEventPartition::class)->handle(now()->startOfMonth()->addMonths($offset));
    }
}
