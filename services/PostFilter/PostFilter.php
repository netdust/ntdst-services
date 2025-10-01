<?php

namespace Netdust\Services\PostFilter;

use Netdust\Core\ServiceProvider;
use Netdust\Service\Assets\AssetManager;
use Netdust\Service\Assets\Script;

class PostFilter
{
	protected ServiceProvider $provider;
	protected Script $script;

	// Configuration
	protected string $post_type = 'post';
	protected string $publish = 'publish';
	protected array $order_by = ['menu_order' => 'DESC', 'date' => 'DESC'];
	protected string $id = '';
	protected string $exclude_cat = '';
	protected int $posts_per_page = 10;

	// Cached data
	protected ?array $taxonomies = null;
	protected ?array $filters = null;
	protected ?array $metas = null;

	public function __construct(ServiceProvider $provider, array $params = [])
	{
		$this->provider = $provider;
		$this->posts_per_page = (int) get_option('posts_per_page', 10);
		$this->configure($params);
	}

	private function configure(array $params): void
	{
		$allowed = ['post_type', 'publish', 'order_by', 'id', 'exclude_cat', 'posts_per_page'];

		foreach ($params as $key => $value) {
			if (in_array($key, $allowed, true)) {
				$this->{$key} = $key === 'posts_per_page' ? (int) $value : $value;
			}
		}
	}

	public function register(): void
	{
		add_filter('term_link', [$this, 'modify_term_link'], 10, 3);
		add_action('init', [$this, 'add_nocache_headers']);
		add_action('wp_head', [$this, 'add_noindex_meta']);

		$this->register_script();
		$this->register_ajax();
	}

	// ============================================
	// AJAX Handlers
	// ============================================

	private function register_ajax(): void
	{
		add_action('wp_ajax_get_filter_nonce', [$this, 'ajax_get_nonce']);
		add_action('wp_ajax_nopriv_get_filter_nonce', [$this, 'ajax_get_nonce']);

		add_action("wp_ajax_{$this->id}", [$this, 'ajax_filter']);
		add_action("wp_ajax_nopriv_{$this->id}", [$this, 'ajax_filter']);
	}

	public function ajax_get_nonce(): void
	{
		wp_send_json_success(['nonce' => wp_create_nonce('filter_security')]);
	}

	public function ajax_filter(): void
	{
		check_ajax_referer('filter_security', 'security');

		$this->clear_cache();

		$page = filter_input(INPUT_POST, 'paged', FILTER_VALIDATE_INT) ?: 1;
		$wp_query = new \WP_Query($this->build_query($page));

		do_action('postfilter:query', $wp_query);

		$html = $wp_query->have_posts()
			? $this->render('filter_result', ['results' => $wp_query])
			: '<div uk-alert>No results found.</div>';

		wp_reset_postdata();

		wp_send_json([
			'total' => $wp_query->found_posts,
			'page' => $this->get_pagination($wp_query),
			'html' => $html
		]);
	}

	// ============================================
	// Query Building
	// ============================================

	public function build_query(int $page = 1): array
	{
		$query = [
			'post_type' => $this->post_type,
			'post_status' => $this->publish,
			'orderby' => $this->order_by,
			'paged' => $page,
			'posts_per_page' => $this->posts_per_page,
			'ignore_sticky_posts' => true
		];



		// Text search
		if ($search = $this->get_sanitized_input('s')) {
			$query['s'] = $search;
		}

		// Taxonomy filters
		if ($tax_query = $this->build_tax_query()) {
			$query['tax_query'] = count($tax_query) > 1
				? array_merge(['relation' => 'AND'], $tax_query)
				: $tax_query;
		}

		// Meta filters
		if ($meta_query = $this->build_meta_query()) {
			$query['meta_query'] = count($meta_query) > 1
				? array_merge(['relation' => 'OR'], $meta_query)
				: $meta_query;
		}

		$query = apply_filters('postfilter:query_args', $query, $this->post_type);


		return $query;
	}

	private function build_tax_query(): array
	{
		$tax_query = [];
		$filters = $this->get_filters();
		$taxonomies = $this->get_taxonomies();

		foreach ($filters as $category => $terms) {
			if (isset($taxonomies[$category])) {
				$tax_query[] = [
					'taxonomy' => $taxonomies[$category]['tax'],
					'field' => 'slug',
					'terms' => array_keys($terms),
					'operator' => 'IN'
				];
			}
		}

		return apply_filters('postfilter:tax_query', $tax_query, $this->post_type);
	}

	private function build_meta_query(): array
	{
		$meta_query = [];

		foreach ($this->get_metas() as $key => $value) {
			if (is_string($value) && !empty($value)) {
				$terms = preg_split('/\s+/', trim($value));
				$regex = implode('.*', array_map('preg_quote', $terms));

				$meta_query[] = [
					'key' => sanitize_key($key),
					'value' => $regex,
					'compare' => 'REGEXP'
				];
			}
		}

		return apply_filters('postfilter:meta_query', $meta_query, $this->post_type);
	}

	// ============================================
	// Data Getters
	// ============================================

	public function get_taxonomies(): array
	{
		if ($this->taxonomies !== null) {
			return $this->taxonomies;
		}

		$taxonomies = [];
		$object_taxonomies = get_object_taxonomies(['post_type' => $this->post_type]);
		$excluded = array_filter(explode(',', $this->exclude_cat));
		$allowed_taxonomies = array_diff($object_taxonomies, $excluded);

		foreach ($allowed_taxonomies as $taxonomy) {
			$terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);

			if (is_wp_error($terms) || empty($terms)) continue;

			$details = get_taxonomy($taxonomy);
			$slug = $this->slugify($details->name);

			$taxonomies[$slug] = [
				'label' => $details->label,
				'tax' => $taxonomy,
				'terms' => array_column(array_map(fn($t) => [$t->slug, $t->name], $terms), 1, 0)
			];
		}

		$this->taxonomies = apply_filters('postfilter:get_taxonomies', $taxonomies);
		return $this->taxonomies;
	}

	public function get_filters(): array
	{
		if ($this->filters !== null) {
			return $this->filters;
		}

		$filters = [];
		$input = $this->get_filter_input();
		$taxonomies = $this->get_taxonomies();

		foreach ($input as $category => $terms) {
			if (!isset($taxonomies[$category])) continue;

			$term_slugs = is_string($terms)
				? array_map('sanitize_title', explode(',', $terms))
				: array_keys($terms);

			foreach ($term_slugs as $slug) {
				if (isset($taxonomies[$category]['terms'][$slug])) {
					$filters[$category][$slug] = $taxonomies[$category]['terms'][$slug];
				}
			}
		}

		$this->filters = apply_filters('postfilter:get_filters', $filters);
		return $this->filters;
	}

	public function get_metas(): array
	{
		if ($this->metas !== null) {
			return $this->metas;
		}

		$excluded = apply_filters('postfilter:meta_exclude', [
			's', 'paged', 'post_type', 'post_status', 'orderby', 'order',
			'pagename', 'page_id', 'name', 'p', 'page', 'cat', 'tag',
			'taxonomy', 'term', 'author', 'year', 'monthnum', 'day',
			'hour', 'minute', 'second', 'w', 'm', 'attachment', 'attachment_id',
			'subpost', 'subpost_id', 'preview', 'static', 'posts', 'posts_per_page',
			'ignore_sticky_posts', 'cache_results', 'update_post_meta_cache',
			'update_post_term_cache', 'nopaging', 'comments_per_page'
		]);

		$input = $this->get_filter_input();
		$filters = $this->get_filters();
		$metas = [];

		foreach ($input as $key => $value) {
			if (!isset($filters[$key]) && !in_array($key, $excluded, true)) {
				$metas[sanitize_key($key)] = sanitize_text_field($value);
			}
		}

		$this->metas = apply_filters('postfilter:get_metas', $metas);
		return $this->metas;
	}

	private function get_filter_input(): array
	{
		global $wp_query;

		$query_vars = !empty($wp_query->query) ? $wp_query->query : [];

		if (wp_doing_ajax()) {
			$request_filter = !empty($_REQUEST['filter']) && is_array($_REQUEST['filter'])
				? $this->sanitize_array($_REQUEST['filter'])
				: [];

			$get_params = $this->sanitize_array($_GET ?? []);

			return array_merge($query_vars, $get_params, $request_filter);
		}

		return array_merge($query_vars, $this->sanitize_array($_GET ?? []));
	}

	private function sanitize_array(array $data): array
	{
		$sanitized = [];
		foreach ($data as $key => $value) {
			$clean_key = sanitize_key($key);
			$sanitized[$clean_key] = is_array($value)
				? $this->sanitize_array($value)
				: sanitize_text_field($value);
		}
		return $sanitized;
	}

	private function get_sanitized_input(string $key): ?string
	{
		return filter_input(INPUT_POST, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
		       ?? filter_input(INPUT_GET, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS)
			?: null;
	}

	// ============================================
	// Output Methods
	// ============================================

	public function render_filter(): string
	{
		do_action('postfilter:before_filter', $this);

		$current_page = max(1, get_query_var('paged') ?: 1);
		$wp_query = new \WP_Query($this->build_query($current_page));


		do_action('postfilter:query', $wp_query);

		return $this->render('filter', [
			'taxonomies' => $this->get_taxonomies(),
			'metas' => $this->get_metas(),
			'results' => $wp_query,
			'page' => $this->get_pagination($wp_query),
			's' => $this->get_sanitized_input('s') ?? '',
			'total' => $wp_query->found_posts,
			'active_filters' => $this->get_filters()
		]);
	}

	private function get_pagination(\WP_Query $query): ?string
	{
		// Only show pagination if there are multiple pages
		if ($query->max_num_pages <= 1) {
			return null;
		}

		$current = wp_doing_ajax() && !empty($_POST['paged'])
			? max(1, intval($_POST['paged']))
			: max(1, get_query_var('paged'));

		$filters = array_map(
			fn($f) => implode(',', array_keys($f)),
			$this->get_filters()
		);

		if ($search = $this->get_sanitized_input('s')) {
			$filters['s'] = $search;
		}

		return paginate_links([
			'base' => $this->get_base_url() . '%_%',
			'add_args' => $filters,
			'format' => 'page/%#%/',
			'current' => $current,
			'total' => $query->max_num_pages,
			'prev_text' => '« Prev',
			'next_text' => 'Next »',
			'end_size' => 1,
			'mid_size' => 2
		]);
	}

	private function get_base_url(): string
	{
		global $wp;

		if (is_page() || is_single()) {
			return trailingslashit(home_url($wp->request));
		}

		if (is_post_type_archive($this->post_type)) {
			return trailingslashit(get_post_type_archive_link($this->post_type));
		}

		if (is_tax() || is_category() || is_tag()) {
			if ($term = get_queried_object()) {
				return trailingslashit(get_term_link($term));
			}
		}

		return trailingslashit(home_url($wp->request));
	}

	protected function render(string $template, array $data = []): string
	{
		return $this->provider->render("filter/{$template}", $data);
	}

	// ============================================
	// Scripts & Headers
	// ============================================

	private function register_script(): void
	{
		$asset_url = plugin_dir_url(__FILE__) . 'assets/filter.js';

		$this->script = \Netdust\App::get(AssetManager::class)->script(
			'filter-js',
			$asset_url,
			['deps' => ['jquery'], 'footer' => true, 'ver' => '0.6'],
			false
		);

		add_action('wp_enqueue_scripts', function() {
			wp_register_script(
				$this->script->getHandle(),
				$this->script->getUrl(),
				$this->script->getDependencies(),
				$this->script->getVersion(),
				$this->script->getInFooter()
			);
		});
	}

	public function enqueue_script(): void
	{
		$this->script->setLocalizedVar('filter_data', [
			'filters' => $this->get_filters(),
			'metas' => $this->get_metas(),
			'ajaxurl' => admin_url('admin-ajax.php'),
			'action' => $this->id
		]);

		wp_enqueue_script($this->script->getHandle());
		$this->script->localize();
	}

	public function add_nocache_headers(): void
	{
		if (!empty($this->get_filter_input())) {
			nocache_headers();
		}
	}

	public function add_noindex_meta(): void
	{
		if (!empty($this->get_filter_input())) {
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		}
	}

	public function modify_term_link(string $link, \WP_Term $term, string $taxonomy): string
	{
		$slug = $this->slugify($taxonomy);
		return home_url("/?{$slug}={$term->slug}");
	}

	// ============================================
	// Utilities
	// ============================================

	public function clear_cache(): void
	{
		$this->taxonomies = null;
		$this->filters = null;
		$this->metas = null;
	}

	private function slugify(string $text): string
	{
		return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text)));
	}

	public function echo_template(): void
	{
		nocache_headers();
		$this->enqueue_script();
		echo $this->render_filter();
	}
}