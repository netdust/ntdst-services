<?php

namespace Netdust\Services\Vite;

class Vite {
	public string $distUri;
	public string $distPath;
	public string $wpEnqueueId;
	public string $server;
	public string $entryPoint;
	public bool $viteIsRunning;
	public array $jsDeps = [];

	public function __construct(bool $isChild, array $env) {
		$dir = $isChild ? get_stylesheet_directory() : get_template_directory();
		$dirUri = $isChild ? get_stylesheet_directory_uri() : get_template_directory_uri();

		// Use $env array from config.php
		$this->distUri = $dirUri . '/' . ($env['output_dir'] ?? 'dist');
		$this->distPath = $dir . '/' . ($env['output_dir'] ?? 'dist');
		$this->wpEnqueueId = $env['wp_enqueue_id'] ?? 'vite-app';
		$this->server = ($env['protocol'] ?? 'http') . '://' . ($env['host'] ?? 'localhost') . ':' . ($env['port'] ?? '5173');
		$this->entryPoint = $env['entry_point'] ?? 'src/main.js';
		$this->viteIsRunning = isset($env['node_env']) && $env['node_env'] === 'development' ? true : $this->checkServer();
	}

	public function init() {
		$viteIsRunning = $this->viteIsRunning;
		add_action('wp_enqueue_scripts', function () use ($viteIsRunning) {
			if ($viteIsRunning) {
				$this->viteDevAssets();
			} else {
				$this->viteBuiltAssets();
			}
		});
	}

	public function checkServer(): bool {
		$ch = curl_init($this->server . '/' . $this->entryPoint);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $httpcode === 200;
	}

	public function getProductionAssets() {
		$manifestPath = $this->distPath . '/.vite/manifest.json';
		$filelist = [
			'css' => [],
			'js' => [],
		];

		if (file_exists($manifestPath)) {
			$manifest = json_decode(file_get_contents($manifestPath), true);
			if (is_array($manifest) && isset($manifest[$this->entryPoint])) {
				$files = $manifest[$this->entryPoint];
				if (isset($files['css'])) {
					$filelist['css'] = $files['css'];
				}
				if (isset($files['file'])) {
					$filelist['js'][] = $files['file'];
				}
			}
		}

		return $filelist;
	}

	public function viteDevAssets() {
		$src = $this->server . '/' . $this->entryPoint;
		add_action('wp_head', function () use ($src) {
			echo '<script id="vite" type="module" crossorigin src="' . $src . '"></script>';
		});
	}

	public function viteBuiltAssets() {
		$filelist = $this->getProductionAssets();
		$i = 0;
		foreach ($filelist['css'] as $file) {
			$i++;
			wp_enqueue_style($this->wpEnqueueId . '-style-' . $i, $this->distUri . '/' . $file);
		}

		$i = 0;
		foreach ($filelist['js'] as $file) {
			$i++;
			wp_enqueue_script($this->wpEnqueueId . '-script-' . $i, $this->distUri . '/' . $file, $this->jsDeps, '', true);
		}
	}
}