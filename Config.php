<?php
namespace infrajs\config;
use infrajs\load\Load;
use infrajs\path\Path;
use infrajs\once\Once;


class Config {
	public static $conf=array();
	public static $exec=false;
	public static $install=false;
	public static function init()
	{
		Once::exec('infrajs::Config::init', function() {
			Config::load('.infra.json');
			Config::load('~.infra.json');

			Config::get('path');
			Config::get('infra');
			Config::get('once');
			Config::get('hash');
			Config::get('load');
			Config::get('ans');
			
				
			/*
				echo '<pre>';
				print_r(get_declared_classes());
			 	exit;
			
				Debug проврить классы каких расширений после композера загружены и в ручную инициализировать их конфиги
				[139] => infrajs\path\Path
			    [140] => infrajs\infra\Config
			    [141] => infrajs\once\Once
			    [142] => infrajs\hash\Hash
			    [143] => infrajs\load\Load
			    [144] => infrajs\infra\Each
			    [145] => infrajs\ans\Ans
			    [146] => infrajs\mem\Mem

			*/
			spl_autoload_register(function($class_name){
				if(Config::$exec) return;
				$p=explode('\\',$class_name);
				if(sizeof($p)<3) return;
				$name=$p[1];
				if(!Path::theme('-'.$name.'/')) return;
				Config::$exec=true;
				spl_autoload_call($class_name);
				Config::$exec=false;
				static::get($name);
				if (Config::$install) static::get(); //Ключ install Заставляет загрузить все имеющийся расширения, чтобы они установились как надо.
			}, true, true);
			set_error_handler(function(){ //bugfix
				ini_set('display_errors',true);
			});
		});
	}
	public static function getAll()
	{
		Once::exec('Infrajs::Config::getAll', function () {
			header('Infrajs-Config: All');
			/**
			 * Для того чтобы в текущем сайте можно было разрабатывать расширения со своим конфигом, 
			 * нужно добавить путь до родительской папки с расширениями в path.config.search
			 * Папки data может конфликтовать так как она содержит общий конфиг, 
			 * А если родительская папка защитается за папку с расширениями папка .infra.json в data буде лишним
			 **/
			$path=Path::$conf;
			foreach($path['search'] as $tsrc) {

				$files = scandir($tsrc);
				foreach($files as $file){
					if ($file{0} == '.') continue;
					if (is_file($tsrc.$file)) continue;
					Config::load($tsrc.$file.'/.infra.json', $file);
				}
			}
		});
		return Config::$conf;
	}
	public static function get($name = null)
	{
		if (!$name) return static::getAll();
		Config::load('-'.$name.'/.infra.json', $name);
		if (!isset(Config::$conf[$name])) return null;
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
		Once::exec('Config::load::'.$src, function () use ($src, $name) {
			
			$path = Path::theme($src);
			if (!$path) {
				return;
				//if(!$name) return;
				//echo '<pre>';
				//throw new \Exception('Конфиг не найден '.$src);
			}
			$d=file_get_contents($path);

			$d=Load::json_decode($d);
			if ($name) {
				Config::accept($name, $d);
			} else {
				foreach ($d as $k => &$v) {
					Config::accept($k, $v);
				}
			}
		});
	}
	public static function accept($name, $v)
	{
		$conf=&Config::$conf;
		if (empty($conf[$name])) $conf[$name] = array();
		foreach ($v as $kk => $vv) {
			if (isset($conf[$name][$kk])) continue; //То что уже есть в конфиге круче вновь прибывшего
			if ($kk == 'require') {
				Each::exec($vv, function($s) use ($name) {
					Path::req('-'.$name.'/'.$s);
				});
			}else if ($kk == 'conf') {
				$conf[$name]=array_merge($vv::$conf, $conf[$name]);
				$vv::$conf=&$conf[$name];
			}else if ($kk == 'install') {
				if (Config::$install) Path::req('-'.$name.'/'.$vv);
			} else {
				$conf[$name][$kk] = $vv;
			}
		}
	}
	public static function &pub ($plugin = false) {
		$conf=Config::get();
		foreach ($conf as $i => $part) {
			$pub = @$part['pub'];
			if (is_array($pub)) {
				foreach ($part as $name => $val) {
					if (!in_array($name, $pub)) {
						unset($conf[$i][$name]);
					}
				}
			} else {
				unset($conf[$i]);
			}
		}
		if ($plugin) return $conf[$plugin];
		else return $conf;
	}
}