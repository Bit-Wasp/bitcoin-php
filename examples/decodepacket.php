<?php

require_once "../vendor/autoload.php";

use BitWasp\Buffertools\Buffer;

if (!isset($argv[1])) {
    die("  [error! provide bitcoin packet hex] \n" . "Usage: php " . $argv[0] . " <hex of packet>\n");
}

$hex = $argv[1];
$net = new \BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer(\BitWasp\Bitcoin\Bitcoin::getDefaultNetwork());

print_r($net->parse(Buffer::hex($hex)));
