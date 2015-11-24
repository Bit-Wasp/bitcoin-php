<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;

class P2shScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private $scriptPubKey;

    /**
     * P2shScriptFactory constructor.
     * @param OutputScriptFactory $scriptPubKey
     */
    public function __construct(OutputScriptFactory $scriptPubKey)
    {
        $this->scriptPubKey = $scriptPubKey;
    }

    /**
     * Create a multisig redeemScript and outputScript
     *
     * @param $m
     * @param array $keys
     * @param bool|true $sort
     * @return P2shScript
     */
    public function multisig($m, array $keys, $sort = true)
    {
        return new P2shScript($this->scriptPubKey->multisig($m, $keys, $sort));
    }

    /**
     * Create a Pay to pubkey redeemScript and outputScript
     * @param PublicKeyInterface  $publicKey
     * @return P2shScript
     */
    public function payToPubKey(PublicKeyInterface $publicKey)
    {
        return new P2shScript($this->scriptPubKey->payToPubKey($publicKey));
    }

    /**
     * Create a Pay to pubkey-hash redeemScript and outputScript
     * @param PublicKeyInterface  $publicKey
     * @return P2shScript
     */
    public function payToPubKeyHash(PublicKeyInterface $publicKey)
    {
        return new P2shScript($this->scriptPubKey->payToPubKeyHash($publicKey));
    }
}
