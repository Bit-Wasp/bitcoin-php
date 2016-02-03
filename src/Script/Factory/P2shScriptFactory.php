<?php

namespace BitWasp\Bitcoin\Script\Factory;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;

class P2shScriptFactory
{
    /**
     * @var OutputScriptFactory
     */
    private $scriptPubKey;

    /**
     * @var Opcodes
     */
    private $opcodes;

    /**
     * P2shScriptFactory constructor.
     * @param OutputScriptFactory $scriptPubKey
     * @param Opcodes $opcodes
     */
    public function __construct(OutputScriptFactory $scriptPubKey, Opcodes $opcodes)
    {
        $this->scriptPubKey = $scriptPubKey;
        $this->opcodes = $opcodes;
    }

    /**
     * Parse a ScriptInterface into a RedeemScript
     *
     * @param ScriptInterface $script
     * @return P2shScript
     */
    public function parse(ScriptInterface $script)
    {
        return new P2shScript($script, $this->opcodes);
    }

    /**
     * Create a multisig P2SH Script
     *
     * @param int $m
     * @param array $keys
     * @param bool|true $sort
     * @return P2shScript
     */
    public function multisig($m, array $keys, $sort = true)
    {
        return new P2shScript($this->scriptPubKey->multisig($m, $keys, $sort), $this->opcodes);
    }

    /**
     * Create a pay-to-pubkey P2SH Script
     *
     * @param PublicKeyInterface $publicKey
     * @return P2shScript
     */
    public function payToPubKey(PublicKeyInterface $publicKey)
    {
        return new P2shScript($this->scriptPubKey->payToPubKey($publicKey), $this->opcodes);
    }

    /**
     * Create a pay-to-pubkey-hash P2SH Script
     *
     * @param PublicKeyInterface $publicKey
     * @return P2shScript
     */
    public function payToPubKeyHash(PublicKeyInterface $publicKey)
    {
        return new P2shScript($this->scriptPubKey->payToPubKeyHash($publicKey), $this->opcodes);
    }
}
