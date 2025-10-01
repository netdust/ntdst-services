window.ntdst = window.ntdst || {};

((exports, util) => {

    exports.ajaxMixin = (globalConfig = {}) => ({
        isLoading: false,
        status: "idle", // "idle" | "loading" | "success" | "error"
        error: null,
        success: null,
        controller: null,

        async ajaxRequest(options = {}) {
            const config = {
                beforeSend: globalConfig.beforeSend,
                afterResponse: globalConfig.afterResponse,
                url: ajaxurl, // WordPress global
                ...options,
            };

            let { url, action, nonce, data = {}, beforeSend, afterResponse } = config;

            this.isLoading = true;
            this.status = "loading";
            this.error = null;
            this.success = null;

            try {
                // 🔹 Cancel any ongoing request
                this.controller?.abort();
                this.controller = new AbortController();

                // 🔹 Pre-send hook
                if (typeof beforeSend === "function") {
                    const mutated = await beforeSend({ action, data });
                    if (mutated?.action) action = mutated.action;
                    if (mutated?.data) data = mutated.data;
                }

                const response = await fetch(url, {
                    method: "POST",
                    signal: this.controller.signal,
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams({ action, nonce, ...data }),
                });

                // 🔹 Custom response handler
                if (typeof afterResponse === "function") {
                    const customResult = await afterResponse(response, { action, data });
                    if (customResult !== undefined) {
                        this.setSuccess();
                        return customResult;
                    }
                }

                // 🔹 Default JSON handling
                const result = await response.json();
                if (!result.success) {
                    this.setError(result.data?.message || "Request failed");
                    return null;
                }

                this.setSuccess();
                return result.data;

            } catch (err) {
                if (err.name !== "AbortError") {
                    this.setError(err.message || "Network error occurred");
                    throw err;
                }
            } finally {
                this.isLoading = false;
            }
        },

        setSuccess(duration = 3000) {
            this.status = "success";
            this.success = true;
            setTimeout(() => {
                this.status = "idle";
                this.success = null;
            }, duration);
        },

        setError(message) {
            this.status = "error";
            this.error = message;
            exports.notifications.showToast(message, "error");
        },
    });

    // Utility to resolve a string path to a function
    exports.ajaxStateComponent = (data = {}, config = {}) => {
        return {
            ...exports.ajaxMixin(), // isLoading, status, success, error, ajaxRequest

            config: {
                ajaxUrl: ajaxurl,
                nonce: null,
                action: null,          // main AJAX action
                requireSelection: false,
                options: [],           // for dropdowns
                beforeSend: null,
                afterResponse: null,
                actions: {},           // optional local JS actions
                ...config,
            },

            value: config.value ?? null, // generic state: string, boolean, number, etc.
            options: config.options || [],

            init() {
                if (config.initAction) {
                    this.fetchInitialState();
                }
            },

            async fetchInitialState() {
                try {
                    const result = await this.ajaxRequest({
                        url: this.config.ajaxUrl,
                        action: this.config.initAction,
                        nonce: this.config.nonce,
                        data,
                        beforeSend: this.config.beforeSend,
                        afterResponse: this.config.afterResponse,
                    });

                    if (result?.value !== undefined) {
                        this.value = result.value;
                    }
                } catch (err) {
                    // error handled by ajaxMixin
                }
            },

            async doAction() {
                if (this.config.requireSelection && !this.value) {
                    this.setError("Please select a value.");
                    return;
                }

                try {
                    const result = await this.ajaxRequest({
                        url: this.config.ajaxUrl,
                        action: this.config.action,
                        nonce: this.config.nonce,
                        data: { ...data, value: this.value },
                        beforeSend: this.config.beforeSend,
                        afterResponse: this.config.afterResponse,
                    });

                    // Execute optional local JS actions returned by server
                    if (result?.action) {
                        const fn = this.config.actions?.[result.action];
                        if (typeof fn === "function") await fn(result, this);
                    }
                } catch (err) {
                    // error handled by ajaxMixin
                }
            },
        };
    };

    exports.multiSelectComponent = (data = {}, config = {}) => {
        return {
            ...exports.ajaxMixin(),

            config: {
                ajaxUrl: ajaxurl,
                nonce: null,
                action: null,
                requireSelection: false,
                options: [],
                beforeSend: null,
                afterResponse: null,
                actions: {},
                autoTrigger: true,
                ...config,
            },

            data: data, // Store the data object for later use
            values: config.values || [],
            options: config.options || [],
            search: "",
            open: false,
            filteredOptions: [],

            init() {
                this.filterOptions();

                this.$watch('open', (value) => {
                    if (value) {
                        this.$nextTick(() => {
                            this.positionDropdown();
                        });
                    }
                });
            },

            filterOptions() {
                const term = this.search.toLowerCase();
                this.filteredOptions = this.options.filter(
                    (o) =>
                        o.toLowerCase().includes(term) &&
                        !this.values.includes(o)
                );
            },

            handleEnter() {
                if (this.search.trim()) {
                    this.select(this.search.trim());
                }
            },

            select(option) {
                if (!option || this.values.includes(option)) return;

                this.values.push(option);

                if (!this.options.includes(option)) {
                    this.options.push(option);
                }

                this.search = "";
                this.filterOptions();
                this.open = false;

                if (this.config.autoTrigger) {
                    this.doAction();
                }
            },

            remove(option) {
                this.values = this.values.filter((o) => o !== option);

                if (this.config.autoTrigger) {
                    this.doAction();
                }

                this.filterOptions();
            },


            positionDropdown() {
                const input = this.$refs.input;
                const dropdown = this.$refs.dropdown;

                if (!input || !dropdown) return;

                const rect = input.getBoundingClientRect();
                dropdown.style.top = `${rect.bottom + 2}px`;
                dropdown.style.left = `${rect.left}px`;
                dropdown.style.width = `${rect.width}px`;
            },

            async doAction() {
                if (this.config.requireSelection && this.values.length === 0) {
                    this.setError("Please select at least one value.");
                    return;
                }

                if (this.config.action) {
                    return;
                }

                try {
                    const result = await this.ajaxRequest({
                        url: this.config.ajaxUrl,
                        action: this.config.action,
                        nonce: this.config.nonce,
                        data: {
                            ...this.data,  // Use this.data instead of data
                            values: this.values
                        },
                        beforeSend: this.config.beforeSend,
                        afterResponse: this.config.afterResponse,
                    });

                    // Execute optional local JS actions
                    if (result?.action) {
                        const fn = this.config.actions?.[result.action];
                        if (typeof fn === "function") await fn(result, this);
                    }
                } catch (err) {
                    console.error('Multi-select AJAX error:', err);
                }
            },
        };
    };


    exports.notifications = {
        showToast(message, type = 'error') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;

            if (type === 'success') {
                toast.style.background = '#4caf50'; // green
            }
            if (type === 'notif') {
                toast.style.background = '#eaaf3d'; // green
            }

            container.appendChild(toast);

            // remove after animation
            setTimeout(() => {
                toast.remove();
            }, 5000); // matches animation timing
        }
    }

    exports.admin = {
        start() {
            // Ensure Alpine is available
            window.Alpine = window.Alpine || {};

            Alpine.data('multiSelectComponent', (data = {}, config = {}) => ntdst.multiSelectComponent(data, {
                    ajaxUrl: window.wpConfig?.ajaxUrl || '',
                    nonce: window.wpConfig?.selectNonce || '',
                    requireSelection: true,
                    action:'do_select_action',
                    ...config
                })
            );

            Alpine.data('actionComponent', (data = {}, config = {}) => ntdst.ajaxStateComponent(data, {
                    ajaxUrl: window.wpConfig?.ajaxUrl || '',
                    nonce: window.wpConfig?.actionNonce || '',
                    requireSelection: true,
                    action:'do_selected_action',
                    ...config
                })
            );

            Alpine.data('statusComponent', (data = {}, config = {}) => ntdst.ajaxStateComponent(data, {
                    ajaxUrl: window.wpConfig?.ajaxUrl || '',
                    nonce: window.wpConfig?.statusNonce || '',
                    initAction:'get_checkbox_status',
                    action:'update_checkbox_status',
                    ...config
                })
            );

            Alpine.data('panelComponent', (data = {},config = {}) => ntdst.panelComponent(data, {
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.panelNonce || '',
                ...config
            }));


            Alpine.data('tableComponent', (data = {},config = {}) => ntdst.tableComponent(data, {
                ajaxUrl: window.wpConfig?.ajaxUrl || '',
                nonce: window.wpConfig?.tableNonce || '',
                initAction:'get_table_content',
                actions: window.ntdst.tableActions || {},
                ...config
            }));

        }
    };

    // Initialize on DOM ready
    util.ready(() => exports.admin.start());


})(window.ntdst,{ ready: (fn) => document.addEventListener('alpine:init', fn) } );

