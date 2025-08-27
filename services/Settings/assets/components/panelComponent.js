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
                beforeSubmit: (form, formData, component) => {

                },
                onSubmitSuccess: (data, component) => {
                    component.success = true;
                },
                onSubmitError: (error, component) => {
                    component.responseMessage = error.message || 'Something went wrong';
                    component.error = true;
                },
                ...config
            },

            responseMessage: '',
            loading: false,
            success: false,
            error: false,


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

                this.loading = true;
                this.responseMessage = '';

                if (!this.config.panelId) {
                    this.loading = false;
                    this.responseMessage = 'Error: panelId is missing';
                    return;
                }

                const form = e.target.closest('form');
                if (!form) {
                    this.loading = false;
                    this.responseMessage = 'Error: Form not found';
                    return;
                }

                const formData = new FormData(form);
                formData.append('action', `${this.config.panelId}_submit`);
                formData.append('nonce', this.config.nonce);

                this.config.beforeSubmit(form, formData ,this);

                try {
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(formData)
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
                        this.success = false;
                        this.error = false;
                    }, this.config.messageTimeout);

                } catch (error) {
                    this.loading = false;
                    this.config.onSubmitError({ message: 'Network error' }, this);

                    setTimeout(() => {
                        this.responseMessage = '';
                        this.success = false;
                        this.error = false;
                    }, this.config.messageTimeout);

                }
            }
        };
    };

})(window.ntdst);