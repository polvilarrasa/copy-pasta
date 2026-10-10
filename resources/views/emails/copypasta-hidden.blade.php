<x-mail::message>
# {{ __('moderation.mail.hidden.heading') }}

{{ __('moderation.mail.hidden.body', ['title' => $copypasta->title]) }}

**{{ __('moderation.mail.hidden.reason') }}:** {{ $copypasta->hidden_reason }}

{{ __('moderation.mail.hidden.appeal', ['email' => config('mail.from.address')]) }}

{{ __('moderation.mail.hidden.footer') }}

<x-mail::button :url="route('copypastas.mine')">
{{ __('moderation.mail.hidden.button') }}
</x-mail::button>
</x-mail::message>
