<?php

use infrajs\config\Config;

$conf = Config::pub();
header('Content-type: application/javascript');
echo 'export default ';
echo json_encode($conf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
