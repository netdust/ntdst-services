<?php

namespace Netdust\Services\Settings;

use Netdust\Core\File;
use Netdust\Core\ServiceProvider;
use Netdust\Logger\Logger;
use Netdust\Service\Assets\AssetManager;
use Netdust\Traits\Mixins;
use Netdust\View\TemplateServiceProvider;


class SettingsServiceProvider extends ServiceProvider
{
    public function register() {
    }

    public function boot() {

	    $conf = [
		    'ajax_url' => admin_url('admin-ajax.php'),
		    'panel_nonce' => wp_create_nonce('panel_nonce'),
		    'table_nonce' => wp_create_nonce('table_nonce'),
		    'cache_nonce' => wp_create_nonce('clear_cache_nonce'),
	    ];

	    $asset = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
	    $this->container->get( AssetManager::class )->script(
		    'alpinejs', $asset,  ['attr'=>['defer'=>true],'to'=>['admin']]
	    );


		$asset = plugin_dir_url( __FILE__ ) . 'assets/components/panelComponent.js';
		$this->container->get( AssetManager::class )->script(
			'panel-component', $asset,  ['ver'=>'0.1','deps'=>['alpinejs'],'to'=>['admin']]
		);
		$asset = plugin_dir_url( __FILE__ ) . 'assets/components/tableComponent.js';
		$this->container->get( AssetManager::class )->script(
			'table-component', $asset,  ['ver'=>'0.1','deps'=>['alpinejs'],'to'=>['admin']]
		);

	    $asset = plugin_dir_url( __FILE__ ) . 'assets/settings.js';
	    $this->container->get( AssetManager::class )->script(
		    'settings-js', $asset,  ['ver'=>'0.1','deps'=>['alpinejs', 'panel-component', 'table-component'],'to'=>['admin'],'localized'=>[
			    'wpConfig',[
				    'ajaxUrl' => $conf['ajax_url'],
				    'panelNonce' => $conf['panel_nonce'],
				    'tableNonce' => $conf['table_nonce'],
				    'cacheNonce' => $conf['cache_nonce']
			    ]]]
	    );

	    $asset = plugin_dir_url( __FILE__ ) . 'assets/components.css';
	    $this->container->get( AssetManager::class )->style(
		    'components-css', $asset,  ['ver'=>'0.1','to'=>['admin']]
	    );

	    $asset = plugin_dir_url( __FILE__ ) . 'assets/settings.css';
	    $this->container->get( AssetManager::class )->style(
		    'settings-css', $asset,  ['ver'=>'0.1','to'=>['admin']]
	    );

	    add_action('wp_ajax_admin-panel_submit', [$this,'handle_my_panel_submit'] );
	    add_action('wp_ajax_get_posts_table', [$this,'handle_get_posts_table'] );
	    add_action('wp_ajax_clear_cache', [$this,'handle_clear_cache'] );

    }

	public function handle_my_panel_submit() {
		check_ajax_referer('panel_nonce', 'nonce');
		$data = json_decode($_POST['data'], true);


		wp_send_json_success(['message' => 'Form submitted successfully']);
	}

	public function handle_get_posts_table() {
		check_ajax_referer('posts_nonce', 'nonce');

		$page = intval($_POST['page']);
		$per_page = intval($_POST['per_page']);
		$search = sanitize_text_field($_POST['search']);



		wp_send_json_success([
			'html' => '<table><tbody></tbody></table>',
			'total' => 0
		]);
	}

	public function handle_clear_courses_cache() {
		check_ajax_referer('clear_cache_nonce', 'nonce');
		wp_send_json_success(['message' => 'Cache cleared']);
	}
}