<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Achievement;
use App\Enums\MilestoneMetric;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Models\Folder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Turns stored notification data into what the bell and the list show. The text comes from lang/es at display time and
 * the destination is looked up now: when the content is gone or no longer visible to the member, the item reads
 * "Contenido retirado" and has no link. All copy-pastas are loaded in one query, so a page of notifications costs one.
 */
class NotificationPresenter
{
    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<int, NotificationItem>
     */
    public function present(Collection $notifications): Collection
    {
        $copypastas = $this->copypastasFor($notifications);
        $folders = $this->foldersFor($notifications);

        return $notifications
            ->map(fn (DatabaseNotification $notification): ?NotificationItem => $this->item($notification, $copypastas, $folders))
            ->filter()
            ->values();
    }

    /**
     * @param  Collection<string, Copypasta>  $copypastas
     * @param  Collection<int, int>  $folders  ids of the folders that still exist
     */
    private function item(DatabaseNotification $notification, Collection $copypastas, Collection $folders): ?NotificationItem
    {
        $type = NotificationType::tryFrom($notification->type);

        if ($type === null) {
            return null;
        }

        $data = $notification->data;

        if ($type === NotificationType::AchievementUnlocked && Achievement::tryFrom((string) ($data['achievement'] ?? '')) === null) {
            return null;
        }

        $copypasta = $copypastas->get((string) ($data['copypasta_id'] ?? ''));

        [$text, $url] = match ($type) {
            NotificationType::Milestone => $this->milestone($data, $copypasta),
            NotificationType::CopypastaHidden => $this->ownContent('copypasta_hidden', $copypasta, visibleOnly: false),
            NotificationType::CopypastaRestored => $this->ownContent('copypasta_restored', $copypasta, visibleOnly: true),
            NotificationType::ReportAccepted => [__('notifications.report_accepted'), null],
            NotificationType::TrustedPromotion => [__('notifications.trusted_promotion'), route('stats.show')],
            NotificationType::AchievementUnlocked => $this->achievementUnlocked($data),
            NotificationType::FolderMadePrivate => $this->folderMadePrivate($data, $folders),
        };

        return new NotificationItem(
            id: $notification->getKey(),
            text: $text,
            url: $url,
            icon: $this->icon($type, $data),
            tone: $type->tone(),
            isRead: $notification->read_at !== null,
            at: $notification->updated_at ?? $notification->created_at,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string|null}
     */
    private function milestone(array $data, ?Copypasta $copypasta): array
    {
        if (! $this->isPubliclyAvailable($copypasta)) {
            return [__('notifications.removed'), null];
        }

        $highest = [];

        foreach ($data['milestones'] ?? [] as $milestone) {
            $metric = (string) $milestone['metric'];
            $highest[$metric] = max($highest[$metric] ?? 0, (int) $milestone['threshold']);
        }

        $parts = collect($highest)
            ->map(fn (int $threshold, string $metric): string => __('notifications.milestone.'.$metric, [
                'count' => number_format($threshold, 0, ',', '.'),
            ]))
            ->values()
            ->all();

        return [
            __('notifications.milestone.text', ['title' => $copypasta->title, 'milestones' => implode(__('notifications.milestone.and'), $parts)]),
            $this->urlFor($copypasta),
        ];
    }

    /**
     * Links to the achievements section of the member's own profile. The notifications listed are always the
     * signed-in member's, so the username comes from the session and costs no query.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string|null}
     */
    private function achievementUnlocked(array $data): array
    {
        $achievement = Achievement::from((string) $data['achievement']);
        $username = auth()->user()?->username;

        return [
            __('notifications.achievement_unlocked', ['name' => $achievement->name()]),
            $username === null ? null : route('profile.show', $username).'#logros',
        ];
    }

    /**
     * The folder the staff made private, with their reason. It links to the folder while it still exists, where the
     * owner sees the lock; the stored name is the one it had then.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, int>  $folders
     * @return array{0: string, 1: string|null}
     */
    private function folderMadePrivate(array $data, Collection $folders): array
    {
        $folderId = (int) ($data['folder_id'] ?? 0);

        return [
            __('notifications.folder_made_private', ['name' => (string) ($data['name'] ?? ''), 'reason' => (string) ($data['reason'] ?? '')]),
            $folders->contains($folderId) ? route('folders.show', $folderId) : null,
        ];
    }

    /**
     * Notifications about the member's own copy-pasta. The author can open it while it is hidden, so only deletion
     * removes the destination of a "hidden" notification; a "restored" one needs it to be visible again.
     *
     * @return array{0: string, 1: string|null}
     */
    private function ownContent(string $key, ?Copypasta $copypasta, bool $visibleOnly): array
    {
        $available = $visibleOnly ? $this->isPubliclyAvailable($copypasta) : ($copypasta !== null && $copypasta->deleted_at === null);

        if (! $available) {
            return [__('notifications.removed'), null];
        }

        return [__('notifications.'.$key, ['title' => $copypasta->title]), $this->urlFor($copypasta)];
    }

    /**
     * @phpstan-assert-if-true Copypasta $copypasta
     */
    private function isPubliclyAvailable(?Copypasta $copypasta): bool
    {
        return $copypasta !== null
            && $copypasta->deleted_at === null
            && $copypasta->published_at !== null
            && ! $copypasta->isHidden();
    }

    private function urlFor(Copypasta $copypasta): string
    {
        return route('copypastas.show', [$copypasta, $copypasta->slug]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function icon(NotificationType $type, array $data): string
    {
        if ($type === NotificationType::AchievementUnlocked) {
            return Achievement::tryFrom((string) ($data['achievement'] ?? ''))?->icon() ?? $type->icon();
        }

        if ($type !== NotificationType::Milestone) {
            return $type->icon();
        }

        $first = $data['milestones'][0]['metric'] ?? null;

        return $first === MilestoneMetric::Upvotes->value ? 'arrow-up' : $type->icon();
    }

    /**
     * The ids, among those the notifications mention, of the folders that still exist: one query for the whole page, and
     * none when no notification is about a folder.
     *
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<int, int>
     */
    private function foldersFor(Collection $notifications): Collection
    {
        $ids = $notifications
            ->filter(fn (DatabaseNotification $notification): bool => $notification->type === NotificationType::FolderMadePrivate->value)
            ->map(fn (DatabaseNotification $notification): int => (int) ($notification->data['folder_id'] ?? 0))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Folder::query()->whereIn('id', $ids)->pluck('id')->map(fn (mixed $id): int => (int) $id)->values();
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<string, Copypasta>
     */
    private function copypastasFor(Collection $notifications): Collection
    {
        $ids = $notifications
            ->map(fn (DatabaseNotification $notification): ?string => $notification->data['copypasta_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Copypasta::withTrashed()
            ->whereIn('id', $ids)
            ->get(['id', 'title', 'slug', 'published_at', 'hidden_at', 'deleted_at'])
            ->keyBy('id');
    }
}
