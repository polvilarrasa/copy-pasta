@props(['copypasta', 'source' => null, 'position' => null])

{{-- Maps a real Copypasta model onto the presentational ui.copypasta-card, which wires up its own interactivity. --}}
<x-ui.copypasta-card
    :copypasta="$copypasta"
    :source="$source"
    :position="$position"
    :title="$copypasta->title"
    :body="$copypasta->body"
    :author="$copypasta->user->displayName()"
    :author-hue="\App\Support\AvatarColor::hueFor($copypasta->user_id)"
    :author-username="$copypasta->user->isAnonymized() || $copypasta->user->isBanned() ? null : $copypasta->user->username"
    :score="$copypasta->score"
    :my-vote="$copypasta->my_vote"
    :saved="(bool) $copypasta->is_favorite"
    :nsfw="$copypasta->is_nsfw"
    :tags="$copypasta->tags->map(fn ($tag) => ['name' => $tag->name, 'color' => $tag->color])->all()"
    {{ $attributes }}
/>
