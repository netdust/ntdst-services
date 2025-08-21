window.ntdst = window.ntdst || {};

((exports) => {
    exports.panelComponent = (config = {}) => {
        return {
            config: {
                ajaxUrl: window.wpVars?.ajaxUrl || '',
                nonce: window.wpVars?.panelNonce || '',
                panelId: '',
                open: true,
                messageTimeout: 3000,
                onSubmitSuccess: (data, component) => {
                    component.responseMessage = data.message || 'Success!';
                    component.inputValue = '';
                },
                onSubmitError: (error, component) => {
                    component.responseMessage = error.message || 'Something went wrong';
                },
                ...config
            },

            inputValue: '',
            responseMessage: '',
            loading: false,


            init() {
                if (!this.config.panelId) {
                    console.error('panelManager: panelId is required in config');
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
                    this.responseMessage = 'Error: panelId is missing';
                    return;
                }

                this.loading = true;
                this.responseMessage = '';

                const form = e.target.closest('form');
                if (!form) {
                    this.loading = false;
                    this.responseMessage = 'Error: Form not found';
                    return;
                }

                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: `${this.config.panelId}_submit`,
                            nonce: this.config.nonce,
                            data: JSON.stringify(data)
                        })
                    });

                    const json = await response.json();
                    this.loading = false;

                    if (json.success) {
                        this.config.onSubmitSuccess(json.data, this);
                    } else {
                        this.config.onSubmitError(json.data || { message: 'Something went wrong' }, this);
                    }

                    setTimeout(() => {
                        this.responseMessage = '';
                    }, this.config.messageTimeout);
                } catch (error) {
                    this.loading = false;
                    this.config.onSubmitError({ message: 'Network error' }, this);
                    setTimeout(() => {
                        this.responseMessage = '';
                    }, this.config.messageTimeout);
                }
            }
        };
    };

})(window.ntdst);