# Система конфигурирования .infra.json
**Disclaimer:** Module is not complete and not ready for use yet.

Для автоматической поддержки сторонних вендоров, кроме infrajs нужно использовать расширение [infrajs/config-search](https://github.com/infrajs/config-search)

## Установка через composer

```json
{
	"reqiure":{
		"infrajs/config":"~1"
	}
}
```

## Использование
В папке расширения в vendor или в подпапке проекта или в корне проекта создаётся файл **.infra.json** в который выносятся параметры
```json
{
	"name":"Лёха"
}
```
В php затем обращаемся к этим параметрам.
```php
use infrajs\config\Config;

$conf = Config::get('имя расширения');
echo $conf['name']; //Лёха
```
Имя расширения совпадает с имененм папки или с ключём в корневом конфиге


## Специальные свойства в .infra.json
```json
{
	"dependencies":"event",
	"require":"script.php",
	"pub":"propname",
	"conf":"infrajs\\access\\Access",
	"off": false, 		
	"js": "path/to/js",  	
	"tester":"test.php", 	
	"testerjs":"test.js" 	
}
```
## Порядок выполнения dependencies
dependencies:'module' - указывает модуль, который должен быть загружен "до". Для php не требуется так как зависимости подключатся при обращении к калссу через autoload. Используется если доступ к модулю осуществляется в js в первом потоке выполнения. Во вложенных вызовах всё в любом случае будет доступно и указывать dependencies не требуется. Секция конфига dependencies будет располагаться до секции конфига указавшего эту зависимость модуля. При всех пробежках сначало будет обработка dependencies и только потом модуля указавшего эту зависимость.

## Параметр off
По умолчанию false. true запрещает require и js
## Параметр js
Путь до javascript файлов. Свойство обрабатывается [infrajs/collect](https://github.com/infrajs/collect)
## Параметр tester и testerjs
Свойство обрабатывается [infrajs/tester](https://github.com/infrajs/tester)

## Подмена и расширение парарметров дефолтного конфига расширения
Переменная Config::$sys предназначена при записи в неё значений, которые должны подменять оригинальные значения из конфига какого-то расширения. Используется с [infrajs/update](https://github.com/infrajs/update). ```Config::$sys``` - массив с конфигами расширений или двухмерный массив с конкретными параметрами, которые в дальнейшем автоматически сохраняются в ```!cache/.infra.json``` и инициализируются при каждом запросе к серверу. Пример использования в расшиении [akiyatkin/catalog-range](https://github.com/akiyatkin/catalog-range/blob/master/update.php).

