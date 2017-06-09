<?php

include_once(__DIR__ . '/../vendor/autoload.php');

use HtmlCompiler\Application;

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

	private function save($data, $file)
	{
		if (!is_scalar($data)) {
			$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
		if (!file_exists(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}
		file_put_contents($file, $data);
	}

	private function getPageFile($name, $locale = null)
	{
		$file = $this->app->dataDir . '/pages/';
		if ($locale) {
			$file .= $locale . '/' . $name . '.json';
		} else {
			$file .= 'db.json';
		}
		return $file;
	}

	private function getTplFile($name = null, $locale = null)
	{
		if ($name === null && $locale === null) {
			return $this->app->appDir . '/tpl/examples/template.html';
		}
		return $this->app->appDir . '/tpl/pages/' . $locale . '/' . $name . '.html';
	}

	public function savePage($page)
	{
		$template = file_get_contents($this->getTplFile());

		foreach ($page->__locales as $locale => $value) {
			// save in data/pages/[locale]/[page->name].json
			$name = $page->__primary;
			$file = $this->getPageFile($page->$name, $locale);
			$this->save((object) $value, $file);

			// save in tpl/pages/[locale]/[page->name].html
			$str = [];
			foreach ($value as $k => $v) {
				$str["%$k%"] = $v;
			}
			$html = strtr($template, $str);
			$file = $this->getTplFile($page->$name, $locale);
			$this->save($html, $file);
		}

		$name = $page->__primary;
		// $page->$name

		foreach ($page as $k => $v) {
			if (substr($k, 0, 2) == '__') {
				unset($page->$k);
			}
		}

		$pages = $this->app->getPages();
		$pages[$page->$name] = $page;
		$file = $this->getPageFile($page->$name);
		$this->save($pages, $file);
	}

	public function dropPage($page)
	{
		// save in pages.json
		$pages   = $this->app->getPages();
		unset($pages[$page->name]);
		$file = $this->getPageFile($page->name);
		$this->save($pages, $file);

		// delete pages [locale]/[page].json & [locale]/[page].tpl
		foreach ($this->app->getLocales() as $locale => $title) {
			$file = $this->getPageFile($page->name, $locale);
			unlink($file);

			$file = $this->getTplFile($page->name, $locale);
			unlink($file);
		}		
	}

	public function addPageAction()
	{
		// add .html to every [locale]
		// add .json to every [locale]
		// add item in navigation
		print("Adding a page\n");

		$toRead = [];

		$locales = $this->app->getLocales();

		$config = $this->app->getPagesConfig();
		foreach ($config as $fieldName => $field) {
			$required = isset($field->required) && $field->required;
			$primary  = isset($field->primary) && $field->primary;
			if (isset($field->locales) && $field->locales) {
				foreach ($locales as $locale => $item) {
					$to = clone $field;
					$str = [];
					foreach ($item as $k => $v) {
						$str['{locale.' . $k .'}'] = $v;
					}
					$to->prompt     = strtr($field->prompt, $str);
					$to->__name     = $locale . '.' . $fieldName;
					$to->__primary  = false;
					$to->__required = isset($field->required) && $field->required;
					$to->__unique   = false;
					$toRead[] = $to;
				}
			} else {
				$to = clone $field;
				$to->__name     = $fieldName;
				$to->__primary  = isset($field->primary) && $field->primary;
				$to->__required = isset($field->required) && $field->required;
				$to->__unique   = isset($field->unique) && $field->unique;
				$toRead[] = $to;
			}
		}

		$page = (object) [
			'__primary' => null,
			'__locales' => [],
			'__unique'  => []
		];

		$pages = $this->app->getPages();

		foreach ($toRead as $what) {
			if ($what->__primary) {
				$page->__primary  = $what->__name;
				$page->__unique[] = $what->__name;
			}
			if ($what->__unique) {
				$page->__unique[] = $what->__name;
			}
		}

		if (!$page->__primary) {
			print("Primary field is undefined\n");
			exit;
		}

		foreach ($toRead as $what) {
			do
			{
				$correct = true;
				$value = trim(readline($what->prompt));
				if ($required && '' == $value) {
					print("  > Required field\n");
					$correct = false;
				}
				if ($what->__unique) {
					$name = $what->__name;
					foreach ($pages as $item) {
						if (isset($item->$name) && $item->$name == $value) {
							print("  > Value is not unique\n");
							$correct = false;
							break;
						}
					}
				}
			} while (!$correct);
			$arr = explode('.', $what->__name);
			if (count($arr) > 1) {
				if (!isset($page->__locales[$arr[0]])) {
					$page->__locales[$arr[0]] = [];
				}
				$page->__locales[$arr[0]][$arr[1]] = $value;
			} else {
				$name = $arr[0];
				$page->$name = $value;
			}
		}

		$this->savePage($page);

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

		$this->dropPage($page);

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