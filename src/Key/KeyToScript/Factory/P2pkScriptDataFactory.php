<?php

declare(strict_types=1);

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
    public function getScriptType(): string
    {
        return ScriptType::P2PK;
    }

    /**
     * @param PublicKeyInterface $publicKeys
     * @return ScriptAndSignData
     */
    protected function convertKeyToScriptData(PublicKeyInterface ...$publicKeys): ScriptAndSignData
    {
        if (count($publicKeys) !== 1) {
            throw new \InvalidArgumentException("Invalid number of keys");
        }
        return new ScriptAndSignData(
            ScriptFactory::scriptPubKey()->p2pk($publicKeys[0]),
            new SignData()
        );
    }
}
