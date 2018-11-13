<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Checksig
{
    /**
     * @var string
     */
    private $scriptType;

    /**
     * @var bool
     */
    private $required = true;

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
     * @param Multisig|PayToPubkeyHash|PayToPubkey $info
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
     * Mark this Checksig operation as not required. Will use OP_0
     * in place of all values (satisfying MINIMALDATA / MINIMALIF)
     *
     * @param bool $setting
     * @return $this
     */
    public function setRequired(bool $setting)
    {
        $this->required = $setting;
        return $this;
    }

    /**
     * Returns whether this opcodes successful completion is
     * necessary for the overall successful operation of the
     * script
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Returns the underlying script info class
     *
     * @return Multisig|PayToPubkey|PayToPubkeyHash
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Return the script type
     * NB: Checksig overloads the various templates, returning 'multisig'
     * even if the opcode was multisigverify. Check the getInfo() result,
     * or isVerify() result, if this is important.
     *
     * @return string
     */
    public function getType(): string
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
    public function getRequiredSigs(): int
    {
        return $this->requiredSigs;
    }

    /**
     * @return bool
     */
    public function isFullySigned(): bool
    {
        if ($this->required) {
            return $this->requiredSigs === count($this->signatures);
        } else {
            return true;
        }
    }

    /**
     * @param int $idx
     * @return bool
     */
    public function hasSignature(int $idx): bool
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
    public function setSignature(int $idx, TransactionSignatureInterface $signature)
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
    public function getSignature(int $idx)
    {
        if (!$this->hasSignature($idx)) {
            return null;
        }

        return $this->signatures[$idx];
    }

    /**
     * @return array
     */
    public function getSignatures(): array
    {
        return $this->signatures;
    }

    /**
     * @param int $idx
     * @return bool
     */
    public function hasKey(int $idx): bool
    {
        return array_key_exists($idx, $this->publicKeys);
    }

    /**
     * @param int $idx
     * @return PublicKeyInterface|null
     */
    public function getKey(int $idx)
    {
        if (!$this->hasKey($idx)) {
            return null;
        }

        return $this->publicKeys[$idx];
    }

    /**
     * @param int $idx
     * @param PublicKeyInterface|null $key
     * @return $this
     */
    public function setKey(int $idx, PublicKeyInterface $key = null)
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
    public function getKeys(): array
    {
        return $this->publicKeys;
    }

    /**
     * @return bool
     */
    public function isVerify(): bool
    {
        return $this->info->isChecksigVerify();
    }

    /**
     * @param TransactionSignatureSerializer $txSigSerializer
     * @param PublicKeySerializerInterface $pubKeySerializer
     * @return BufferInterface[]
     */
    public function serialize(
        TransactionSignatureSerializer $txSigSerializer,
        PublicKeySerializerInterface $pubKeySerializer
    ): array {
        $outputType = $this->getType();
        $result = [];

        if (ScriptType::P2PK === $outputType) {
            if (!$this->required) {
                $result[0] = new Buffer();
            } else {
                if ($this->hasSignature(0)) {
                    $result[0] = $txSigSerializer->serialize($this->getSignature(0));
                }
            }
        } else if (ScriptType::P2PKH === $outputType) {
            if (!$this->required && $this->hasKey(0)) {
                $result[0] = new Buffer();
                $result[1] = $pubKeySerializer->serialize($this->getKey(0));
            } else {
                if ($this->hasSignature(0) && $this->hasKey(0)) {
                    $result[0] = $txSigSerializer->serialize($this->getSignature(0));
                    $result[1] = $pubKeySerializer->serialize($this->getKey(0));
                }
            }
        } else if (ScriptType::MULTISIG === $outputType) {
            if (!$this->required) {
                $result = array_fill(0, 1 + $this->getRequiredSigs(), new Buffer());
            } else {
                $result[] = new Buffer();
                for ($i = 0, $nPubKeys = count($this->getKeys()); $i < $nPubKeys; $i++) {
                    if ($this->hasSignature($i)) {
                        $result[] = $txSigSerializer->serialize($this->getSignature($i));
                    }
                }
            }
        } else {
            throw new \RuntimeException('Parameter 0 for serializeSolution was a non-standard input type');
        }

        return $result;
    }
}
