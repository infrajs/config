<?php
use infrajs\access\Access;
use infrajs\load\Load;
use infrajs\config\Config;
use infrajs\each\Each;
use infrajs\path\Path;
use infrajs\mem\Mem;
use infrajs\nostore\Nostore;
use infrajs\template\Template;
use MatthiasMullie\Minify;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');
	require_once('vendor/autoload.php');
	Config::init();
}
Nostore::pub();



$re = isset($_GET['re']); //Modified re нужно обновлять с ctrl+F5
$debug = Access::debug();
if ($debug || $re) {
	header('Infrajs-Cache: false');
	$conf=Config::get();
	foreach($conf as $name=>$c){
		Config::collectJS($js, $name);	
	}
	$key = 'Infrajs::Config::js'.true;
	Mem::delete($key);
	$key = 'Infrajs::Config::js'.false;
	Mem::delete($key);
	header('Content-Type: text/javascript; charset=utf-8');
	echo $js;
	exit;
}



$p = explode(',', str_replace(' ', '', $_SERVER['HTTP_ACCEPT_ENCODING']));
$isgzip = in_array('gzip', $p);

$key = 'Infrajs::Config::js'.$isgzip; //Два кэша зазипованый и нет. Не все браузеры понимают зазипованую версию.

$js = Mem::get($key);

if (!$js) {
	header('Infrajs-Cache: false');
	$js = 'window.infra={}; window.infrajs={ }; infra.conf=('.Load::json_encode(Config::pub()).'); infra.config=function(){ return infra.conf; };';

	$conf=Config::get();
	foreach($conf as $name=>$c){
		Config::collectJS($js, $name);	
	}
	if ($isgzip) {
		$min = new Minify\JS($js);
		$js = $min->gzip();
	} else {
		$min = new Minify\JS($js);
		$js = $min->minify();
	}
	
	Mem::set($key, $js);
} else {
	header('Infrajs-Cache: true');
}
if ($isgzip) {
	header('Content-Encoding: gzip');
	header('Vary: accept-encoding');
	header('Content-Length: ' . strlen($js));
}
header('Content-Type: text/javascript; charset=utf-8');
echo $js;
