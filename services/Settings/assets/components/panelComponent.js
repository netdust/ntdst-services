window.ntdst = window.ntdst || {};

((exports) => {
    exports.panelComponent = (data = {}, config = {}) => {
        return {
            ...exports.ajaxMixin(), // isLoading, status, success, error, ajaxRequest

            config: {
                ajaxUrl: '',
                nonce: '',
                panelId: '',
                open: true,
                messageTimeout: 3000,
                beforeSend: null, // optional hook for global or panel-specific logic
                afterResponse: null, // optional custom response handler
                ...config
            },

            init() {
                if (!this.config.panelId) {
                    console.error('panelComponent: panelId is required in config');
                    return;
                }
                const stored = localStorage.getItem(`panel-open-${this.config.panelId}`);
                this.config.open = stored === null ? this.config.open : stored === 'true';
            },

            toggle() {
                this.config.open = !this.config.open;
                localStorage.setItem(`panel-open-${this.config.panelId}`, this.config.open);
            },

            async submit(e) {
                e.preventDefault();

                if (!this.config.panelId) {
                    this.setError('panelId is missing');
                    return;
                }

                const form = e.target.closest('form');
                if (!form) {
                    this.setError('Form not found');
                    return;
                }

                // Convert FormData to object
                const dataObject = Object.fromEntries(new FormData(form).entries());
                dataObject.action = `${this.config.panelId}_submit`;
                dataObject.nonce = this.config.nonce;

                try {
                    const result = await this.ajaxRequest({
                        url: this.config.ajaxUrl,
                        data: dataObject,
                        beforeSend: this.config.beforeSend,
                        afterResponse: this.config.afterResponse
                    });

                    if (result) {
                        this.success = true;
                    }

                } catch (err) {
                   //
                } finally {
                    setTimeout(() => {
                        this.success = false;
                        this.error = false;
                    }, this.config.messageTimeout);
                }
            }
        };
    };
})(window.ntdst);
