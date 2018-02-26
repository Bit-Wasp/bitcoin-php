<?php

namespace BitWasp\Bitcoin\Key\KeyToScript\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Transaction\Factory\SignData;

class P2pkScriptDataFactory extends KeyToScriptDataFactory
{
    /**
     * @return string
     */
    public function getScriptType()
    {
        return ScriptType::P2PK;
    }

    /**
     * @param PublicKeyInterface $publicKey
     * @return ScriptAndSignData
     */
    protected function convertKeyToScriptData(PublicKeyInterface $publicKey)
    {
        return new ScriptAndSignData(
            ScriptFactory::scriptPubKey()->p2pk($publicKey),
            new SignData()
        );
    }
}
