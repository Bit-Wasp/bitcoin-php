<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';

if (!$loader) {
    $loader = require __DIR__ . '/../../../../vendor/autoload.php';
}
\BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer::disableCache();
$loader->addPsr4('BitWasp\\Bitcoin\\Tests\\', __DIR__);
