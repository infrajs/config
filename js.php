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

header('Infrajs-Cache: true');

$re = isset($_GET['re']); //Modified re нужно обновлять с ctrl+F5

$p = explode(',', str_replace(' ', '', $_SERVER['HTTP_ACCEPT_ENCODING']));

$debug = Access::debug();

$isgzip = !$debug && in_array('gzip', $p);

$key = 'Infrajs::Config::js'.$isgzip;

$js = Mem::get($key);

if (!$js || $debug || $re) {
	header('Infrajs-Cache: false');
	$js = 'window.infra={}; window.infrajs={ }; infra.conf=('.Load::json_encode(Config::pub()).'); infra.config=function(){ return infra.conf; };';


	$conf=Config::get();
	foreach($conf as $name=>$c){
	
		
		if (empty($c['js'])) continue;
		Each::exec($c['js'], function ($path) use ($name,&$js) {

			$src = '-'.$name.'/'.$path;
			if(!Path::theme($src)) {
				echo '<pre>';
				throw new \Exception('Не найден файл '.$src);
			}
			$js.= "\n\n".'//require js '.$src."\n";
			$js.= Load::loadTEXT($src).';';
		});
	}
	
	if (!$debug) {
		$min = new Minify\JS($js);
		if ($isgzip) {
			$js = $min->gzip();
		} else {
			$js = $min->minify();
		}
	}
	Mem::set($key,$js);
}

if ($isgzip) {
	header('Content-Encoding: gzip');
	header('Vary: accept-encoding');
	header('Content-Length: ' . strlen($js));
}

header('Content-Type: text/javascript; charset=utf-8');
echo $js;
