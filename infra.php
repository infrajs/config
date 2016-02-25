<?php
namespace infrajs\config;
use infrajs\path\Path;
if(isset($_GET['-config'])&&$_GET['-config']=='update'){
	Config::update();
}
$path = Config::get('path');
if ($path['fs']&&is_file($path['data'].'update')) {
	unlink($path['data'].'update');
	Config::get('access');
	//Файл update даёт право на тестирование сайта
	Config::$conf['access']['test']=true;
	if (!Config::$update) {
		header('Infrajs-Path-Update:true');
		Path::fullrmdir($path['cache']);
		Config::update();
	}
}
?>