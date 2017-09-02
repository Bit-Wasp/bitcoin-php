<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Buffertools\BufferInterface;

class Checksig
{
    /**
     * @var string
     */
    private $scriptType;

    /**
     * @var PayToPubkeyHash|PayToPubkey|Multisig
     */
    private $info;

    /**
     * @var int
     */
    protected $requiredSigs;

    /**
     * @var int
     */
    protected $keyCount;

    /**
     * @var TransactionSignatureInterface[]
     */
    protected $signatures = [];

    /**
     * @var PublicKeyInterface[]|null[]
     */
    protected $publicKeys = [];

    /**
     * Checksig constructor.
     * @param $info
     */
    public function __construct($info)
    {
        if (!is_object($info)) {
            throw new \RuntimeException("First value to checksig must be an object");
        }

        $infoClass = get_class($info);
        switch ($infoClass) {
            case PayToPubkey::class:
                /** @var PayToPubkey $info */
                $this->scriptType = $info->getType();
                $this->requiredSigs = $info->getRequiredSigCount();
                $this->keyCount = 1;
                break;
            case PayToPubkeyHash::class:
                /** @var PayToPubkeyHash $info */
                $this->scriptType = ScriptType::P2PKH;
                $this->requiredSigs = $info->getRequiredSigCount();
                $this->keyCount = 1;
                break;
            case Multisig::class:
                /** @var Multisig $info */
                $this->scriptType = ScriptType::MULTISIG;
                $this->requiredSigs = $info->getRequiredSigCount();
                $this->keyCount = $info->getKeyCount();
                break;
            default:
                throw new \RuntimeException("Unsupported class passed to Checksig");
        }

        $this->info = $info;
    }

    /**
     * @return Multisig|PayToPubkey|PayToPubkeyHash
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->scriptType;
    }

    /**
     * @return array|BufferInterface|BufferInterface[]
     */
    public function getSolution()
    {
        if ($this->info instanceof Multisig) {
            return $this->info->getKeyBuffers();
        } else if ($this->info instanceof PayToPubkey) {
            return $this->info->getKeyBuffer();
        } else {
            return $this->info->getPubKeyHash();
        }
    }

    /**
     * @return int
     */
    public function getRequiredSigs()
    {
        return $this->requiredSigs;
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        return $this->requiredSigs !== 0 && $this->requiredSigs === count($this->signatures);
    }

    /**
     * @param int $idx
     * @return bool
     */
    public function hasSignature($idx)
    {
        if ($idx > $this->requiredSigs) {
            throw new \RuntimeException("Out of range signature queried");
        }

        return array_key_exists($idx, $this->signatures);
    }

    /**
     * @param int $idx
     * @param TransactionSignatureInterface $signature
     * @return $this
     */
    public function setSignature($idx, TransactionSignatureInterface $signature)
    {
        if ($idx < 0 || $idx > $this->keyCount) {
            throw new \RuntimeException("Out of range signature for operation");
        }

        $this->signatures[$idx] = $signature;
        return $this;
    }

    /**
     * @param int $idx
     * @return TransactionSignatureInterface|null
     */
    public function getSignature($idx)
    {
        if (!$this->hasSignature($idx)) {
            return null;
        }

        return $this->signatures[$idx];
    }

    /**
     * @return array
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * @param int $idx
     * @return bool
     */
    public function hasKey($idx)
    {
        return array_key_exists($idx, $this->publicKeys);
    }

    /**
     * @param int $idx
     * @return PublicKeyInterface|null
     */
    public function getKey($idx)
    {
        if (!$this->hasKey($idx)) {
            return null;
        }

        return $this->publicKeys[$idx];
    }

    /**
     * @param $idx
     * @param PublicKeyInterface|null $key
     * @return $this
     */
    public function setKey($idx, $key)
    {
        if ($idx < 0 || $idx > $this->keyCount) {
            throw new \RuntimeException("Out of range index for public key");
        }

        $this->publicKeys[$idx] = $key;
        return $this;
    }

    /**
     * @return PublicKeyInterface[]
     */
    public function getKeys()
    {
        return $this->publicKeys;
    }
}
