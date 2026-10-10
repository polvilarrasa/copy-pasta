<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The single registry of achievements. Each case knows its family, the metric it reads and the threshold on it, the
 * optional title it unlocks, whether it is secret and how it looks. Granting one is always "metric >= threshold", so
 * progress needs no per-achievement code.
 *
 * Adding one is a case here plus its lines in lang/es/achievements.php (name, description and, with a title, the
 * title). Difusion (Fase 20) and Variantes and Plantillas (Fase 21) are added the same way.
 */
enum Achievement: string
{
    case FirstPaste = 'first_paste';
    case HabitualPaster = 'habitual_paster';
    case PasteFactory = 'paste_factory';

    case FirstApplause = 'first_applause';
    case WellReceived = 'well_received';
    case PublicFavorite = 'public_favorite';
    case Legend = 'legend';

    case Copied = 'copied';
    case Viral = 'viral';
    case InternetHeritage = 'internet_heritage';

    case Trending = 'trending';

    case FirstFolder = 'first_folder';
    case Collector = 'collector';

    case Vigilante = 'vigilante';
    case Guardian = 'guardian';
    case Sentinel = 'sentinel';

    case Critic = 'critic';

    case Veteran = 'veteran';

    case NightOwl = 'night_owl';
    case Dynamite = 'dynamite';

    public function family(): AchievementFamily
    {
        return match ($this) {
            self::FirstPaste, self::HabitualPaster, self::PasteFactory => AchievementFamily::Creator,
            self::FirstApplause, self::WellReceived, self::PublicFavorite, self::Legend => AchievementFamily::Popularity,
            self::Copied, self::Viral, self::InternetHeritage => AchievementFamily::Copies,
            self::Trending => AchievementFamily::Trending,
            self::FirstFolder, self::Collector => AchievementFamily::Collector,
            self::Vigilante, self::Guardian, self::Sentinel => AchievementFamily::Guardian,
            self::Critic => AchievementFamily::Voter,
            self::Veteran => AchievementFamily::Veteran,
            self::NightOwl, self::Dynamite => AchievementFamily::Secret,
        };
    }

    public function metric(): AchievementMetric
    {
        return match ($this) {
            self::FirstPaste, self::HabitualPaster, self::PasteFactory => AchievementMetric::Published,
            self::FirstApplause, self::WellReceived, self::PublicFavorite, self::Legend => AchievementMetric::UpvotesReceived,
            self::Copied, self::Viral, self::InternetHeritage => AchievementMetric::CopiesReceived,
            self::Trending => AchievementMetric::TrendingTop,
            self::FirstFolder => AchievementMetric::FoldersCreated,
            self::Collector => AchievementMetric::Saved,
            self::Vigilante, self::Guardian, self::Sentinel => AchievementMetric::ReportsAccepted,
            self::Critic => AchievementMetric::VotesCast,
            self::Veteran => AchievementMetric::AccountDays,
            self::NightOwl => AchievementMetric::NightPublications,
            self::Dynamite => AchievementMetric::Dynamite,
        };
    }

    /**
     * The value of the metric from which the achievement is granted.
     */
    public function threshold(): int
    {
        return match ($this) {
            self::FirstPaste, self::FirstApplause, self::Trending, self::FirstFolder, self::Vigilante,
            self::NightOwl, self::Dynamite => 1,
            self::HabitualPaster, self::WellReceived, self::Copied, self::Guardian => 10,
            self::Collector, self::Sentinel => 50,
            self::PasteFactory, self::PublicFavorite, self::Viral, self::Critic => 100,
            self::Legend, self::InternetHeritage => 1000,
            self::Veteran => 365,
        };
    }

    /**
     * The key of the title this achievement unlocks (lang `achievements.titles.{key}`, stored in users.title_key), or
     * null when it gives none.
     */
    public function titleKey(): ?string
    {
        return match ($this) {
            self::FirstApplause, self::Copied, self::FirstFolder, self::Vigilante, self::Critic => null,
            default => $this->value,
        };
    }

    /**
     * Secrets show as "???" without a description until the member earns them, and have no progress bar.
     */
    public function isSecret(): bool
    {
        return $this->family() === AchievementFamily::Secret;
    }

    /**
     * Lucide icon name.
     */
    public function icon(): string
    {
        return match ($this) {
            self::FirstPaste => 'clipboard-paste',
            self::HabitualPaster => 'clipboard-list',
            self::PasteFactory => 'factory',
            self::FirstApplause => 'thumbs-up',
            self::WellReceived => 'heart',
            self::PublicFavorite => 'star',
            self::Legend => 'crown',
            self::Copied => 'copy',
            self::Viral => 'zap',
            self::InternetHeritage => 'landmark',
            self::Trending => 'trending-up',
            self::FirstFolder => 'folder-plus',
            self::Collector => 'folders',
            self::Vigilante => 'shield',
            self::Guardian => 'shield-check',
            self::Sentinel => 'shield-alert',
            self::Critic => 'vote',
            self::Veteran => 'medal',
            self::NightOwl => 'moon',
            self::Dynamite => 'bomb',
        };
    }

    /**
     * The design's tag palette slot (t1 to t5) used for the icon tile.
     */
    public function tone(): string
    {
        return match ($this->family()) {
            AchievementFamily::Creator => 't1',
            AchievementFamily::Popularity => 't5',
            AchievementFamily::Copies => 't4',
            AchievementFamily::Trending => 't2',
            AchievementFamily::Collector => 't3',
            AchievementFamily::Guardian => 't1',
            AchievementFamily::Voter => 't3',
            AchievementFamily::Veteran => 't4',
            AchievementFamily::Secret => 't2',
        };
    }

    public function name(): string
    {
        return __('achievements.items.'.$this->value.'.name');
    }

    public function description(): string
    {
        return __('achievements.items.'.$this->value.'.description');
    }

    /**
     * The achievement that unlocks the given title key, if any.
     */
    public static function forTitleKey(string $titleKey): ?self
    {
        foreach (self::cases() as $achievement) {
            if ($achievement->titleKey() === $titleKey) {
                return $achievement;
            }
        }

        return null;
    }

    /**
     * @param  list<AchievementMetric>  $metrics
     * @return list<self>
     */
    public static function forMetrics(array $metrics): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $achievement): bool => in_array($achievement->metric(), $metrics, true),
        ));
    }
}
