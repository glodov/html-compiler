<?php

include_once(__DIR__ . '/../vendor/autoload.php');

use HtmlCompiler\Application;

class CliController
{
	private $app;

	public function compile()
	{
		if (!defined('LIVE_MODE')) {
			define('LIVE_MODE', true);
		}
		if (!defined('DEV_MODE')) {
			define('DEV_MODE', false);
		}
		$app = new Application();
		if (!LIVE_MODE || DEV_MODE) {
			throw new Exception('Compile must work in LIVE_MODE only');
		}
		$this->app = $app;

		static::rmdir($app->pubDir);
		static::mkdir($app->pubDir);

		$this->initModules(LIVE_MODE);

		static::copy($app->devDir . '/assets', $app->pubDir . '/assets');

		foreach ($app->getLocales() as $locale => $l) {
			foreach ($app->getPages() as $page) {
				if (preg_match('/^(http:\/\/|https:\/\/|\/\/)/', $page->url)) {
					continue;
				}

				printf("%s\n", $page->url);

				$prefix = '/';
				if ('/' != $l->url) {
					$prefix = $l->url;
				}

				$url = $prefix . $page->url;

				$buildFile = $app->pubDir . $prefix . '/' . $page->name . '.html';
				$dir = dirname($buildFile);
				static::mkdir(dirname($buildFile));

				ob_start();
				$app->run($url, $locale);
				extract(get_object_vars($app));
				include($app->getTplFile());
				$data = ob_get_contents();
				ob_end_clean();

				file_put_contents($buildFile, $data);
			}
		}
	}

	public function update()
	{
		if (!defined('LIVE_MODE')) {
			define('LIVE_MODE', true);
		}
		if (!defined('DEV_MODE')) {
			define('DEV_MODE', false);
		}
		$this->app = new Application();

		$this->initModules(false);
		$this->initModules(true);
	}

	private function initModules($liveMode)
	{
		$app = $this->app;
		$mode = false;
		$prefix = 'dev';
		if ($liveMode) {
			$mode = true;
			$prefix = 'live';
		}

		$file = $app->appDir . '/config/compile.json';
		$json = json_decode(file_get_contents($file), true);

		$modules = [];
		$styles = $app->getStyles($mode);
		foreach ($styles as $item) {
			if ($item->module) {
				$modules[$prefix][$item->module] = true;
			}
		}

		$scripts = $app->getScripts($mode);
		foreach ($scripts as $item) {
			if ($item->module) {
				$modules[$prefix][$item->module] = true;
			}
		}

		foreach ($modules as $prefix => $list) {
			$dir = $app->appDir . '/public/' . $prefix . '/node_modules';
			// delete folder
			static::rmdir($dir);
			if (count($list)) {
				static::mkdir($dir);
			}

			foreach ($list as $module => $bool) {
				$src  = $app->appDir . '/node_modules/' . $module;
				$dest = $dir . '/' . $module;
				static::copy($src, $dest);
			}
		}		
	}

	public static function rmdir($dir)
	{ 
		if (!file_exists($dir)) {
			return true;
		}
		$files = array_diff(scandir($dir), array('.','..')); 
		foreach ($files as $file) { 
			(is_dir("$dir/$file")) ? static::rmdir("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir); 
  } 

  public static function mkdir($dir, $mode = 0755, $recursive = true)
  {
  	if (file_exists($dir)) {
  		return false;
  	}
  	return mkdir($dir, $mode, $recursive);
  }

  public static function copy($src, $dst)
  {
    $dir = opendir($src); 
    static::mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                static::copy($src . '/' . $file, $dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file, $dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir);
  }
}

$cli = new CliController;
$action = isset($argv[1]) ? $argv[1] : '';
if (!method_exists($cli, $action)) {
	$action = 'compile';
}

call_user_func([$cli, $action]);