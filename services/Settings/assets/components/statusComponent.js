window.ntdst = window.ntdst || {};

((exports) => {
    exports.statusComponent = (data={}, config = {}) => {
        return {

            // Spread mixin to include isLoading, error, and ajaxRequest
            ...exports.ajaxMixin(),

            // Config object
            config: {
                ...config,
            },

            update_action: config.update_action || 'update_checkbox_status',
            get_action: config.get_action || 'get_checkbox_status',

            // Initialize isChecked from config or default to false
            isChecked: config.isChecked || false,

            init() {
                this.fetchStatus();
            },

            async fetchStatus() {
                try {
                    const response = await this.ajaxRequest(
                        this.config.ajaxUrl,
                        this.get_action,
                        this.config.nonce,
                        { ...data, status: this.isChecked }
                    );
                    this.isChecked = response.status;
                } catch (err) {
                    // Error is already set by the mixin
                }
            },

            async updateStatus() {
                const originalStatus = this.isChecked;
                try {
                    await this.ajaxRequest(
                        this.config.ajaxUrl,
                        this.update_action,
                        this.config.nonce,
                        { ...data, status: this.isChecked }
                    );
                } catch (err) {
                    this.isChecked = !originalStatus; // Revert on error
                }
            },


        };
    };

})(window.ntdst);