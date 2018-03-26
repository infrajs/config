<?php

use infrajs\update\Update;
use infrajs\config\Config;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\each\Each;



//search
//Анализируется папка vendor Находятся все производители поддерживающие конфигурационные файлы .infra.json
//Некий производитель angelcharly попадёт в список поиска, если у него есть библиотека с файлом .infra.json
//Эту обработку можно убрать если производители прописаны вручную в config.path.search проекта
//Без этой обработке, например, переопределения в кореновм .infra.json для расширения weather
//не применятся к Weather::$conf и неinfrajs расширения будет работать со значениями по умолчанию
//.infra.json в самих неinfrajs расширениях также не будет прочитан,
//но значения конфига по умолчанию и так указаны в переменной класса, вроде Weather::$conf по этому не скажется на работе
//В общем заполняем config.path.search путями до установленных расширений
//Config::search();

$search = Config::search();
if (!isset(Config::$sys['path'])) Config::$sys['path'] = array();
Config::$sys['path']['search'] = $search;
Config::$conf['path']['search'] = $search;



//clutch
Update::exec(); //Все updatы должны выполниться

Config::$sys['path']['clutch'] = array();
foreach (Config::$conf as $name => $c) { //clutch переносится из того места где был указан в то место где нужен
	if ($name == 'path') continue;
	if(empty($c['clutch'])) continue;
	if (!empty($c['off'])) continue;
	foreach($c['clutch'] as $k => $v) {
		if (empty(Config::$sys['path']['clutch'][$k])) Config::$sys['path']['clutch'][$k] = array();
		Each::exec($v, function &($dir) use ($k) {
			$r = null;
			if (in_array($dir, Config::$sys['path']['clutch'][$k])) return $r;
			Config::$sys['path']['clutch'][$k][] = $dir;
			return $r;
		});
	}
}
//clutch во время update не может подменить .infra.json если он уже был выполнен
//Сохраняем для работы поиска файла
//Для работы Update срабатывает getAll где каждый clutch в отдельности будет обработан

//При обновлении всё что было добавлено в Config::$sys должно попасть и в $conf;			
foreach (Config::$sys as $name => $v) {
	if (empty(Config::$conf[$name])) Config::$conf[$name] = array();
	foreach ($v as $kk => $vv) {
		Config::$conf[$name][$kk] = $vv;
	}
}
//Config::$conf['path']['clutch'] = Config::$sys['path']['clutch'];


//cache/.infra.json 
//В переменной что-то можно сохранять и использовать на старте системы. Перенос происходит при обновлении.
$json = Load::json_encode(Config::$sys);
file_put_contents(Path::$conf['cache'].'/.infra.json', $json);