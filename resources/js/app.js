/**
 * Contribution of one vote to the score: +1, -1 or nothing.
 */
const contribution = (vote) => vote ?? 0;

async function postJson(url, body = {}) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify(body),
    });

    return response.ok ? response.json() : null;
}

document.addEventListener('alpine:init', () => {
    Alpine.data('copypastaActions', ({
        body,
        copyUrl,
        voteUrl,
        favoriteUrl,
        shareUrl,
        shareTitle,
        authenticated,
        score,
        myVote,
        isFavorite,
        favoritesCount,
        copiedMessage,
        linkCopiedMessage,
        loginRequiredVoteMessage,
        loginRequiredFavoriteMessage,
        actionFailedMessage,
    }) => ({
        revealed: false,
        score,
        myVote,
        isFavorite,
        favoritesCount,

        async copy() {
            await navigator.clipboard.writeText(body);

            this.countCopy();
            this.toast(copiedMessage);
        },

        async share() {
            if (navigator.share) {
                try {
                    await navigator.share({ title: shareTitle, url: shareUrl });
                } catch (error) {
                    // The visitor dismissed the share sheet; nothing to report.
                }

                return;
            }

            await navigator.clipboard.writeText(shareUrl);
            this.toast(linkCopiedMessage);
        },

        countCopy() {
            fetch(copyUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    Accept: 'application/json',
                },
            }).catch(() => {});
        },

        /**
         * Pressing the vote you already cast removes it; pressing the opposite one replaces it.
         * The UI moves first and the server's score replaces it when the request returns.
         */
        async vote(value) {
            if (! authenticated) {
                return this.requireLogin(loginRequiredVoteMessage);
            }

            const previous = { score: this.score, myVote: this.myVote };
            const next = this.myVote === value ? null : value;

            this.score = previous.score + contribution(next) - contribution(previous.myVote);
            this.myVote = next;

            const response = await postJson(voteUrl, { value });

            if (response === null) {
                Object.assign(this, previous);
                return this.toast(actionFailedMessage);
            }

            this.score = response.score;
            this.myVote = response.my_vote;
        },

        async toggleFavorite() {
            if (! authenticated) {
                return this.requireLogin(loginRequiredFavoriteMessage);
            }

            const previous = { isFavorite: this.isFavorite, favoritesCount: this.favoritesCount };

            this.isFavorite = ! this.isFavorite;
            this.favoritesCount = previous.favoritesCount + (this.isFavorite ? 1 : -1);

            const response = await postJson(favoriteUrl);

            if (response === null) {
                Object.assign(this, previous);
                return this.toast(actionFailedMessage);
            }

            this.isFavorite = response.favorited;
            this.favoritesCount = response.favorites_count;
        },

        requireLogin(message) {
            window.dispatchEvent(new CustomEvent('login-required', { detail: message }));
        },

        toast(message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: message }));
        },
    }));
});
