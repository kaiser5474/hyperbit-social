export const postIndex = () => ({
    loading: false,
    controller: null,
    onPopState: null,

    init() {
        this.onPopState = () => {
            this.load(window.location.href, false);
        };

        window.addEventListener('popstate', this.onPopState);
    },

    destroy() {
        if (this.onPopState) {
            window.removeEventListener('popstate', this.onPopState);
        }

        this.controller?.abort();
    },

    handleClick(event) {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const anchor = event.target.closest('a');

        if (!anchor) {
            return;
        }

        if (!anchor.hasAttribute('data-posts-nav') && !anchor.closest('[data-posts-pagination]')) {
            return;
        }

        event.preventDefault();
        this.load(anchor.href, true);
    },

    async load(url, push) {
        this.controller?.abort();

        const controller = new AbortController();
        this.controller = controller;
        this.loading = true;

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                headers: {
                    'X-Posts-Panel': '1',
                    Accept: 'text/html',
                },
            });

            if (!response.ok) {
                window.location.href = url;

                return;
            }

            const html = await response.text();
            this.$refs.panel.innerHTML = html;

            if (push) {
                window.history.pushState({}, '', url);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            window.location.href = url;
        } finally {
            if (this.controller === controller) {
                this.loading = false;
            }
        }
    },
});
