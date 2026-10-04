<x-mail::message>
# {{ __('auth.invitation.mail.heading') }}

{{ __('auth.invitation.mail.body') }}

<x-mail::button :url="$url">
{{ __('auth.invitation.mail.button') }}
</x-mail::button>

{{ __('auth.invitation.mail.expires', ['hours' => $hours]) }}
</x-mail::message>
