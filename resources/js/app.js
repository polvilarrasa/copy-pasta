import focus from '@alpinejs/focus';
import './pwa';
import { SHARE_IMAGE_STYLES, canvasToBlob, drawShareImage, loadShareImageFonts } from './share-image';

/**
 * Contribution of one vote to the score: +1, -1 or nothing.
 */
const contribution = (vote) => vote ?? 0;

/**
 * The toast every action shares: window.dispatchEvent(ui-toast) is what <x-ui.toast /> listens to.
 */
function showToast(message) {
    window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message } }));
}

/**
 * Posts JSON and returns the decoded body, or null when the request fails: a non-2xx answer and no network at all
 * (fetch rejecting) are the same to the callers, who put their optimistic state back.
 */
async function postJson(url, body = {}) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(body),
        });

        return response.ok ? await response.json() : null;
    } catch (error) {
        return null;
    }
}

/**
 * Sends a JSON request and reports the status and body, so callers can show server validation messages. Without a
 * network the status is 0.
 */
async function sendJson(url, method, body = null) {
    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: body === null ? null : JSON.stringify(body),
        });

        return { ok: response.ok, status: response.status, data: await response.json().catch(() => ({})) };
    } catch (error) {
        return { ok: false, status: 0, data: {} };
    }
}

/**
 * The `{{name}}` variables of a template: letters (accents included), digits and underscores, up to 30 characters,
 * spaces allowed inside the braces. It is the same rule as App\Support\TemplateVariables, so keep both in step.
 */
const TEMPLATE_VARIABLE = /\{\{[ \t]*([\p{L}\p{N}_]{1,30})[ \t]*\}\}/gu;

const TEMPLATE_MAX_VALUE_LENGTH = 200;

/**
 * The distinct variables of a body, in order of first appearance. Names that differ only in case are one variable and
 * keep the label of their first appearance.
 */
function templateVariables(body) {
    const variables = new Map();

    for (const match of body.matchAll(TEMPLATE_VARIABLE)) {
        const key = match[1].toLowerCase();

        if (! variables.has(key)) {
            variables.set(key, match[1]);
        }
    }

    return [...variables].map(([key, label]) => ({ key, label }));
}

/**
 * Replaces every appearance of every variable that has a value. A variable with an empty value stays as it was written.
 */
function fillTemplate(body, values) {
    return body.replace(TEMPLATE_VARIABLE, (whole, name) => {
        const value = (values[name.toLowerCase()] ?? '').slice(0, TEMPLATE_MAX_VALUE_LENGTH);

        return value === '' ? whole : value;
    });
}

/**
 * Livewire's own handling of a failed request is an English confirm() box for an expired session and a raw HTML
 * modal for anything else. Here a 419 opens the Spanish "session expired" modal, a 429 says to slow down, and a
 * request that never reached the server (no network) says the action failed.
 */
document.addEventListener('livewire:init', () => {
    const messages = () => document.querySelector('[data-request-errors]')?.dataset ?? {};

    Livewire.interceptRequest(({ onError, onFailure }) => {
        onError(({ response, preventDefault }) => {
            if (response.status === 419) {
                preventDefault();
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'session-expired' }));
            } else if (response.status === 429) {
                preventDefault();
                showToast(messages().rateLimited);
            }
        });

        onFailure(() => showToast(messages().failed));
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.plugin(focus);

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
                window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message: messages.sent } }));
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
                this.toast(messages.failed);
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
                this.failedWithoutMessage(response);
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
                this.failedWithoutMessage(response);
                return;
            }

            this.folders.push(response.data.folder);
            this.selected.push(String(response.data.folder.id));
            this.newName = '';
            this.error = '';
            this.$dispatch('copypasta-folders-saved', response.data);
        },

        /**
         * A validation answer (422) explains itself inside the modal; any other failure, like having no network, also
         * gets the error toast.
         */
        failedWithoutMessage(response) {
            if (response.status !== 422) {
                this.toast(messages.failed);
            }
        },

        toast(message) {
            window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message } }));
        },
    }));

    /**
     * "Compartir carpeta": a public folder is shared straight away; a private one asks first, because sharing makes it
     * public. The server returns the public address either way.
     */
    Alpine.data('folderShare', ({ title, copiedMessage }) => ({
        share() {
            if (! this.$wire.isPublic) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'share-folder-confirm' }));

                return;
            }

            return this.go();
        },

        async go() {
            const url = await this.$wire.shareFolder();

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                } catch (error) {
                    // The member dismissed the share sheet; nothing to report.
                }

                return;
            }

            await navigator.clipboard.writeText(url);
            window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message: copiedMessage } }));
        },
    }));

    /**
     * The "Compartir como imagen" panel of the detail page. The image is drawn in the browser on a canvas. Adult
     * content asks for confirmation before anything is drawn. It is shared with the Web Share API as a file when the
     * browser can, and downloaded otherwise; either way the share is recorded with method=image.
     */
    Alpine.data('shareImage', ({
        title,
        body,
        author,
        brand,
        styleLabels,
        slug,
        nsfw,
        shareEventUrl,
        shareTitle,
        context = {},
        sharedMessage,
        downloadedMessage,
        failedMessage,
    }) => ({
        expanded: false,
        confirmed: ! nsfw,
        style: 'midnight',
        styles: Object.keys(SHARE_IMAGE_STYLES),
        swatches: Object.fromEntries(Object.entries(SHARE_IMAGE_STYLES).map(([key, value]) => [key, value.swatch])),
        styleLabels,
        busy: false,

        toggle() {
            this.expanded = ! this.expanded;

            if (this.expanded && this.confirmed) {
                this.$nextTick(() => this.draw());
            }
        },

        confirm() {
            this.confirmed = true;
            this.$nextTick(() => this.draw());
        },

        pick(style) {
            this.style = style;
            this.draw();
        },

        async draw() {
            await loadShareImageFonts();

            drawShareImage(this.$refs.canvas, { title, body, author, style: this.style, brand });
        },

        async file() {
            await this.draw();

            return new File([await canvasToBlob(this.$refs.canvas)], `${slug || 'copy-pasta'}.png`, { type: 'image/png' });
        },

        record() {
            postJson(shareEventUrl, { ...context, method: 'image' }).catch(() => {});
        },

        toast(message) {
            window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message } }));
        },

        canShareFile() {
            return typeof navigator.canShare === 'function' && typeof navigator.share === 'function';
        },

        async download() {
            this.busy = true;

            try {
                const file = await this.file();
                const url = URL.createObjectURL(file);
                const link = document.createElement('a');

                link.href = url;
                link.download = file.name;
                link.click();
                URL.revokeObjectURL(url);

                this.record();
                this.toast(downloadedMessage);
            } catch (error) {
                this.toast(failedMessage);
            } finally {
                this.busy = false;
            }
        },

        async share() {
            this.busy = true;

            try {
                const file = await this.file();

                if (! this.canShareFile() || ! navigator.canShare({ files: [file] })) {
                    return await this.download();
                }

                await navigator.share({ files: [file], title: shareTitle });

                this.record();
                this.toast(sharedMessage);
            } catch (error) {
                if (error?.name !== 'AbortError') {
                    this.toast(failedMessage);
                }
            } finally {
                this.busy = false;
            }
        },
    }));

    Alpine.data('copypastaActions', ({
        body,
        copyUrl,
        voteUrl,
        favoriteUrl,
        dismissUrl,
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
        dismissedMessage,
        undoLabel,
        undoneMessage,
        isTemplate = false,
        templateModalId = null,
    }) => ({
        revealed: false,
        templateFields: isTemplate ? templateVariables(body) : [],
        templateValues: {},
        dismissed: false,
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

        /**
         * A template opens the modal that asks for its variables; the copy happens from there.
         */
        async copy() {
            if (isTemplate) {
                this.$dispatch('open-modal', templateModalId);

                return;
            }

            if (! await this.writeToClipboard(body)) {
                return;
            }

            this.countCopy();
            this.toast(copiedMessage);
        },

        /**
         * The clipboard can refuse (permissions, an insecure page); the toast says the copy did not happen.
         */
        async writeToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);

                return true;
            } catch (error) {
                this.toast(actionFailedMessage);

                return false;
            }
        },

        /**
         * The template with the values typed so far. The values stay in the browser: the server only learns that a
         * template was copied.
         */
        get templatePreview() {
            return fillTemplate(body, this.templateValues);
        },

        async copyTemplate() {
            if (! await this.writeToClipboard(this.templatePreview)) {
                return;
            }

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

            if (await this.writeToClipboard(url)) {
                this.toast(linkCopiedMessage);
            }
        },

        countCopy() {
            fetch(copyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(isTemplate ? { ...context, template: true } : context),
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

        /**
         * "No me interesa": the card goes away at once and the toast offers to undo it, which brings the card back and
         * gives the affinity its points again. The server request that fails puts the card back.
         */
        async dismiss() {
            if (! authenticated) {
                return;
            }

            this.dismissed = true;

            const response = await sendJson(dismissUrl, 'POST', context);

            if (! response.ok) {
                this.dismissed = false;
                return this.toast(actionFailedMessage);
            }

            window.dispatchEvent(new CustomEvent('ui-toast', {
                detail: { message: dismissedMessage, actionLabel: undoLabel, action: () => this.undoDismiss() },
            }));
        },

        async undoDismiss() {
            const response = await sendJson(dismissUrl, 'DELETE', context);

            if (! response.ok) {
                return this.toast(actionFailedMessage);
            }

            this.dismissed = false;
            this.toast(undoneMessage);
        },

        requireLogin(message) {
            window.dispatchEvent(new CustomEvent('login-required', { detail: message }));
        },

        toast(message) {
            window.dispatchEvent(new CustomEvent('ui-toast', { detail: { message } }));
        },
    }));
});
