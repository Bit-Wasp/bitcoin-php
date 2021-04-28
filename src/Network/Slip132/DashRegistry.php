<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Network\Slip132;

use BitWasp\Bitcoin\Key\Deterministic\Slip132\PrefixRegistry;
use BitWasp\Bitcoin\Script\ScriptType;

class DashRegistry extends PrefixRegistry
{
    protected static $table = [
        [["02fe52cc", "02fe52f8"], /* xpub */ [ScriptType::P2PKH]],
        [["02fe52cc", "02fe52f8"], /* xpub */ [ScriptType::P2SH, ScriptType::MULTISIG]],
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
