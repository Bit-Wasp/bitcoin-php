<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Network\Slip132;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;

class ZcashRegistry extends PrefixRegistry
{
    protected static $table = [
        [["0488ade4","0488b21e"], /* xpub */ [ScriptType::P2PKH]],
        [["0488ade4","0488b21e"], /* xpub */ [ScriptType::P2SH, ScriptType::MULTISIG]],
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
