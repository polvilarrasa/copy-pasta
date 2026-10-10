<?php

declare(strict_types=1);

namespace App\Enums;

use App\Notifications\AchievementUnlockedNotification;
use App\Notifications\AppNotification;
use App\Notifications\CopypastaHiddenNotification;
use App\Notifications\CopypastaMilestoneNotification;
use App\Notifications\CopypastaRestoredNotification;
use App\Notifications\ReportAcceptedNotification;
use App\Notifications\TrustedPromotionNotification;

/**
 * The single registry of in-app notification types. Adding one takes three steps: a case here (with its class, icon,
 * tone and whether it can be switched off), a class extending AppNotification, and its lines in lang/es/notifications.php.
 * The presenter that turns the stored data into text and a destination (App\Support\NotificationPresenter) gets one branch.
 */
enum NotificationType: string
{
    case Milestone = 'milestone';
    case CopypastaHidden = 'copypasta_hidden';
    case CopypastaRestored = 'copypasta_restored';
    case ReportAccepted = 'report_accepted';
    case TrustedPromotion = 'trusted_promotion';
    case AchievementUnlocked = 'achievement_unlocked';

    /**
     * @return class-string<AppNotification>
     */
    public function notificationClass(): string
    {
        return match ($this) {
            self::Milestone => CopypastaMilestoneNotification::class,
            self::CopypastaHidden => CopypastaHiddenNotification::class,
            self::CopypastaRestored => CopypastaRestoredNotification::class,
            self::ReportAccepted => ReportAcceptedNotification::class,
            self::TrustedPromotion => TrustedPromotionNotification::class,
            self::AchievementUnlocked => AchievementUnlockedNotification::class,
        };
    }

    /**
     * Moderation decisions about the member's own content always arrive: they cannot be switched off.
     */
    public function isMandatory(): bool
    {
        return in_array($this, [self::CopypastaHidden, self::CopypastaRestored], true);
    }

    /**
     * Lucide icon name; the milestone icon depends on its metric and is chosen by the presenter.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Milestone => 'copy',
            self::CopypastaHidden => 'eye-off',
            self::CopypastaRestored => 'rotate-ccw',
            self::ReportAccepted => 'shield-check',
            self::TrustedPromotion => 'badge-check',
            self::AchievementUnlocked => 'trophy',
        };
    }

    /**
     * The design's tag palette slot (t1 to t5) used for the icon tile.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Milestone => 't1',
            self::CopypastaHidden => 't2',
            self::CopypastaRestored => 't1',
            self::ReportAccepted => 't3',
            self::TrustedPromotion => 't4',
            self::AchievementUnlocked => 't4',
        };
    }

    /**
     * @return list<self>
     */
    public static function configurable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => ! $type->isMandatory()));
    }

    /**
     * @return list<self>
     */
    public static function mandatory(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->isMandatory()));
    }
}
