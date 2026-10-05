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

/**
 * Sends a JSON request and reports the status and body, so callers can show server validation messages.
 */
async function sendJson(url, method, body = null) {
    const response = await fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: body === null ? null : JSON.stringify(body),
    });

    return { ok: response.ok, data: await response.json().catch(() => ({})) };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('copypastaReport', ({ copypastaId, url, messages }) => ({
        copypastaId,
        visible: false,
        sending: false,
        reason: '',
        details: '',
        error: '',
        context: {},

        open(context = {}) {
            this.visible = true;
            this.error = '';
            this.context = context;
        },

        close() {
            this.visible = false;
        },

        async submit() {
            this.sending = true;
            this.error = '';

            const response = await sendJson(url, 'POST', { reason: this.reason, details: this.details, ...this.context });

            this.sending = false;

            if (response.ok) {
                this.details = '';
                this.close();
                window.dispatchEvent(new CustomEvent('toast', { detail: messages.sent }));
                return;
            }

            if (response.status === 422) {
                this.error = response.data.errors?.details?.[0]
                    ?? response.data.errors?.reason?.[0]
                    ?? messages.failed;
                return;
            }

            this.error = response.status === 429 ? messages.rateLimited : messages.failed;
        },
    }));

    Alpine.data('copypastaFolders', ({ copypastaId, indexUrl, syncUrl, storeUrl, messages }) => ({
        copypastaId,
        messages,
        visible: false,
        loading: false,
        folders: [],
        selected: [],
        newName: '',
        error: '',
        context: {},

        async open(context = {}) {
            this.visible = true;
            this.error = '';
            this.loading = true;
            this.context = context;

            const response = await sendJson(indexUrl, 'GET');

            this.loading = false;

            if (! response.ok) {
                this.error = messages.failed;
                return;
            }

            this.folders = response.data.folders;
            this.selected = this.folders.filter((folder) => folder.contains).map((folder) => String(folder.id));
        },

        close() {
            this.visible = false;
        },

        async save() {
            const response = await sendJson(syncUrl, 'PUT', { folder_ids: this.selected.map(Number), ...this.context });

            if (! response.ok) {
                this.error = response.data.errors?.folder_ids?.[0] ?? messages.failed;
                return;
            }

            this.$dispatch('copypasta-folders-saved', response.data);
            this.toast(messages.saved);
            this.close();
        },

        async createFolder() {
            const name = this.newName.trim();

            if (name === '') {
                return;
            }

            const response = await sendJson(storeUrl, 'POST', { name, ...this.context });

            if (! response.ok) {
                this.error = response.data.errors?.name?.[0] ?? messages.failed;
                return;
            }

            this.folders.push(response.data.folder);
            this.selected.push(String(response.data.folder.id));
            this.newName = '';
            this.error = '';
            this.$dispatch('copypasta-folders-saved', response.data);
        },

        toast(message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: message }));
        },
    }));

    Alpine.data('copypastaActions', ({
        body,
        copyUrl,
        voteUrl,
        favoriteUrl,
        shareUrl,
        shareEventUrl,
        shareTitle,
        context = {},
        authenticated,
        score,
        myVote,
        isFavorite,
        favoritesCount,
        copiedMessage,
        linkCopiedMessage,
        loginRequiredVoteMessage,
        loginRequiredFavoriteMessage,
        loginRequiredFolderMessage,
        actionFailedMessage,
    }) => ({
        revealed: false,
        authenticated,
        context,
        loginRequiredFolderMessage,
        score,
        myVote,
        isFavorite,
        favoritesCount,

        /**
         * Keeps the star in step when the folder selector moves the copy-pasta into or out of Favoritos.
         */
        applyFolderState({ favorited, favorites_count }) {
            this.isFavorite = favorited;
            this.favoritesCount = favorites_count;
        },

        async copy() {
            await navigator.clipboard.writeText(body);

            this.countCopy();
            this.toast(copiedMessage);
        },

        /**
         * Records the share on the server and returns the link with its reference code. When the server cannot record
         * it, the plain link is shared, so sharing never depends on analytics.
         */
        async trackedShareUrl() {
            const response = await postJson(shareEventUrl, context);

            return response?.ref ? `${shareUrl}?ref=${response.ref}` : shareUrl;
        },

        async share() {
            const url = await this.trackedShareUrl();

            if (navigator.share) {
                try {
                    await navigator.share({ title: shareTitle, url });
                } catch (error) {
                    // The visitor dismissed the share sheet; nothing to report.
                }

                return;
            }

            await navigator.clipboard.writeText(url);
            this.toast(linkCopiedMessage);
        },

        countCopy() {
            fetch(copyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(context),
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

            const response = await postJson(voteUrl, { value, ...context });

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

            const response = await postJson(favoriteUrl, context);

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
