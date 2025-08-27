window.ntdst = window.ntdst || {};

((exports) => {
    exports.actionComponent = (data = {}, config = {}) => {
        return {
            ...exports.ajaxMixin(),

            config: {
                handleResponse: null,
                ...config,
            },

            action: config.action || 'do_selected_action',

            selected: config.selected || '',

            options: config.options || [],

            async doAction() {
                if (!this.selected) {
                    this.error = 'Please select an option.';
                    return;
                }

                try {

                    const response = await this.ajaxRequest(
                        this.config.ajaxUrl,
                        this.action,
                        this.config.nonce,
                        { ...data, selected: this.selected },
                        this.config.handleResponse

                    );

                } catch (err) {
                    // Error is handled by ajaxMixin
                }
            },
        };
    };
})(window.ntdst);