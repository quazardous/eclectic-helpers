<?php
/** @var \Composer\Autoload\ClassLoader $loader */
$loader = include __DIR__ . '/../vendor/autoload.php';
$loader->addPsr4('Quazardous\\Eclectic\\Helper\\Test\\', [__DIR__ . '/src/']);

