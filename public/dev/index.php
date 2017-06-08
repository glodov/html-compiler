<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$loader = include_once(__DIR__ . '/../../vendor/autoload.php');

use HtmlCompiler\Application;

$app = new Application;
$app->run($_SERVER['REQUEST_URI'], @$_COOKIE['locale']);

extract(get_object_vars($app));
ob_start();
include($app->getTplFile());
$html = ob_get_contents();
ob_end_clean();

$html = $app->processHtml($html);

header('Content-Type: text/html');
echo $html;

// <!--removeOnCompile-->
// <!--/removeOnCompile-->
// <remove-on-compile>
// </remove-on-compile>
// <div remove-on-compile>