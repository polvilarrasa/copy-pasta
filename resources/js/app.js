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
    Alpine.data('copypastaFolders', ({ copypastaId, indexUrl, syncUrl, storeUrl, messages }) => ({
        visible: false,
        loading: false,
        folders: [],
        selected: [],
        newName: '',
        error: '',

        async open() {
            this.visible = true;
            this.error = '';
            this.loading = true;

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
            const response = await sendJson(syncUrl, 'PUT', { folder_ids: this.selected.map(Number) });

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

            const response = await sendJson(storeUrl, 'POST', { name });

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
        loginRequiredFolderMessage,
        actionFailedMessage,
    }) => ({
        revealed: false,
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
