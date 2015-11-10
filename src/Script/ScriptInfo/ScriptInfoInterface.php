<?php

namespace BitWasp\Bitcoin\Script\ScriptInfo;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

interface ScriptInfoInterface
{
    /**
     * @return integer
     */
    public function getRequiredSigCount();

    /**
     * @return integer
     */
    public function getKeyCount();

    /**
     * @param PublicKeyInterface $publicKey
     * @return bool
     */
    public function checkInvolvesKey(PublicKeyInterface $publicKey);

    /**
     * @return PublicKeyInterface[]
     */
    public function getKeys();

    /**
     * @return string
     */
    public function classification();

    /**
     * @param array $signatures
     * @param array $publicKeys
     * @return ScriptInterface
     */
    public function makeScriptSig(array $signatures = [], array $publicKeys = []);
}
