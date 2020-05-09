<?php

use infrajs\load\Load;
use infrajs\config\Config;
use infrajs\ans\Ans;

$js = 'if (!window.infra) window.infra={}; infra.conf=(' . Load::json_encode(Config::pub()) . '); ';
$js .= 'infra.config = function (name){ if(!name)return infra.conf; return infra.conf[name]; };';
$js .= 'window.Config = {}; Config.get = infra.config; Config.conf = infra.conf; export {Config}';


return Ans::js($js);
