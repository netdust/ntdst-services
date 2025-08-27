window.ntdst = window.ntdst || {};

((exports, util) => {

    // Utility to resolve a string path to a function
    const resolveFunction = (path) => {
        if (!path) return null;
        try {
            if (typeof path === 'function') {
                return path;
            }
            return path.split('.').reduce((obj, key) => obj[key], window);
        } catch (e) {
            console.error(`Failed to resolve function: ${path}`, e);
            return null;
        }
    };

    // AJAX Mixin
    exports.ajaxMixin = () => ({
        isLoading: false,
        success: null,
        error: null,
        async ajaxRequest(url, action, nonce, data = {}, handleResponse = null) {
            this.isLoading = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action, nonce, ...data }),
                });

                const responseHandler = resolveFunction(handleResponse);
                if (typeof responseHandler === 'function') {
                    const result = await responseHandler(response, data);
                    if (result.success) {
                        this.success = true;
                        setTimeout(() => {
                            this.success = '';
                        }, 3000);
                        return result.success;
                    }
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.data?.message || 'Request failed');
                }
                return result.data;
            } catch (err) {
                this.error = err.message || 'Network error occurred';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
    });

    exports.admin = {
        start() {
            // Ensure Alpine is available
            window.Alpine = window.Alpine || {};

            Alpine.data('actionComponent', (data = {}, config = {}) => window.ntdst.actionComponent(data, {
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.actionNonce ||  '',
                ...config
            }));

            Alpine.data('statusComponent', (data = {}, config = {}) => window.ntdst.statusComponent(data, {
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.statusNonce ||  '',
                ...config
            }));

            Alpine.data('panelComponent', (config = {}) => window.ntdst.panelComponent({
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.panelNonce || '',
                ...config
            }));


            Alpine.data('tableComponent', (config = {}) => window.ntdst.tableComponent({
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.tableNonce || '',
                cacheClearNonce: window.wpConfig?.cacheNonce || '',
                actions: window.ntdst.tableActions || {},
                ...config
            }));
        }
    };

    // Initialize on DOM ready
    util.ready(() => exports.admin.start());


})(window.ntdst,{ ready: (fn) => document.addEventListener('alpine:init', fn) } );

