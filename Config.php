<?php
namespace infrajs\config;
use infrajs\load\Load;
use infrajs\each\Each;
use infrajs\path\Path;
use infrajs\once\Once;


class Config {
	public static $conf = array();
	public static $exec = array();
	public static $sys = array(); //Генерируемый конфиг в cache
	public static $all = false; //флаг, что собраны все конфиги
	public static function init ()
	{
		Once::func( function(){			
			Config::add('conf', function ($name, $value, &$conf) {
				$valconf = $value::$conf;
				foreach ($conf as $k => $v) $valconf[$k] = $v; //merge нужно делать с сохранением ключей, даже для числовых ключей
				$conf = $valconf;
				//$conf=array_merge($value::$conf, $conf); //Второй массив важнее его значения остаются
				$value::$conf = &$conf;
			});
			/*Config::add('clutch', function ($name, $value, &$conf) { 
				//Имя расширения в котором найдено свойство, значение, весь конфиг того расширения
				//$dir = Path::theme('-'.$name.'/');
				foreach ($value as $plugin => $paths) {
					Each::exec($paths, function &($dir) use ($plugin, &$conf) {
						if (empty(Path::$conf['clutch'][$plugin])) Path::$conf['clutch'][$plugin] = [];

						//Все clutch складываем в Path
						if (!in_array($dir, Path::$conf['clutch'][$plugin])) Path::$conf['clutch'][$plugin][] = $dir;
						Config::load($dir.$plugin.'/.infra.json', $plugin);
						$r = null; return $r;
					});
				}
				//Path::$conf['clutch'][] = $value;
			});*/
			
			
			//Конфиг в кэш папке генерируется автоматически это единственный способ попасть в стартовую обработку нового расширения. Для clutch
			$sys = Config::load('!.infra.json');
			
			Config::load('.infra.json'); //При совпадени опций будет ошибка
			Config::load('~.infra.json');
			
			
			

			if (!isset(Config::$conf['path'])) Config::$conf['path'] = array();
			
			if(empty(Config::$conf['path']['cache'])) Config::$conf['path']['cache'] = Path::$conf['cache'];
			if(empty(Config::$conf['path']['data'])) Config::$conf['path']['data'] = Path::$conf['data'];
			if (empty(Config::$conf['path']['search'])) Config::$conf['path']['search'] = array('vendor/infrajs/');
			if (!$sys) Config::$conf['path']['search'] = Config::search();
			

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
			//require_once('vendor/infrajs/path/Path.php');
			spl_autoload_register( function ($class_name) {
				$p = explode('\\', $class_name);
				if (sizeof($p) < 3) return;
				
				//Ищем имя расширения по переданному полному адресу до Класса
				//Ситуация с именем расширения
				//infrajs/path/Path - path
				//infrajs/path/src/URN - path
				//infrajs/config/search/Search - config-search Search
				//infrajs/config/search/Search - config search/Search
				//infrajs/config/Search - config src/Search
				//path/Path - path
				//path/src/URN - path
				//config/search/Search - config-search

				$vendor = array_shift($p);
				$class = array_pop($p);
				$name = implode('-',$p);
				while (!Path::theme('-'.$name.'/') && sizeof($p)>1) {
					array_pop($p);
					$name = implode('-',$p);
				}

				if (!empty(Config::$exec[$name])) return;
				if (!Path::theme('-'.$name.'/')) return;

				Config::$exec[$name] = true;

				spl_autoload_call($class_name);
				
				Config::get($name); // <- Всё ради автоматического этого
			}, true, true);
			//set_error_handler( function () { //bugfix
			//	ini_set('display_errors', true);
			//});
			Config::get('index');
			foreach ($_GET as $name => $val) { //Параметр в адресной строке инициализирует соответствующее расширение
				if ($name{0}!='-') continue;
				$ext = substr($name, 1);
				Config::get($ext);
			}
		});
	}
	public static function search ()
	{
		//Используется в update.php
		//Заполнять Path::$conf['search'] нужно после того как пройдёт инициализация конфигов .infra.json
		//Чтобы значения по умолчанию не заменили сгенерированные значения
		return Once::func( function(){
			$search = array();
			$ex = array_merge(array(Config::$conf['path']['cache'], Config::$conf['path']['data']), Config::$conf['path']['search']);
			Config::scan('', function ($src, $level) use (&$search, $ex){
				if (in_array($src, $ex)) return true; //вглубь не идём

				if ($level < 2) return;
				if ($level >= 3) return true;
				if (!is_file($src.'.infra.json')) return;
				$r = explode('/', $src);
				array_pop($r);
				array_pop($r);
				
				$search[] = implode('/',$r).'/';
				return false; //вглубь не идём и в соседние папки тоже
			});
			$search = array_values(array_unique(array_merge(Config::$conf['path']['search'], $search)));
			return $search;
		});
		/*if (Config::$all) { //Если все конфиги были уже обраны, нужно заного пробежаться по найденным
			for ($i = 0; $i < sizeof($search); $i++) {
				$tsrc = $search[$i];
				if (!is_dir($tsrc)) continue;
				$files = scandir($tsrc);
				foreach ($files as $file) {
					if ($file{0} == '.') continue;
					if (!is_dir($tsrc.$file)) continue;
					Config::load($tsrc.$file.'/.infra.json', $file);
				}
			}
		}
		/*$comp = Load::loadJSON('composer.json');
		if ($comp && !empty($comp['require'])) {	
			foreach ($comp['require'] as $n => $v) {
				$r = explode('/', $n);
				
				if (sizeof($r) != 2) continue;
				$path = 'vendor/'.$r[0].'/';
				if (!in_array($path, Path::$conf['search'])){
					Path::$conf['search'][] = $path;
				}
			}
		}*/
	}
	/**
	 * Рекурсивный скан папки
	 * Функция $fn($src, $level) может возвращать управляющие данные
	 * null - идём дальше и вглубь и в ширь
	 * true - вглубь не идём, в ширь идём - переход к соседней папке
	 * false - вглубь не идём, в ширь не идём - выход на уровень выше
	 **/
	public static function scan($idir, $fn, $takefiles = false, $level = 0)
	{

		$src = Path::theme($idir);
		if (!$idir) $src = './';
		$d = opendir($src);
		$r = null;
		while ($file = readdir($d)) {

			if ($file{0}=='.') continue;
			$dir = $idir.Path::toutf($file);
			$isdir = is_dir($src.$file);
			if ($isdir) {
				$dir = $dir.'/';
				$file = $file.'/';
			}

			if ($takefiles && !$isdir || !$takefiles && $isdir) {
				$r = $fn($dir, $level);
				if ($r === true) {
					$r = null;
					continue;
				} 
				if ($r === false) {
					$r = null;
					break;
				}
				if (!is_null($r)) break;
			}

			if ($isdir) {
				$r = static::scan($dir, $fn, $takefiles, $level + 1);
				if (!is_null($r)) break; 
			}
		}
		closedir($d);
		return $r;
	}
	public static function &getAll()
	{
		Config::$all = true;
		Once::func( function () {
			Config::init();
			@header('Infrajs-Config-All: true');
			/**
			 * Для того чтобы в текущем сайте можно было разрабатывать расширения со своим конфигом, 
			 * нужно добавить путь до родительской папки с расширениями в path.config.search
			 * Папки data может конфликтовать так как она содержит общий конфиг, 
			 * А если родительская папка защитается за папку с расширениями папка .infra.json в data буде лишним
			 **/
			
			
			$files = scandir('.');
			foreach ($files as $name) {
				if ($name{0} == '.') continue;
				if (!is_dir($name)) continue;
				if (in_array($name.'/', array(Config::$conf['path']['cache'], Config::$conf['path']['data']))) continue;
				Config::load($name.'/.infra.json', $name);
			}
			
			if (is_dir('index/')) {
				$files = scandir('index/');
				foreach ($files as $name) {
					if ($name{0} == '.') continue;
					if (!is_dir('index/'.$name)) continue;
					Config::load('index/'.$name.'/.infra.json', $name);
				}
			}

			$path = Config::$conf['path'];
			for ($i = 0; $i < sizeof($path['search']); $i++) {
				$tsrc = $path['search'][$i];
				if (!is_dir($tsrc)) continue;
				$files = scandir($tsrc);
				foreach ($files as $name) {
					if ($name{0} == '.') continue;
					if (!is_dir($tsrc.$name)) continue;;
					Config::load($tsrc.$name.'/.infra.json', $name);
				}
			}
			/*foreach($path['clutch'] as $name => $val) {
				for ($i = 0; $i < sizeof($path['clutch'][$name]); $i++) {
					$tsrc = $path['clutch'][$name][$i];
					Config::load($tsrc.$name.'/'.'.infra.json', $name);
				}
			}*/
			foreach (Config::$conf as $name => $conf) {
				if ($name == 'path') continue;
				if (empty($conf['clutch'])) continue;
				if (!empty($conf['off'])) continue;
				foreach ($conf['clutch'] as $child => $val) {
					Each::exec($val, function &($src) use ($child) {
						$r = null;
						Config::load($src.$child.'/'.'.infra.json', $child);
						return $r;
					});
				}
			}	
			foreach (Config::$conf as $name => $val) {
				Config::get($name);
			}
		});
		return Config::$conf;
		
	}
	public static $ready = array();
	public static function &get($name = null)
	{
		
		if (!$name) return Config::getAll();
		if (isset(Config::$ready[$name])) return Config::$conf[$name];
		Config::$ready[$name] = true;
		Config::init();

		Config::load($name.'/.infra.json', $name);
		//Config::load('index/'.$name.'/.infra.json', $name);

		foreach (Config::$conf['path']['search'] as $dir) {
			Config::load($dir.$name.'/.infra.json', $name);	
		}
		if (isset(Config::$conf['path']['clutch'][$name])) {
			Each::exec(Config::$conf['path']['clutch'][$name], function &($src) use ($name) {
				$r = null;
				Config::load($src.$name.'/'.'.infra.json', $name);
				return $r;
			});
		}
		

		$conf = &Config::$conf;
		if (!isset($conf[$name])) {
			$r = array();
			return $r;
		}

		/*if (!empty($conf[$name]['clutch'])) {
			foreach ($conf[$name]['clutch'] as $child => $val) {
				Each::exec($val, function ($src) use ($child) {
					Config::load($src.$child.'/'.'.infra.json', $child);
				});
			}
		}*/
		/**
		 *	Порядок установки update, 
		 *	Порядок js и css
		 * 	
		**/
		if (!empty($conf[$name]['dependencies'])) {
			Each::exec($conf[$name]['dependencies'], function &($s) use ($name) {
				Config::get($s);
				$r = null; 
				return $r;
			});
		}

		//Должен быть до req.. чтобы conf уже обработался и в Path был правильный search
		foreach (Config::$list as $prop => $callback) {
			if (!empty($conf[$name][$prop])) {
				$callback($name, $conf[$name][$prop], $conf[$name]);
			}	
		}
		//if (isset($_GET['-config'])) echo $name.'<br>';
		if(!empty($conf[$name]['require'])&&empty($conf[$name]['off'])){
			Each::exec($conf[$name]['require'], function &($s) use ($name) {
				Path::req('-'.$name.'/'.$s);
				$r = null; return $r;
			});
		}
		return Config::$conf[$name];
	}
	public static function reqsrc($src)
	{
		Each::exec($src, function &($src){
			Path::req($src);
			$r = null; return $r;
		});
	}
	public static function load($isrc, $name = null)
	{	
		$src = Path::theme($isrc);
		if (!$src) return;
		return Once::func( function ($isrc) use ($name) {
			$src = Path::theme($isrc);
			$d = file_get_contents($src);
			
			try {
				$d = Load::json_decode($d);
			} catch (\Exception $e){ }

			if (!is_array($d)){
				echo '<pre>';
				throw new \Exception('Wrong config '.$src);
			}
			if ($name) {
				Config::accept($name, $d);
			} else {
				foreach ($d as $k => &$v) {
					//echo $k.'<br>';
					Config::accept($k, $v);
				}
				//if (!$name) echo '<b>'.$src.'</b><br>';
			}
			return $d;
		}, array($isrc));
	}
	public static $list = array();
	public static function add($prop, $callback)
	{
		self::$list[$prop] = $callback;
	}
	public static function accept($name, $v)
	{
		$conf=&Config::$conf;
		if (empty($conf[$name])) $conf[$name] = array();

		//if (!is_array($v)) return;
		foreach ($v as $kk => $vv) {
			if (isset($conf[$name][$kk])) continue; //То что уже есть в конфиге круче вновь прибывшего
			$conf[$name][$kk] = $vv;
		}
	}
	private static function pubclean($part)
	{
		if (empty($part['pub'])) return null;
		$newpart = array();
		Each::exec($part['pub'], function &($pub) use (&$newpart, &$part) {
			$r = null; 
			if (!isset($part[$pub])) return $r;
			$newpart[$pub] = $part[$pub];
			return $r;
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
			if (!is_null($res)) $pub[$i]=$res;
		}
		return $pub;
	}
}
