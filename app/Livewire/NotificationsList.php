<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\MarkNotificationsRead;
use App\Actions\OpenNotification;
use App\Models\User;
use App\Support\NotificationPresenter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationsList extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    #[Url]
    public string $tab = 'all';

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'unread' ? 'unread' : 'all';
        $this->resetPage();
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
     * The bell changed something: rendering again is enough.
     */
    #[On('notifications-updated')]
    public function refresh(): void {}

    public function render(): View
    {
        $paginator = $this->user()->notifications()
            ->when($this->tab === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('updated_at')
            ->paginate(self::PER_PAGE);

        return view('livewire.notifications-list', [
            'paginator' => $paginator,
            'items' => app(NotificationPresenter::class)->present(collect($paginator->items())),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
