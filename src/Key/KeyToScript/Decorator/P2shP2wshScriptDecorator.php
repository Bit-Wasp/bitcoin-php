<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\KeyToScript\Decorator;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

class P2shP2wshScriptDecorator extends ScriptHashDecorator
{
    /**
     * @var string[]
     */
    protected $allowedScriptTypes = [
        ScriptType::MULTISIG,
        ScriptType::P2PKH,
        ScriptType::P2PK,
    ];

    /**
     * @var string
     */
    protected $decorateType = "scripthash|witness_v0_scripthash";

    /**
     * @param KeyInterface ...$keys
     * @return ScriptAndSignData
     * @throws \BitWasp\Bitcoin\Exceptions\P2shScriptException
     * @throws \BitWasp\Bitcoin\Exceptions\WitnessScriptException
     */
    public function convertKey(KeyInterface ...$keys): ScriptAndSignData
    {
        $witnessScript = new WitnessScript($this->scriptDataFactory->convertKey(...$keys)->getScriptPubKey());
        $redeemScript = new P2shScript($witnessScript);
        return new ScriptAndSignData(
            $redeemScript->getOutputScript(),
            (new SignData())
                ->p2sh($redeemScript)
                ->p2wsh($witnessScript)
        );
    }
}
