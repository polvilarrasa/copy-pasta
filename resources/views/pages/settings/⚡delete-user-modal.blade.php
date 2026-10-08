<?php

use App\Actions\DeleteOwnAccount;
use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(DeleteOwnAccount $deleteOwnAccount, Logout $logout): void
    {
        abort_if(is_impersonating(), 403);

        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $deleteOwnAccount->handle(Auth::user());

        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-ui.modal
    id="confirm-user-deletion"
    :title="__('Are you sure you want to delete your account?')"
    :open="$errors->isNotEmpty()"
    class="max-w-lg"
>
    <form id="confirm-user-deletion-form" method="POST" wire:submit="deleteUser" class="space-y-6">
        <p class="text-base text-muted">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <x-ui.password-input wire:model="password" name="password" :label="__('Password')" />
    </form>

    <x-slot:actions>
        <x-ui.button variant="danger" type="submit" form="confirm-user-deletion-form" data-test="confirm-delete-user-button">
            {{ __('Delete account') }}
        </x-ui.button>
    </x-slot:actions>
</x-ui.modal>
