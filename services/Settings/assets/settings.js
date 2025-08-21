window.ntdst = window.ntdst || {};

((exports, util) => {


    exports.admin = {
        start() {
            // Ensure Alpine is available
            window.Alpine = window.Alpine || {};

            Alpine.data('panelComponent', (config = {}) => window.ntdst.panelComponent({
                ajaxUrl: window.wpConfig?.ajaxUrl || window.wpVars?.ajaxUrl || '',
                nonce: window.wpConfig?.panelNonce || window.wpVars?.panelNonce || '',
                ...config
            }));


            Alpine.data('tableComponent', (config = {}) => window.ntdst.tableComponent({
                ajaxUrl: window.wpConfig?.ajaxUrl || window.wpVars?.ajaxUrl || '',
                nonce: window.wpConfig?.tableNonce || window.wpVars?.tableNonce || '',
                cacheClearNonce: window.wpConfig?.cacheNonce || window.wpVars?.cacheNonce || '',
                actions: window.ntdst.tableActions || {},
                ...config
            }));
        }
    };

    // Initialize on DOM ready
    util.ready(() => exports.admin.start());


})(window.ntdst,{ ready: (fn) => document.addEventListener('alpine:init', fn) } );

