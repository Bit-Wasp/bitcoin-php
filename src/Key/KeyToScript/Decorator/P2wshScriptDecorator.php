<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript\Decorator;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

class P2wshScriptDecorator extends ScriptHashDecorator
{
    /**
     * @var array
     */
    protected $allowedScriptTypes = [
        ScriptType::P2PKH,
        ScriptType::P2PK,
    ];

    /**
     * @var string
     */
    protected $decorateType = ScriptType::P2WSH;

    /**
     * @param KeyInterface $key
     * @return ScriptAndSignData
     * @throws \BitWasp\Bitcoin\Exceptions\WitnessScriptException
     */
    public function convertKey(KeyInterface $key): ScriptAndSignData
    {
        $witnessScript = new WitnessScript($this->scriptDataFactory->convertKey($key)->getScriptPubKey());
        return new ScriptAndSignData(
            $witnessScript->getOutputScript(),
            (new SignData())
                ->p2wsh($witnessScript)
        );
    }
}
