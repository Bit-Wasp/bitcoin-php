<?php

namespace BitWasp\Bitcoin\Key\KeyToScript\Decorator;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

class P2shScriptDecorator extends ScriptHashDecorator
{
    /**
     * @var array
     */
    protected $allowedScriptTypes = [
        ScriptType::P2PKH,
        ScriptType::P2PK,
        ScriptType::P2WKH,
    ];

    /**
     * @var string
     */
    protected $decorateType = ScriptType::P2SH;

    /**
     * @param Key $key
     * @return ScriptAndSignData
     * @throws \BitWasp\Bitcoin\Exceptions\P2shScriptException
     */
    public function convertKey(Key $key)
    {
        $redeemScript = new P2shScript($this->scriptDataFactory->convertKey($key)->getScriptPubKey());
        return new ScriptAndSignData(
            $redeemScript->getOutputScript(),
            (new SignData())
                ->p2sh($redeemScript)
        );
    }
}
