<?php

declare(strict_types=1);

/*
 | Tuning of the "Para ti" feed. The weights are the points a signal adds to the member's affinity with every tag of the
 | copy-pasta; they live here because they will be adjusted with real data (run app:rebuild-affinities after changing
 | them). Favorite tags are not part of the stored score: they add `favorite_bonus` when the affinity is read.
 */
return [
    'weights' => [
        'copy' => 3.0,
        'favorite' => 3.0,
        'upvote' => 1.0,
        'downvote' => -2.0,
        'dismiss' => -3.0,
    ],

    /** A stored score loses half its value every this many days; the factor is applied when it is read or updated. */
    'half_life_days' => 30,

    'favorite_bonus' => 5.0,

    /** Copy-pastas carrying a tag whose effective affinity is below this never appear in "Para ti". */
    'exclusion_floor' => -5.0,

    'feed' => [
        'candidates' => 200,
        'cache_minutes' => 30,
        'page_size' => 20,
        'top_tags' => 5,
        'recent_hours' => 48,

        /** "Few votes": up and down votes together, at most this many. */
        'few_votes' => 5,

        'seen_limit' => 500,
        'seen_days' => 7,

        /** Slots of a page of 20 taken by exploration and by recently published copy-pastas; the rest is affinity. */
        'explore_slots' => [4, 9, 14, 18],
        'recent_slots' => [7, 16],

        /** Signals (votes, copies, favorites, dismissals) from which "Para ti" is the default tab. */
        'default_signals' => 5,
    ],

    'onboarding_min_tags' => 3,
];
