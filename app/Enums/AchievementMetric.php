<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\User;

/**
 * What an achievement measures. The running value of every metric lives in user_achievement_progress, written in the
 * same transaction as the action that moves it; achievements are evaluated by reading it, never by counting events.
 * Adding a metric means a case here, the code that moves it, and its query in App\Actions\BackfillAchievements.
 */
enum AchievementMetric: string
{
    /** Copy-pastas of the member that exist now and are visible: published, not hidden by moderation, not deleted. */
    case Published = 'published';

    /** Copy-pastas published between 3:00 and 3:59 (app time zone). Never goes down. */
    case NightPublications = 'night_publications';

    /** Upvotes received from verified accounts at least 72 hours old when they voted, minus those withdrawn. */
    case UpvotesReceived = 'upvotes_received';

    /** Copies of the member's copy-pastas made by anyone but the member. Never goes down. */
    case CopiesReceived = 'copies_received';

    /** Flag: 1 once one of the member's copy-pastas has been in the weekly top 10. */
    case TrendingTop = 'trending_top';

    /** Folders the member has created, the default Favorites folder excluded. Never goes down. */
    case FoldersCreated = 'folders_created';

    /** Copy-pastas the member has in Favorites now. */
    case Saved = 'saved';

    /** Reports of the member that moderation accepted. Never goes down. */
    case ReportsAccepted = 'reports_accepted';

    /** Votes the member has cast and not withdrawn, up or down. */
    case VotesCast = 'votes_cast';

    /** Flag: 1 once one of the member's copy-pastas got 100 copies from others within 24 hours. */
    case Dynamite = 'dynamite';

    /** Days since the account was created. Derived from users.created_at, so it is not stored. */
    case AccountDays = 'account_days';

    /**
     * Whether the value is computed from the user instead of read from the progress table.
     */
    public function isDerived(): bool
    {
        return $this === self::AccountDays;
    }

    /**
     * A flag is 0 or 1 and, once 1, stays 1.
     */
    public function isFlag(): bool
    {
        return in_array($this, [self::TrendingTop, self::Dynamite], true);
    }

    public function derivedValue(User $user): int
    {
        return match ($this) {
            self::AccountDays => $user->created_at === null ? 0 : (int) $user->created_at->diffInDays(now(), absolute: true),
            default => 0,
        };
    }
}
