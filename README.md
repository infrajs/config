# Система конфигурирования .infra.json
**Disclaimer:** Module is not complete and not ready for use yet.

dependencies:'module' - указывает модуль, который должен быть загружен "до". Для php не требуется так как зависимости подключатся при обращении к калссу через autoload. Используется если доступ к модулю осуществляется в js в первом потоке выполнения. Во вложенных вызовах всё в любом случае будет доступно и указывать dependencies не требуется. Секция конфига dependencies будет располагаться до секции конфига указавшего эту зависимость модуля. При всех пробежках сначало будет обработка dependencies и только потом модуля указавшего эту зависимость.

# use
```html
<head>
	<script async defer src="/-config/js.php"></script>
</head>
<body>
	...
	<script>
		window.addEventListener('load', function () { //if jquery in .infra.json you can't use $(function() {...
			alert('use infrajs or any loaded scripts like jquery');
		});
	</script>
	...
</body>
```

# infra.json

```json
{
	"dependencies":"event",
	"require":"script.php",
	"pub":"propname",
	"conf":"Access::$conf",
	"off": false, //true запрещает require
	"testerjs":"test.js" //(Свойство обрабатывается [infrajs/tester](https://github.com/infrajs/tester))
}
```


