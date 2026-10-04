<x-mail::message>
# {{ __('moderation.mail.minors.heading') }}

{{ __('moderation.mail.minors.body', ['title' => $report->copypasta->title]) }}

<x-mail::button :url="$queueUrl">
{{ __('moderation.mail.minors.button') }}
</x-mail::button>
</x-mail::message>
