<?php

declare(strict_types=1);

namespace App\Enums;

enum ModerationActionType: string
{
    case Hide = 'hide';
    case Restore = 'restore';
    case MarkNsfw = 'mark_nsfw';
    case UnmarkNsfw = 'unmark_nsfw';
    case DismissReports = 'dismiss_reports';
    case Ban = 'ban';
    case Unban = 'unban';
    case ChangeRole = 'change_role';
    case SendPasswordReset = 'send_password_reset';
    case ImpersonateStart = 'impersonate_start';
    case ImpersonateEnd = 'impersonate_end';
    case TagCreated = 'tag_created';
    case TagUpdated = 'tag_updated';
    case UserCreated = 'user_created';
    case UserDeleted = 'user_deleted';
    case UserRestored = 'user_restored';
    case UserAnonymized = 'user_anonymized';
    case EmailVerified = 'email_verified';
    case EmailUnverified = 'email_unverified';
    case VerificationResent = 'verification_resent';
    case RevokeAchievement = 'revoke_achievement';
    case RestoreAchievement = 'restore_achievement';
    case ReplaceFeatured = 'replace_featured';
    case MakeFolderPrivate = 'make_folder_private';
}
