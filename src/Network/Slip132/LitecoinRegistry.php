<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Network\Slip132;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;

class LitecoinRegistry extends PrefixRegistry
{
    protected static $table = [
        [["019d9cfe", "019da462"], /* xpub */ [ScriptType::P2PKH]],
        [["019d9cfe", "019da462"], /* xpub */ [ScriptType::P2SH, ScriptType::MULTISIG]],
        [["01b26792", "01b26ef6"], /* ypub */ [ScriptType::P2SH, ScriptType::P2WKH]],
        [["01b26792", "01b26ef6"], /* Ypub */ [ScriptType::P2SH, ScriptType::P2WSH, ScriptType::MULTISIG]],
    ];

    public function __construct()
    {
        $map = [];
        foreach (static::$table as list ($prefixList, $scriptType)) {
            $type = implode("|", $scriptType);
            $map[$type] = $prefixList;
        }

        parent::__construct($map);
    }
}
