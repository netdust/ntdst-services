window.ntdst = window.ntdst || {};

((exports) => {
    exports.tableComponent = (config = {}) => {
        return {
            config: {
                ajaxUrl: window.wpVars?.ajaxUrl || '', // Use wpVars from wp_localize_script
                nonce: window.wpVars?.nonce || '',
                fetchAction: 'get_table_data',
                cacheClearAction: 'clear_table_cache',
                cacheClearNonce: '',
                actions: {},
                actionColumnSelector: '.action-button',
                statusToggleSelector: '.status-toggle',
                checkboxSelector: 'input[name="table_item[]"]',
                tableContentId: 'table-content',
                debounceDelay: 300,
                cacheTTL: 5 * 60 * 1000,
                onFetchSuccess: (data, component) => {
                    document.getElementById(component.config.tableContentId).innerHTML = data.html;
                    component.totalItems = data.total;
                },
                onFetchError: (error, component) => {
                    console.error('Error fetching table:', error);
                    document.getElementById(component.config.tableContentId).innerHTML = '<p>Error loading data.</p>';
                },
                ...config
            },

            // Component state
            search: '',
            currentPage: 1,
            perPage: 10,
            totalItems: 0,
            selectedItems: [],
            selectAll: false,
            editLinks: {},
            isLoading: false,
            lastFetchParams: null,
            debounceTimer: null,
            cache: new Map(),
            items: [],
            responseMessage: '',
            filters: config.filters || {}, // Initialize filters from config

            init() {
                console.log('Initializing tableComponent with config:', this.config);
                this.totalItems =  0;
                this.fetchData();
                this.attachListeners();
            },

            debouncedSearch() {
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => {
                    const value = this.search;

                    // Trigger search if empty OR at least 3 chars
                    if (value.length === 0 || value.length >= 3) {
                        this.fetchData();
                    }
                }, 500); // 300ms debounce
            },

            async fetchData() {

                const fetchParams = {
                    page: this.currentPage,
                    per_page: this.perPage,
                    search: this.search,
                    filters: JSON.stringify(this.filters) // Include filters as JSON
                };

                const filtersChanged = JSON.stringify(this.filters) !== JSON.stringify(this.lastFetchParams?.filters || '{}');

                if (!filtersChanged && this.search === this.lastFetchParams?.search && !this.isLoading) {
                    console.log('Skipping fetch: parameters unchanged');
                    return;
                }

                const cacheKey = JSON.stringify(fetchParams);
                const cached = this.cache.get(cacheKey);
                if (cached && Date.now() - cached.timestamp < this.config.cacheTTL) {
                    console.log('Serving from cache:', cacheKey);
                    this.isLoading = true;
                    this.config.onFetchSuccess(cached.data, this);
                    this.attachListeners();
                    this.isLoading = false;
                    return;
                }

                if (this.search.length > 0 && this.search.length < 3) {
                    return;
                }


                this.isLoading = true;
                this.activePanelId = null;
                this.lastFetchParams = fetchParams;

                try {
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: this.config.fetchAction,
                            ...fetchParams,
                            nonce: this.config.nonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.cache.set(cacheKey, { data: data.data, timestamp: Date.now() });
                        this.cleanupCache();
                        this.config.onFetchSuccess(data.data, this);
                        const rows = document.querySelectorAll(`#${this.config.tableContentId} tbody tr:not(.action-panel-row)`);
                        rows.forEach(row => {
                            const itemId = row.querySelector(this.config.actionColumnSelector)?.dataset.id;
                            if (itemId) {
                                this.editLinks[itemId] = row.querySelector(this.config.actionColumnSelector).dataset.editLink || '';
                            }
                        });
                        this.attachListeners();
                    } else {
                        this.config.onFetchError(data, this);
                    }
                } catch (error) {
                    this.config.onFetchError(error, this);
                } finally {
                    this.isLoading = false;
                }
            },

            cleanupCache() {
                const now = Date.now();
                for (const [key, value] of this.cache.entries()) {
                    if (now - value.timestamp > this.config.cacheTTL) {
                        this.cache.delete(key);
                    }
                }
            },

            async retryFetch(params, maxRetries, attempt = 1) {
                try {
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: this.config.fetchAction,
                            ...params,
                            nonce: this.config.nonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.cache.set(JSON.stringify(params), { data: data.data, timestamp: Date.now() });
                        this.config.onFetchSuccess(data.data, this);
                        this.attachListeners();
                        return { success: true };
                    }
                    throw new Error('Fetch failed');
                } catch (error) {
                    if (attempt < maxRetries) {
                        console.warn(`Retrying fetch (attempt ${attempt + 1}/${maxRetries})`);
                        await new Promise(resolve => setTimeout(resolve, 1000 * attempt));
                        return this.retryFetch(params, maxRetries, attempt + 1);
                    }
                    return { success: false, error };
                }
            },

            openActionPanel(itemId, event) {
                const id = parseInt(itemId);
                console.log('Opening panel for itemId:', id, 'previous activePanelId:', this.activePanelId);
                this.activePanelId = id;
                this.$nextTick(() => {
                    console.log('Panel updated, activePanelId:', this.activePanelId);
                    document.querySelectorAll('.action-panel-row').forEach(row => {
                        console.log('Row ID:', row.getAttribute('x-ref').replace('actionPanel_', ''), 'Visible:', row.style.display !== 'none');
                    });
                });
            },

            closeAllPanels() {
                console.log('Closing all panels');
                this.activePanelId = null;
            },

            async clearCache(itemId = 0) {
                if (!this.config.cacheClearAction) return;
                try {
                    const response = await fetch(this.config.ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: this.config.cacheClearAction,
                            item_id: itemId,
                            nonce: this.config.cacheClearNonce
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        console.log('Cache cleared');
                        this.cache.clear();
                        this.fetchData();
                    } else {
                        console.error('Failed to clear cache:', data);
                    }
                } catch (error) {
                    console.error('Error clearing cache:', error);
                }
            },

            async executeAction(actionName, data, event) {
                if (this.config.actions[actionName]) {
                    try {
                        const result = await this.config.actions[actionName](data, event, this);
                        if (result.success) {
                            //this.closeAllPanels();
                            //this.fetchData();
                        }
                    } catch (error) {
                        console.error(`Error executing action ${actionName}:`, error);
                    }
                } else {
                    console.warn(`Action ${actionName} not defined in config`);
                }
            },

            toggleSelectAll() {
                const checkboxes = document.querySelectorAll(`#${this.config.tableContentId} ${this.config.checkboxSelector}`);
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.selectAll;
                    this.selectedItems = this.selectAll
                        ? Array.from(checkboxes).map(cb => parseInt(cb.value))
                        : [];
                });
            },

            attachListeners() {
                const actionButtons = document.querySelectorAll(`#${this.config.tableContentId} ${this.config.actionColumnSelector}`);
                console.log('Attaching listeners to', actionButtons.length, 'action buttons');
                actionButtons.forEach(button => {
                    button.removeEventListener('click', this.handleActionButton);
                    button.addEventListener('click', this.handleActionButton.bind(this));
                });

                const statusToggles = document.querySelectorAll(`#${this.config.tableContentId} ${this.config.statusToggleSelector}`);
                console.log('Attaching listeners to', statusToggles.length, 'status toggles');
                statusToggles.forEach(span => {
                    span.removeEventListener('click', this.handleStatusToggle);
                    span.addEventListener('click', this.handleStatusToggle.bind(this));
                });

                const checkboxes = document.querySelectorAll(`#${this.config.tableContentId} ${this.config.checkboxSelector}`);
                console.log('Attaching listeners to', checkboxes.length, 'checkboxes');
                checkboxes.forEach(checkbox => {
                    checkbox.removeEventListener('change', this.handleCheckboxChange);
                    checkbox.addEventListener('change', this.handleCheckboxChange.bind(this));
                });
            },

            handleActionButton(event) {
                const itemId = parseInt(event.target.closest(this.config.actionColumnSelector).dataset.id);
                console.log('Action button handler, itemId:', itemId);
                this.openActionPanel(itemId, event);
            },

            handleStatusToggle(event) {
                const itemId = parseInt(event.target.dataset.id);
                this.executeAction('toggleStatus', itemId, event);
            },

            handleCheckboxChange() {
                const checkboxes = document.querySelectorAll(`#${this.config.tableContentId} ${this.config.checkboxSelector}`);
                this.selectedItems = Array.from(checkboxes)
                    .filter(cb => cb.checked)
                    .map(cb => parseInt(cb.value));
                this.selectAll = checkboxes.length === this.selectedItems.length;
            }
        };
    };
})(window.ntdst);