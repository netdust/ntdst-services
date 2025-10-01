window.ntdst = window.ntdst || {};

((exports) => {

    exports.tableComponent = (data = {}, config = {}) => {
        return {
            ...exports.ajaxMixin(), // brings isLoading, status, success, error, ajaxRequest

            config: {
                ajaxUrl: '',
                nonce: '',
                initAction: '',
                cacheClearAction: 'clear_table_cache',
                bulkActionHandler: 'do_bulk_action',
                bulkActions: [],
                actions: {},
                tableContentId: 'table-content',
                checkboxSelector: 'input[name="table_item[]"]',
                actionColumnSelector: '.action-button',
                statusToggleSelector: '.status-toggle',
                checkboxToggleSelector: 'input[type="checkbox"]:not([name="table_item[]"])',
                cacheTTL: 5 * 60 * 1000,
                debounceDelay: 300,
                beforeSend: null,
                afterResponse: null,
                ...config
            },

            // State
            search: '',
            tableHtml: '',
            currentPage: 1,
            perPage: config.perPage || 10,
            totalItems: config.totalItems || 0,
            lastFetchParams: null,
            cache: new Map(),
            filters: config.filters || {},

            // Bulk action state
            selectedIds: [],
            bulkAction: '',
            isBulkLoading: false,

            init() {
                // fetch initial table data
                if (this.config.initAction) {
                    this.doAction({ action: this.config.initAction });
                }
                this.$el.addEventListener("reload-table", () => {
                    console.log("reload-table event received in tableComponent");
                    this.invalidateCache();
                    this.doAction();
                });
            },

            debouncedSearch() {
                clearTimeout(this._searchTimeout);
                this._searchTimeout = setTimeout(() => {
                    const value = this.search;
                    // Trigger search if empty OR at least 3 chars
                    if (value.length === 0 || value.length >= 3) {
                        this.currentPage = 1;
                        this.doAction(); // calls initAction by default
                    }
                }, this.config.debounceDelay || 500);
            },

            async doAction(options = {}) {
                const actionName = options.action || this.config.action;
                const requestData = {
                    ...data,
                    page: this.currentPage,
                    per_page: this.perPage,
                    search: this.search,
                    filters: JSON.stringify(this.filters),
                    ...options.data
                };

                const cacheKey = JSON.stringify({ action: actionName, ...requestData });
                const cached = this.cache.get(cacheKey);

                if (cached && Date.now() - cached.timestamp < this.config.cacheTTL) {
                    console.log( 'get from cache: ', cached);
                    this.tableHtml = cached.data.html;
                    this.totalItems = cached.data.total;
                    this.$nextTick(() => {this.attachListeners()});
                    return;
                }

                try {
                    const response = await this.ajaxRequest({
                        url: this.config.ajaxUrl,
                        action: actionName,
                        nonce: this.config.nonce,
                        data: requestData,
                        beforeSend: this.config.beforeSend,
                        afterResponse: async (res) => {
                            if (typeof this.config.afterResponse === 'function') {
                                return await this.config.afterResponse(res, {action: actionName, data: requestData});
                            }
                            const json = await res.json();
                            if (!json.success) throw new Error(json.data?.message || 'Request failed');
                            return json.data;
                        }
                    });


                    if (response !== undefined ) {
                        this.cache.set(cacheKey, {data: response, timestamp: Date.now()});
                        this.pruneExpiredCache();
                        this.tableHtml = response.html; // Reactive property
                        this.totalItems = response.total;
                        this.$nextTick(() => this.attachListeners());
                    }
                } catch (err) {
                    this.setError(err);
                }
            },

            async executeBulkAction() {
                if (!this.bulkAction) {
                    this.setError('Please select an action');
                    return;
                }

                if (this.selectedIds.length === 0) {
                    this.setError('Please select at least one item');
                    return;
                }

                this.isBulkLoading = true;

                try {
                    const customAction = this.config.actions?.[this.bulkAction];
                    let response;

                    if (typeof customAction === 'function') {
                        response = await customAction(this.selectedIds, null, this);
                    } else {
                        // Use ajaxRequest for default bulk action endpoint
                        response = await this.ajaxRequest({
                            url: this.config.ajaxUrl,
                            action: this.config.bulkActionHandler,
                            nonce: this.config.nonce,
                            data: {
                                ...data,
                                bulk_action: this.bulkAction,
                                selected_ids: JSON.stringify(this.selectedIds)
                            }
                        });
                    }

                    const message = response?.message || response?.data?.message || 'Action completed successfully';
                    exports.notifications.showToast(message, "success");

                    // Reset and reload on success

                    const shouldRefresh = response?.refresh ?? response?.data?.refresh ?? true;
                    if (shouldRefresh) {
                        this.selectedIds = [];
                        this.bulkAction = '';
                        this.invalidateCache();
                        await this.doAction({ action: this.config.initAction });
                    }

                } catch (err) {
                    console.error('Bulk action failed:', err);
                } finally {
                    this.isBulkLoading = false;
                }
            },


            toggleSelection(id) {
                const index = this.selectedIds.indexOf(id);
                if (index > -1) {
                    this.selectedIds.splice(index, 1);
                } else {
                    this.selectedIds.push(id);
                }
            },


            pruneExpiredCache() {
                const now = Date.now();
                for (const [key, value] of this.cache.entries()) {
                    if (now - value.timestamp > this.config.cacheTTL) {
                        this.cache.delete(key);
                    }
                }
            },


            invalidateCache() {
                this.cache.clear();
            },

            async clearCache(itemId = 0) {
                if (!this.config.cacheClearAction) return;
                await this.doAction({ action: this.config.cacheClearAction, data: { item_id: itemId } });
                this.invalidateCache();
                await this.doAction({ action: this.config.initAction });
            },

            attachListeners() {
                const contentEl = document.getElementById(this.config.tableContentId);
                if (!contentEl) return;

                // Action buttons
                contentEl.querySelectorAll(this.config.actionColumnSelector)
                    .forEach(btn => {
                        btn.removeEventListener('click', this.handleActionButton);
                        btn.addEventListener('click', this.handleActionButton.bind(this));
                    });

                // Status toggle elements (e.g., .status-toggle spans/buttons)
                contentEl.querySelectorAll(this.config.statusToggleSelector)
                    .forEach(el => {
                        el.removeEventListener('click', this.handleStatusToggle);
                        el.addEventListener('click', this.handleStatusToggle.bind(this));
                    });

                // Status/toggle checkboxes (for inline status changes)
                contentEl.querySelectorAll(this.config.checkboxToggleSelector)
                    .forEach(cb => {
                        cb.removeEventListener('change', this.handleToggleCheckbox);
                        cb.addEventListener('change', this.handleToggleCheckbox.bind(this));
                    });

                // Bulk selection checkboxes (for selecting rows)
                contentEl.querySelectorAll(this.config.checkboxSelector)
                    .forEach(cb => {
                        cb.removeEventListener('change', this.handleBulkCheckbox);
                        cb.addEventListener('change', this.handleBulkCheckbox.bind(this));
                    });

                // Select-all checkbox
                contentEl.querySelectorAll('input[type="checkbox"][id^="cb-select-all"]')
                    .forEach(cb => {
                        cb.removeEventListener('change', this.handleSelectAll);
                        cb.addEventListener('change', this.handleSelectAll.bind(this));
                    });
            },

            handleBulkCheckbox(event) {
                const itemId = parseInt(event.target.value) || parseInt(event.target.dataset.id);

                console.log( itemId);
                if (itemId) {
                    this.toggleSelection(itemId);
                }
            },

            handleSelectAll(event) {
                const contentEl = document.getElementById(this.config.tableContentId);
                const checkboxes = contentEl.querySelectorAll(this.config.checkboxSelector);

                this.selectedIds = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => parseInt(cb.value) || parseInt(cb.dataset.id));
            },

            handleActionButton(event) {
                const itemId = event.target.closest(this.config.actionColumnSelector).dataset.id;
                this.executeAction('openPanel', itemId, event);
            },

            handleStatusToggle(event) {
                const itemId = event.target.dataset.id;
                this.executeAction('toggleStatus', itemId, event);
            },

            handleToggleCheckbox(event) {
                const itemId = event.target.dataset.id || event.target.value;
                const name = event.target.name;
                const value = event.target.value;
                const checked = event.target.checked;

                if (itemId) {
                    this.executeAction('checkboxChange', { id: itemId, name, value, checked }, event);
                } else {
                    console.error('No itemId found for checkbox');
                }
            },

            async executeAction(actionName, payload, event) {
                if (typeof this.config.actions[actionName] === 'function') {
                    try {
                        await this.config.actions[actionName](payload, event, this);
                    } catch (err) {
                        console.error(`Error executing action ${actionName}:`, err);
                    }
                } else {
                    console.warn(`Action ${actionName} not defined in config.actions`);
                }
            },

            // 👇 NEW: Helper computed property for UI binding
            get hasSelection() {
                return this.selectedIds.length > 0;
            },

        };
    };

})(window.ntdst);
