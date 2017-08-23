<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class Checker
{
    /**
     * @var EcAdapterInterface
     */
    protected $adapter;

    /**
     * @var TransactionInterface
     */
    protected $transaction;

    /**
     * @var int
     */
    protected $nInput;

    /**
     * @var int|string
     */
    protected $amount;

    /**
     * @var Hasher
     */
    protected $hasherV0;

    /**
     * @var array
     */
    protected $sigHashCache = [];

    /**
     * @var array
     */
    protected $sigCache = [];

    /**
     * @var TransactionSignatureSerializer
     */
    private $sigSerializer;

    /**
     * @var PublicKeySerializerInterface
     */
    private $pubKeySerializer;

    /**
     * @var int
     */
    protected $sigHashOptionalBits = SigHash::ANYONECANPAY;

    /**
     * Checker constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $transaction
     * @param int $nInput
     * @param int $amount
     * @param TransactionSignatureSerializer|null $sigSerializer
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $transaction, $nInput, $amount, TransactionSignatureSerializer $sigSerializer = null, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        $this->sigSerializer = $sigSerializer ?: new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter));
        $this->pubKeySerializer = $pubKeySerializer ?: EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $this->adapter = $ecAdapter;
        $this->transaction = $transaction;
        $this->nInput = $nInput;
        $this->amount = $amount;
    }

    /**
     * @param BufferInterface $signature
     * @return bool
     */
    public function isValidSignatureEncoding(BufferInterface $signature)
    {
        try {
            TransactionSignature::isDERSignature($signature);
            return true;
        } catch (SignatureNotCanonical $e) {
            /* In any case, we will return false outside this block */
        }

        return false;
    }

    /**
     * @param BufferInterface $signature
     * @return bool
     * @throws ScriptRuntimeException
     * @throws \Exception
     */
    public function isLowDerSignature(BufferInterface $signature)
    {
        if (!$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_DERSIG, 'Signature with incorrect encoding');
        }

        $binary = $signature->getBinary();
        $nLenR = ord($binary[3]);
        $nLenS = ord($binary[5 + $nLenR]);
        $s = $signature->slice(6 + $nLenR, $nLenS)->getGmp();

        return $this->adapter->validateSignatureElement($s, true);
    }

    /**
     * @param int $hashType
     * @return bool
     */
    public function isDefinedHashtype($hashType)
    {
        $nHashType = $hashType & (~($this->sigHashOptionalBits));

        return !(($nHashType < SigHash::ALL) || ($nHashType > SigHash::SINGLE));
    }

    /**
     * Determine whether the sighash byte appended to the signature encodes
     * a valid sighash type.
     *
     * @param BufferInterface $signature
     * @return bool
     */
    public function isDefinedHashtypeSignature(BufferInterface $signature)
    {
        if ($signature->getSize() === 0) {
            return false;
        }

        $binary = $signature->getBinary();
        return $this->isDefinedHashtype(ord(substr($binary, -1)));
    }

    /**
     * @param BufferInterface $signature
     * @param int $flags
     * @return $this
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
     */
    public function checkSignatureEncoding(BufferInterface $signature, $flags)
    {
        if ($signature->getSize() === 0) {
            return $this;
        }

        if (($flags & (Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_LOW_S | Interpreter::VERIFY_STRICTENC)) !== 0 && !$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_DERSIG, 'Signature with incorrect encoding');
        } else if (($flags & Interpreter::VERIFY_LOW_S) !== 0 && !$this->isLowDerSignature($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_LOW_S, 'Signature s element was not low');
        } else if (($flags & Interpreter::VERIFY_STRICTENC) !== 0 && !$this->isDefinedHashtypeSignature($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_STRICTENC, 'Signature with invalid hashtype');
        }

        return $this;
    }

    /**
     * @param BufferInterface $publicKey
     * @param int $flags
     * @return $this
     * @throws \Exception
     */
    public function checkPublicKeyEncoding(BufferInterface $publicKey, $flags)
    {
        if (($flags & Interpreter::VERIFY_STRICTENC) !== 0 && !PublicKey::isCompressedOrUncompressed($publicKey)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_STRICTENC, 'Public key with incorrect encoding');
        }

        return $this;
    }

    /**
     * @param ScriptInterface $script
     * @param int $sigHashType
     * @param int $sigVersion
     * @return BufferInterface
     */
    public function getSigHash(ScriptInterface $script, $sigHashType, $sigVersion)
    {
        $cacheCheck = $sigVersion . $sigHashType . $script->getBuffer()->getBinary();
        if (!isset($this->sigHashCache[$cacheCheck])) {
            if (SigHash::V1 === $sigVersion) {
                $hasher = new V1Hasher($this->transaction, $this->amount);
            } else {
                if ($this->hasherV0) {
                    $hasher = $this->hasherV0;
                } else {
                    $hasher = $this->hasherV0 = new Hasher($this->transaction, new TransactionSerializer());
                }
            }

            $hash = $hasher->calculate($script, $this->nInput, $sigHashType);
            $this->sigHashCache[$cacheCheck] = $hash->getBinary();
        } else {
            $hash = new Buffer($this->sigHashCache[$cacheCheck], 32, $this->adapter->getMath());
        }

        return $hash;
    }

    /**
     * @param ScriptInterface $script
     * @param BufferInterface $sigBuf
     * @param BufferInterface $keyBuf
     * @param int $sigVersion
     * @param int $flags
     * @return bool
     * @throws ScriptRuntimeException
     */
    public function checkSig(ScriptInterface $script, BufferInterface $sigBuf, BufferInterface $keyBuf, $sigVersion, $flags)
    {
        $this
            ->checkSignatureEncoding($sigBuf, $flags)
            ->checkPublicKeyEncoding($keyBuf, $flags);

        try {
            $cacheCheck = $flags . $sigVersion . $keyBuf->getBinary() . $sigBuf->getBinary();
            if (!isset($this->sigCache[$cacheCheck])) {
                $txSignature = $this->sigSerializer->parse($sigBuf);
                $publicKey = $this->pubKeySerializer->parse($keyBuf);

                $hash = $this->getSigHash($script, $txSignature->getHashType(), $sigVersion);
                $result = $this->sigCache[$cacheCheck] = $this->adapter->verify($hash, $publicKey, $txSignature->getSignature());
            } else {
                $result = $this->sigCache[$cacheCheck];
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param int $txLockTime
     * @param int $nThreshold
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime
     * @return bool
     */
    private function verifyLockTime($txLockTime, $nThreshold, \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime)
    {
        $nTime = $lockTime->getInt();
        if (($txLockTime < $nThreshold && $nTime < $nThreshold) ||
            ($txLockTime >= $nThreshold && $nTime >= $nThreshold)
        ) {
            return false;
        }

        return $nTime >= $txLockTime;
    }

    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $lockTime
     * @return bool
     */
    public function checkLockTime(\BitWasp\Bitcoin\Script\Interpreter\Number $lockTime)
    {
        if ($this->transaction->getInput($this->nInput)->isFinal()) {
            return false;
        }

        return $this->verifyLockTime($this->transaction->getLockTime(), Locktime::BLOCK_MAX, $lockTime);
    }


    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $sequence
     * @return bool
     */
    public function checkSequence(\BitWasp\Bitcoin\Script\Interpreter\Number $sequence)
    {
        $txSequence = $this->transaction->getInput($this->nInput)->getSequence();
        if ($this->transaction->getVersion() < 2) {
            return false;
        }

        if (($txSequence & TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG) !== 0) {
            return true;
        }

        $mask = TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG | TransactionInputInterface::SEQUENCE_LOCKTIME_MASK;
        return $this->verifyLockTime(
            $txSequence & $mask,
            TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG,
            Number::int($sequence->getInt() & $mask)
        );
    }
}
