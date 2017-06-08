<?php

include_once(__DIR__ . '/../includes/application.class.php');

class CliController
{
	private $app;

	public function __construct()
	{
		$this->app = new Application;
	}

	public function run()
	{
		$actions = [];
		print("Select an action:\n");
		foreach (get_class_methods($this) as $method) {
			if (substr($method, -6) == 'Action') {
				$action = substr($method, 0, strlen($method) - 6);
				$actions[] = $action;
				printf("  %d. %s\n", count($actions), $action);
			}
		}

		$action = null;
		do {
			$line = readline('> ');
			if (in_array($line, ['exit', 'quit'])) {
				print("Goodbye ;)\n");
				return false;
			}
			if (is_numeric($line) && isset($actions[((int) $line) - 1])) {
				$line = $actions[((int) $line) - 1];
			}
			$action = $line . 'Action';
			if (!method_exists($this, $action)) {
				$action = null;
			}
		} while (null === $action);

		return call_user_func([$this, $action]);
	}

	private function doesPageExist($page)
	{
		$pages = $this->app->getPages();
		if (isset($pages[$page->name])) {
			return true;
		}
		foreach ($pages as $item) {
			if ($item->url == $page->url) {
				return true;
			}
		}
		return false;
	}

	private function decodeName($string, $default = 'index')
	{
		$name = preg_replace('/[^\w-\s]+/', '', $string);
		$name = preg_replace('/[\s-]+/', '_', $name);
		if (!$name) {
			$name = $default;
		}
		$name = strtolower($name);
		return $name;		
	}

	private function save($data, $filename, $locale = null, $dir = 'data')
	{
		$file = __DIR__ . '/../' . $dir . '/' 
			. ($locale ? $locale . '/' : '') . $filename;
		if (!is_scalar($data)) {
			$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
		file_put_contents($file, $data);
	}

	private function drop($filename, $locale = null, $dir = 'data')
	{
		$file = __DIR__ . '/../' . $dir . '/' 
			. ($locale ? $locale . '/' : '') . $filename;
		unlink($file);
	}

	public function addPageAction()
	{
		// add .html to every [locale]
		// add .json to every [locale]
		// add item in navigation
		print("Adding a page\n");

		$url = readline('Enter url: ');
		$url = $url ? $url : '/';

		$default = $this->decodeName($url);

		$name = readline('Enter name [' . $default . ']: ');
		$name = $this->decodeName($name, $default);

		$page = (object) [
			'url'  => $url,
			'name' => $name,
			'menu' => true
		];

		if ($this->doesPageExist($page)) {
			printf("Page [%s: %s] already exists\n", $name, $url);
			exit;
		}

		$menu = readline('Is page in menu? [Y/n]: ');
		$page->menu = in_array($menu, ['', 'Y', 'y']);

		$titles = [];
		foreach ($this->app->getLocales() as $locale => $item) {
			$titles[$locale] = (object) ['menu' => '', 'title' => ''];
			$titles[$locale]->title = readline('Enter title (' . $item->title . '): ');

			if (!$page->menu) {
				continue;
			}
			$titles[$locale]->menu = readline('Enter menu (' . $item->title . '): ');
			if (!$titles[$locale]->menu) {
				$titles[$locale]->menu = $titles[$locale]->title;
			}
		}

		// save in pages.json
		$pages   = $this->app->getPages();
		$pages[$page->name] = $page;
		$this->save($pages, 'pages.json');

		$template = file_get_contents(__DIR__ . '/../tpl/examples/template.html');

		foreach ($titles as $locale => $title) {
			// save in data/[locale]/[page->name].json
			$item = [
				'menu'  => $title->menu,
				'title' => $title->title
			];
			$this->save($item, $page->name . '.json', $locale);

			// save in tpl/pages/[locale]/[page->name].html
			$html = str_replace('%title%', $title->title, $template);
			$this->save($html, $page->name . '.html', $locale, 'tpl/pages');
		}

		printf("Page %s successfully added\n", $page->name);
	}

	public function delPageAction()
	{
		// add .html to every [locale]
		// add .json to every [locale]
		// add item in navigation
		print("Select a page to delete:\n");

		$pages = [];
		foreach ($this->app->getPages() as $name => $page) {
			$pages[] = $page;
			printf("  %d. %-30s %s\n", count($pages), $page->name, $page->url);
		}

		$id = readline('Enter id: ');
		if (!isset($pages[(int) $id - 1])) {
			printf("Page with id %s not found\n", $id);
			exit;
		}

		$page = $pages[(int) $id - 1];

		// save in pages.json
		$pages   = $this->app->getPages();
		unset($pages[$page->name]);
		$this->save($pages, 'pages.json');

		foreach ($this->app->getLocales() as $locale => $title) {
			// save in data/[locale]/[page->name].json
			$this->drop($page->name . '.json', $locale);

			// save in tpl/pages/[locale]/[page->name].html
			$this->drop($page->name . '.html', $locale, 'tpl/pages');
		}

		printf("Page %s successfully removed\n", $page->name);
	}

	public function addLangAction()
	{

	}

	public function delLangAction()
	{

	}
}

$cli = new CliController;
$cli->run();