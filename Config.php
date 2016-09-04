<?php
namespace infrajs\config;
use infrajs\load\Load;
use infrajs\each\Each;
use infrajs\path\Path;
use infrajs\once\Once;


class Config {
	public static $conf = array();
	public static $exec = array();
	public static $all = false; //флаг, что собраны все конфиги
	public static function init ()
	{
		Once::exec(__FILE__.'::init', function() {
			@header('Infrajs-Config-All: false');
			require_once('vendor/infrajs/path/Path.php');
			spl_autoload_register( function ($class_name) {
				$p = explode('\\', $class_name);
				if (sizeof($p) < 3) return;
				
				//Ищем имя расширения по переданному полному адресу до Класса
				//Ситуация с именем расширения
				//infrajs/path/Path - path
				//infrajs/path/src/URN - path
				//infrajs/config/search/Search - config-search
				//path/Path - path
				//path/src/URN - path
				//config/search/Search - config-search

				array_shift($p);
				do {
					array_pop($p);
					//path - path
					//path/src - path - найдём во втророй интерации
					//config/search - config-search - найдём в первой интерации
					$name = implode('-', $p);
					if (Path::theme('-'.$name.'/')) break;
				} while (sizeof($p)>1);

				if (!empty(Config::$exec[$name])) return;
				if (!Path::theme('-'.$name.'/')) return;
				Config::$exec[$name] = true;
				
				spl_autoload_call($class_name);

				Config::get($name); // <- Всё ради автоматического этого
			}, true, true);
			set_error_handler( function () { //bugfix
				ini_set('display_errors', true);
			});
			Config::add('conf', function ($name, $value, &$conf) {
				$valconf = $value::$conf;
				foreach ($conf as $k=>$v) $valconf[$k]=$v; //merge нужно делать с сохранением ключей, даже для числовых ключей
				$conf = $valconf;
				//$conf=array_merge($value::$conf, $conf); //Второй массив важнее его значения остаются
				$value::$conf=&$conf;
			});
			Config::add('clutch', function ($name, $value, &$conf) { 
				//Имя расширения в котором найдено свойство, значение, весь конфиг того расширения
				//$dir = Path::theme('-'.$name.'/');
				foreach ($value as $plugin => $paths) {
					Each::exec($paths, function ($dir) use ($plugin, &$conf) {
						if (empty(Path::$conf['clutch'][$plugin])) Path::$conf['clutch'][$plugin] = [];
						if (!in_array($dir, Path::$conf['clutch'][$plugin])) Path::$conf['clutch'][$plugin][] = $dir;
						Config::load($dir.$plugin.'/.infra.json', $plugin);
					});
				}
				//Path::$conf['clutch'][] = $value;
			});
			Config::load('.infra.json');
			Config::load('~.infra.json');

			//Конфиг в кэш папке генерируется автоматически это единственный способ попасть в стартовую обраотку нового расширения. Для clutch
			Config::load('!.infra.json');

			Config::get('path');
			Config::get('config');
			Config::get('each');
			Config::get('hash');
			Config::get('once');
			Config::get('load');
			Config::get('ans');
			
			/* 
				echo '<pre>';
				print_r(get_declared_classes());
				exit;
				Debug проврить классы каких расширений после композера загружены и в ручную инициализировать их конфиги
			    [132] => infrajs\config\Config
				[133] => infrajs\once\Once
				[134] => infrajs\hash\Hash
				[135] => infrajs\path\Path
				[136] => infrajs\load\Load
				[137] => infrajs\each\Each
				[138] => infrajs\ans\Ans
			*/
		});
	}
	
	public static function &getAll()
	{
		Config::$all = true;
		Once::exec('Infrajs::Config::getAll', function () {
			Config::init();
			@header('Infrajs-Config-All: true');
			/**
			 * Для того чтобы в текущем сайте можно было разрабатывать расширения со своим конфигом, 
			 * нужно добавить путь до родительской папки с расширениями в path.config.search
			 * Папки data может конфликтовать так как она содержит общий конфиг, 
			 * А если родительская папка защитается за папку с расширениями папка .infra.json в data буде лишним
			 **/
			
			
			$files = scandir('.');
			foreach ($files as $file) {
				if ($file{0} == '.') continue;
				if (!is_dir($file)) continue;
				if (in_array($file.'/', array(Path::$conf['cache'], Path::$conf['data']))) continue;
				Config::load($tsrc.$file.'/.infra.json', $file);
			}

			$path = &Path::$conf;
			for ($i = 0; $i < sizeof($path['search']); $i++) {
				$tsrc = $path['search'][$i];
				if (!is_dir($tsrc)) continue;
				$files = scandir($tsrc);
				foreach ($files as $file) {
					if ($file{0} == '.') continue;
					if (!is_dir($tsrc.$file)) continue;
					Config::load($tsrc.$file.'/.infra.json', $file);
				}
			}
		});
		
		return Config::$conf;
	}
	public static function &get($name = null)
	{
		
		if (!$name) return Config::getAll();

		Once::exec(__FILE__.'::get'.$name, function () use ($name) {

			Config::init();


			Config::load($name.'/.infra.json', $name);

			foreach(Path::$conf['search'] as $dir) {
				Config::load($dir.$name.'/.infra.json', $name);	
			}
			if (!isset(Config::$conf[$name])) {
				$r = array();
				return $r;
			}
		});
		return Config::$conf[$name];
	}
	public static function reqsrc($src)
	{
		Each::exec($src, function ($src){
			Path::req($src);
		});
	}
	public static function load($src, $name = null)
	{	
		$src = Path::theme($src);
		if (!$src) return;
		Once::exec('Config::load::'.$src, function () use ($src, $name) {
			
			$d = file_get_contents($src);
			try {
				$d = Load::json_decode($d);
			}catch(\Exception $e){ }
			if(!is_array($d)){
				echo '<pre>';
				throw new \Exception('Wrong config '.$src);
			}
			if ($name) {
				Config::accept($name, $d);
			} else {
				foreach ($d as $k => &$v) {
					Config::accept($k, $v);
				}
			}
		});
	}
	public static $list = array();
	public static function add($prop, $callback)
	{
		self::$list[$prop] = $callback;
	}
	public static function accept($name, $v)
	{
		$conf=&Config::$conf;
		if (!empty($v['dependencies'])) {
			//Должны быть добавлены в conf ДО $name
			/**
			 * Используется для порядка загрузки javascript
			 * 
			 **/
			Each::exec($v['dependencies'], function($s) use ($name) {
				$r=Config::get($s);
			});
		}
		if (empty($conf[$name])) $conf[$name] = array();

		if (!is_array($v)) return;
		foreach ($v as $kk => $vv) {
			if (isset($conf[$name][$kk])) continue; //То что уже есть в конфиге круче вновь прибывшего
			$conf[$name][$kk] = $vv;
		}

		foreach (self::$list as $prop => $callback) {
			if (!empty($conf[$name][$prop])) {
				$callback($name, $conf[$name][$prop], $conf[$name]);
			}	
		}

		if(!empty($conf[$name]['require'])&&empty($conf[$name]['off'])){
			Each::exec($conf[$name]['require'], function($s) use ($name) {
				Path::req('-'.$name.'/'.$s);
			});
		}
		
	}
	private static function pubclean($part)
	{
		if (empty($part['pub'])) return null;
		$newpart = array();
		Each::exec($part['pub'], function ($pub) use (&$newpart, &$part) {
			if (!isset($part[$pub])) return;
			$newpart[$pub]=$part[$pub];
		});
		return $newpart;
	}
	public static function &pub($plugin = null) 
	{
		if ($plugin) {
			$conf = Config::get($plugin);
			$pub = Config::pubclean($conf);
			return $pub;
		}

		$pub = array();

		$conf = Config::get();
		foreach ($conf as $i => $part) {
			$res = Config::pubclean($part);
			if ($res) $pub[$i]=$res;
		}
		return $pub;
	}
}