<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\MarkNotificationsRead;
use App\Actions\OpenNotification;
use App\Models\User;
use App\Support\NotificationPresenter;
use App\Support\UnreadNotificationCount;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public const LIST_SIZE = 8;

    /** The list is only queried once the member opens the panel, so a page load costs a cached counter. */
    public bool $loaded = false;

    public string $tab = 'all';

    public function loadList(): void
    {
        $this->loaded = true;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'unread' ? 'unread' : 'all';
    }

    public function open(string $notificationId): void
    {
        $url = app(OpenNotification::class)->handle($this->user(), $notificationId);

        $this->dispatch('notifications-updated');

        if ($url !== null) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAllRead(): void
    {
        app(MarkNotificationsRead::class)->all($this->user());

        $this->dispatch('notifications-updated');
    }

    /**
     * The other notification component changed something: rendering again is enough.
     */
    #[On('notifications-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        $user = $this->user();

        $items = collect();

        if ($this->loaded) {
            $items = app(NotificationPresenter::class)->present(
                $user->notifications()
                    ->when($this->tab === 'unread', fn ($query) => $query->whereNull('read_at'))
                    ->orderByDesc('updated_at')
                    ->limit(self::LIST_SIZE)
                    ->get(),
            );
        }

        return view('livewire.notification-bell', [
            'unreadCount' => app(UnreadNotificationCount::class)->get($user),
            'items' => $items,
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
