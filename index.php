<?php
use infrajs\access\Access;
use infrajs\config\Config;

Access::admin(true);

$conf = Config::get();

echo '<pre>';
print_r($conf);

