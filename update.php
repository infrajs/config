<?php

use infrajs\update\Update;
use infrajs\config\Config;
use infrajs\load\Load;
use infrajs\path\Path;

$conf = Config::get();

$sys = array();
foreach ($conf as $name => $c) {
	if(!empty($c['clutch'])) {
		$sys[$name] = array();
		$sys[$name]['clutch'] = $c['clutch'];
	}
}

$json = Load::json_encode($sys);
file_put_contents(Path::$conf['cache'].'/.infra.json', $json);
