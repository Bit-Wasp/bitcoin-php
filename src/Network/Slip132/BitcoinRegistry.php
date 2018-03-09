<?php

namespace BitWasp\Bitcoin\Network\Slip132;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;

class BitcoinRegistry extends PrefixRegistry
{
    public function __construct()
    {
        $map = [];
        foreach ([
                     // private, public
                     [["0488ade4", "0488b21e"], /* xpub */ [ScriptType::P2PKH]],
                     [["0488ade4", "0488b21e"], /* xpub */ [ScriptType::P2SH, ScriptType::P2PKH]],
                     [["049d7878", "049d7cb2"], /* ypub */ [ScriptType::P2SH, ScriptType::P2WKH]],
                     [["0295b005", "0295b43f"], /* Ypub */ [ScriptType::P2SH, ScriptType::P2WSH, ScriptType::P2PKH]],
                     [["04b2430c", "04b24746"], /* zpub */ [ScriptType::P2WKH]],
                     [["02aa7a99", "02aa7ed3"], /* Zpub */ [ScriptType::P2WSH, ScriptType::P2PKH]],
                 ] as $row) {
            list ($prefixList, $scriptType) = $row;
            $type = implode("|", $scriptType);
            $map[$type] = $prefixList;
        }

        parent::__construct($map);
    }
}
