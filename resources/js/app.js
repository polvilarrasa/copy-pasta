document.addEventListener('alpine:init', () => {
    Alpine.data('copypastaActions', ({ body, copyUrl, shareUrl, shareTitle, copiedMessage, linkCopiedMessage }) => ({
        revealed: false,

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

        toast(message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: message }));
        },
    }));
});
