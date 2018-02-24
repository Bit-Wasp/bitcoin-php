<?php

declare(strict_types=1);

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
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;

abstract class CheckerBase
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
     * @var int
     */
    protected $amount;

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
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $transaction, int $nInput, int $amount, TransactionSignatureSerializer $sigSerializer = null, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        $this->sigSerializer = $sigSerializer ?: new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter));
        $this->pubKeySerializer = $pubKeySerializer ?: EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $this->adapter = $ecAdapter;
        $this->transaction = $transaction;
        $this->nInput = $nInput;
        $this->amount = $amount;
    }

    /**
     * @param ScriptInterface $script
     * @param int $hashType
     * @param int $sigVersion
     * @return BufferInterface
     */
    abstract public function getSigHash(ScriptInterface $script, int $hashType, int $sigVersion): BufferInterface;

    /**
     * @param BufferInterface $signature
     * @return bool
     */
    public function isValidSignatureEncoding(BufferInterface $signature): bool
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
    public function isLowDerSignature(BufferInterface $signature): bool
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
    public function isDefinedHashtype(int $hashType): bool
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
    public function isDefinedHashtypeSignature(BufferInterface $signature): bool
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
    public function checkSignatureEncoding(BufferInterface $signature, int $flags)
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
    public function checkPublicKeyEncoding(BufferInterface $publicKey, int $flags)
    {
        if (($flags & Interpreter::VERIFY_STRICTENC) !== 0 && !PublicKey::isCompressedOrUncompressed($publicKey)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_STRICTENC, 'Public key with incorrect encoding');
        }

        return $this;
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
    public function checkSig(ScriptInterface $script, BufferInterface $sigBuf, BufferInterface $keyBuf, int $sigVersion, int $flags)
    {
        $this
            ->checkSignatureEncoding($sigBuf, $flags)
            ->checkPublicKeyEncoding($keyBuf, $flags);

        try {
            $cacheCheck = "{$script->getBinary()}{$sigVersion}{$keyBuf->getBinary()}{$sigBuf->getBinary()}";
            if (!isset($this->sigCache[$cacheCheck])) {
                $txSignature = $this->sigSerializer->parse($sigBuf);
                $publicKey = $this->pubKeySerializer->parse($keyBuf);

                $hash = $this->getSigHash($script, $txSignature->getHashType(), $sigVersion);
                $result = $this->sigCache[$cacheCheck] = $publicKey->verify($hash, $txSignature->getSignature());
            } else {
                $result = $this->sigCache[$cacheCheck];
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $scriptLockTime
     * @return bool
     */
    public function checkLockTime(\BitWasp\Bitcoin\Script\Interpreter\Number $scriptLockTime): bool
    {
        $input = $this->transaction->getInput($this->nInput);
        $nLockTime = $scriptLockTime->getInt();
        $txLockTime = $this->transaction->getLockTime();

        if (!(($txLockTime < Locktime::BLOCK_MAX && $nLockTime < Locktime::BLOCK_MAX) ||
            ($txLockTime >= Locktime::BLOCK_MAX && $nLockTime >= Locktime::BLOCK_MAX))
        ) {
            return false;
        }

        if ($nLockTime > $txLockTime) {
            return false;
        }

        if ($input->isFinal()) {
            return false;
        }

        return true;
    }

    /**
     * @param \BitWasp\Bitcoin\Script\Interpreter\Number $sequence
     * @return bool
     */
    public function checkSequence(\BitWasp\Bitcoin\Script\Interpreter\Number $sequence): bool
    {
        $txSequence = $this->transaction->getInput($this->nInput)->getSequence();
        if ($this->transaction->getVersion() < 2) {
            return false;
        }

        if (($txSequence & TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG) !== 0) {
            return false;
        }

        $mask = TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG | TransactionInputInterface::SEQUENCE_LOCKTIME_MASK;

        $txToSequenceMasked = $txSequence & $mask;
        $nSequenceMasked = $sequence->getInt() & $mask;
        if (!(($txToSequenceMasked < TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG && $nSequenceMasked < TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG) ||
            ($txToSequenceMasked >= TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG && $nSequenceMasked >= TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG))
        ) {
            return false;
        }

        if ($nSequenceMasked > $txToSequenceMasked) {
            return false;
        }

        return true;
    }
}
