(function($) {
    'use strict';

    if (typeof filter_data === 'undefined') {
        return;
    }

    class PostFilter {
        constructor(config) {
            this.config = config;
            this.filters = this.parseJSON(config.filters) || {};
            this.metas = this.parseJSON(config.metas) || {};
            this.nonce = null;
            this.isLoading = false;
            this.currentPage = 1;

            this.searchDebounce = null;
            this.searchDelay = 300;

            this.init();
        }

        init() {
            this.fetchNonce();
            this.syncWithPage();
            this.updateUI();
            this.attachEvents();
        }

        // Sync JavaScript state with page load state
        syncWithPage() {
            // Check if there are URL parameters (filters)
            const urlParams = new URLSearchParams(window.location.search);
            const hasURLParams = urlParams.toString().length > 0;

            // Check if there are any active filters in the HTML on page load
            const hasActiveFilters = $('.filter-item.uk-active').length > 0;

            // If we have URL params but no active filters in HTML, parse from URL
            if (hasURLParams && !hasActiveFilters) {
                this.parseURLState();
            } else if (!hasActiveFilters) {
                // No URL params and no active filters - ensure JS filters are empty
                this.filters = {};
            }

            // Parse page number from URL
            const pageMatch = window.location.pathname.match(/\/page\/(\d+)\/?/);
            if (pageMatch) {
                this.currentPage = parseInt(pageMatch[1]);
            }
        }

        // ============================================
        // Event Handlers
        // ============================================

        attachEvents() {
            $(document)
                .on('click', '.filter-item, .filter-active a', this.handleFilterClick.bind(this))
                .on('click', 'form.uk-search .uk-search-icon', this.handleSearchSubmit.bind(this))
                .on('keyup', 'form.uk-search .uk-search-input', this.handleSearchInput.bind(this))
                .on('click', '.pagination a', this.handlePaginationClick.bind(this));

            $(window).on('popstate', this.handlePopState.bind(this));
        }

        handleFilterClick(e) {
            e.preventDefault();

            if (this.isLoading) return;

            const $btn = $(e.currentTarget);
            const category = $btn.data('cat');
            const term = String($btn.data('term'));
            const label = $btn.data('label');

            if (!category || !term) {
                console.warn('Invalid filter data');
                return;
            }

            this.toggleFilter(category, term, label);
            this.currentPage = 1; // Reset to page 1 when filter changes
            this.submitFilter();
        }

        handleSearchSubmit(e) {
            e.preventDefault();
            if (this.isLoading) return;
            this.currentPage = 1; // Reset to page 1 on search
            this.submitFilter();
        }

        handleSearchInput(e) {
            if (e.which === 13) {
                e.preventDefault();
                this.currentPage = 1; // Reset to page 1
                this.submitFilter();
                return;
            }

            // Debounce search
            clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => {
                this.currentPage = 1; // Reset to page 1
                this.submitFilter();
            }, this.searchDelay);
        }

        handlePopState() {
            if (this.isLoading) return;

            this.parseURLState();
            this.submitFilter();
        }

        handlePaginationClick(e) {
            e.preventDefault();

            if (this.isLoading) return;

            const $link = $(e.currentTarget);
            const href = $link.attr('href');

            if (!href || href === '#') return;

            // Extract page number from URL
            const pageMatch = href.match(/\/page\/(\d+)\//);
            const page = pageMatch ? parseInt(pageMatch[1]) : 1;

            this.currentPage = page;
            this.submitFilter(page);
        }

        // ============================================
        // Filter Management
        // ============================================

        toggleFilter(category, term, label) {
            if (!this.filters[category]) {
                this.filters[category] = {};
            }

            if (this.filters[category][term]) {
                delete this.filters[category][term];

                // Clean up empty categories
                if (Object.keys(this.filters[category]).length === 0) {
                    delete this.filters[category];
                }
            } else {
                this.filters[category][term] = label;
            }

            this.updateUI();
        }

        updateUI() {
            // Update filter buttons
            $('.filter-item').removeClass('uk-active');

            Object.entries(this.filters).forEach(([category, terms]) => {
                Object.keys(terms).forEach(term => {
                    $(`.filter-item[data-cat="${category}"][data-term="${term}"]`)
                        .addClass('uk-active');
                });
            });

            // Update active filter tags
            this.updateActiveTags();
        }

        updateActiveTags() {
            const $container = $('.filter-active').empty();

            $('.filter-item.uk-active').each(function() {
                const $this = $(this);
                const category = $this.data('cat');
                const term = $this.data('term');
                const label = $this.text().trim();

                $container.append(
                    `<li>
                        <a href="#" 
                           uk-icon="icon: close; ratio: .5" 
                           data-term="${term}" 
                           data-cat="${category}" 
                           data-label="${label}"
                           class="filter-active-tag">
                            ${label}
                        </a>
                    </li>`
                );
            });
        }

        submitFilter(page = null) {
            if (!this.nonce) {
                console.error('Nonce not available');
                return;
            }

            if (this.isLoading) {
                return;
            }

            // Use provided page or current page
            if (page !== null) {
                this.currentPage = page;
            }

            this.isLoading = true;
            this.showLoading();

            const postData = {
                action: this.config.action,
                filter: this.filters,
                metas: this.metas,
                s: this.getSearchValue(),
                paged: this.currentPage,
                security: this.nonce
            };

            // Debug logging
            if (window.location.search.indexOf('debug=1') !== -1) {
                console.log('PostFilter AJAX Request:', postData);
            }

            $.ajax({
                type: 'POST',
                url: this.config.ajaxurl,
                data: postData,
                success: (response) => this.handleSuccess(response),
                error: (xhr, status, error) => this.handleError(error),
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }

        handleSuccess(response) {
            try {
                const data = typeof response === 'string'
                    ? JSON.parse(response)
                    : response;

                // Debug logging
                if (window.location.search.indexOf('debug=1') !== -1) {
                    console.log('PostFilter AJAX Response:', data);
                }

                if (data.html) {
                    $('#result-count').text(data.total || 0);
                    $('.ntdst-filter-results').html(data.html);
                    $('.pagination').html(data.page || '');

                    this.updateURL();

                    // Scroll to results
                    const $results = $('.ntdst-filter-results');
                    if ($results.length) {
                        $('html, body').animate({
                            scrollTop: $results.offset().top - 100
                        }, 300);
                    }

                    // Trigger custom event for other scripts
                    $(document).trigger('postfilter:updated', [data]);
                }
            } catch (e) {
                console.error('Failed to parse response:', e);
                this.showError('Failed to load results');
            }
        }

        handleError(error) {
            console.error('Filter error:', error);
            this.showError('An error occurred. Please try again.');
        }

        // ============================================
        // UI Feedback
        // ============================================

        showLoading() {
            $('.ntdst-filter-results').addClass('is-loading').css('opacity', '0.5');
        }

        hideLoading() {
            $('.ntdst-filter-results').removeClass('is-loading').css('opacity', '1');
        }

        showError(message) {
            $('.ntdst-filter-results').prepend(
                `<div class="uk-alert-danger" uk-alert>
                    <a class="uk-alert-close" uk-close></a>
                    <p>${message}</p>
                </div>`
            );
        }

        // ============================================
        // URL Management
        // ============================================

        updateURL() {
            const params = new URLSearchParams();

            // Add filters
            Object.entries(this.filters).forEach(([key, terms]) => {
                params.set(key, Object.keys(terms).join(','));
            });

            // Add metas
            Object.entries(this.metas).forEach(([key, value]) => {
                if (value) params.set(key, value);
            });

            // Add search
            const search = this.getSearchValue();
            if (search) params.set('s', search);

            const queryString = params.toString();

            // Get current path without pagination
            let basePath = window.location.pathname.replace(/\/page\/\d+\/?$/, '');

            // Add page to path if not page 1
            let newURL;
            if (this.currentPage > 1) {
                // Add /page/X/ to the path
                newURL = basePath.replace(/\/?$/, '') + '/page/' + this.currentPage + '/';
                if (queryString) {
                    newURL += '?' + queryString;
                }
            } else {
                // Page 1, no /page/X/ in URL
                newURL = queryString ? `${basePath}?${queryString}` : basePath;
            }

            if (window.history && window.history.pushState) {
                window.history.pushState({ path: newURL }, '', newURL);
            }
        }

        parseURLState() {
            const params = new URLSearchParams(window.location.search);

            this.filters = {};

            params.forEach((value, key) => {
                if (key !== 's' && value) {
                    const terms = value.split(',');
                    this.filters[key] = {};
                    terms.forEach(term => {
                        this.filters[key][term] = term; // We don't have labels here
                    });
                }
            });

            // Parse page from URL path
            const pageMatch = window.location.pathname.match(/\/page\/(\d+)\/?/);
            this.currentPage = pageMatch ? parseInt(pageMatch[1]) : 1;

            this.updateUI();
        }

        // ============================================
        // Utilities
        // ============================================

        fetchNonce() {
            $.post(this.config.ajaxurl, { action: 'get_filter_nonce' })
                .done((response) => {
                    if (response.success && response.data && response.data.nonce) {
                        this.nonce = response.data.nonce;
                    }
                })
                .fail(() => {
                    console.error('Failed to fetch nonce');
                });
        }

        getSearchValue() {
            return $('form.uk-search input[name="s"]').val() || '';
        }

        parseJSON(data) {
            if (!data) return null;

            try {
                return typeof data === 'string' ? JSON.parse(data) : data;
            } catch (e) {
                console.warn('Failed to parse JSON:', e);
                return null;
            }
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        // Check if initial results container exists
        const $resultsContainer = $('.ntdst-filter-results');
        const $resultCount = $('#result-count');

        if ($resultsContainer.length === 0) {
            console.warn('PostFilter: Results container .ntdst-filter-results not found');
        }

        // Debug logging
        if (window.location.search.indexOf('debug=1') !== -1) {
            console.log('PostFilter Debug:', {
                config: filter_data,
                resultsContainer: $resultsContainer.length,
                initialCount: $resultCount.text(),
                initialHTML: $resultsContainer.html()?.substring(0, 100)
            });
        }

        window.postFilter = new PostFilter(filter_data);

        // Expose debug method globally
        window.debugPostFilter = function() {
            const filter = window.postFilter;
            console.log('Current Filter State:', {
                filters: filter.filters,
                metas: filter.metas,
                nonce: filter.nonce ? 'Present' : 'Missing',
                isLoading: filter.isLoading
            });

            // Test AJAX debug endpoint (admin only)
            $.post(filter.config.ajaxurl, {
                action: filter.config.action + '_debug',
                filter: filter.filters,
                metas: filter.metas,
                security: filter.nonce
            }, function(response) {
                console.log('Server Debug Response:', response);
            });
        };
    });

})(jQuery);