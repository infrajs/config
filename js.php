<?php
use infrajs\load\Load;
use infrajs\config\Config;
use infrajs\router\Router;
use infrajs\ans\Ans;

if (!is_file('vendor/autoload.php')) {
	chdir('../../../');
	require_once('vendor/autoload.php');
	Router::init();
}


$js = 'if (!window.infra) window.infra={}; if (!window.infrajs) window.infrajs={}; infra.conf=('.Load::json_encode(Config::pub()).'); ';
$js .= 'infra.config = function (name){ if(!name)return infra.conf; return infra.conf[name]; };';
$js .= 'window.Config = {}; Config.get = infra.config; Config.conf = infra.conf;';


return Ans::js($js);