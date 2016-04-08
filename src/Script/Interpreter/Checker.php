<?php

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Exceptions\ScriptRuntimeException;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;

class Checker
{
    /**
     * @var EcAdapterInterface
     */
    private $adapter;

    /**
     * @var TransactionInterface
     */
    private $transaction;

    /**
     * @var int
     */
    private $nInput;

    /**
     * @var int|string
     */
    private $amount;

    /**
     * Checker constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $transaction
     * @param int $nInput
     * @param int|string $amount
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $transaction, $nInput, $amount)
    {
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
        $s = $signature->slice(6 + $nLenR, $nLenS)->getInt();

        return $this->adapter->validateSignatureElement($s, true);
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
        $nHashType = ord(substr($binary, -1)) & (~(SigHashInterface::ANYONECANPAY));

        $math = $this->adapter->getMath();
        return ! ($math->cmp($nHashType, SigHashInterface::ALL) < 0 || $math->cmp($nHashType, SigHashInterface::SINGLE) > 0);
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

        if (($flags & (Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_LOW_S | Interpreter::VERIFY_STRICTENC)) != 0 && !$this->isValidSignatureEncoding($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_DERSIG, 'Signature with incorrect encoding');
        } else if (($flags & Interpreter::VERIFY_LOW_S) != 0 && !$this->isLowDerSignature($signature)) {
            throw new ScriptRuntimeException(Interpreter::VERIFY_LOW_S, 'Signature s element was not low');
        } else if (($flags & Interpreter::VERIFY_STRICTENC) != 0 && !$this->isDefinedHashtypeSignature($signature)) {
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
        if (($flags & Interpreter::VERIFY_STRICTENC) != 0 && !PublicKey::isCompressedOrUncompressed($publicKey)) {
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
    public function checkSig(ScriptInterface $script, BufferInterface $sigBuf, BufferInterface $keyBuf, $sigVersion, $flags)
    {
        $this
            ->checkSignatureEncoding($sigBuf, $flags)
            ->checkPublicKeyEncoding($keyBuf, $flags);

        try {
            $txSignature = TransactionSignatureFactory::fromHex($sigBuf->getHex());
            $publicKey = PublicKeyFactory::fromHex($keyBuf->getHex());

            if ($sigVersion === 1) {
                $hasher = new V1Hasher($this->transaction, $this->amount);
            } else {
                $hasher = new Hasher($this->transaction);
            }

            $hash = $hasher->calculate($script, $this->nInput, $txSignature->getHashType());
            return $this->adapter->verify($hash, $publicKey, $txSignature->getSignature());
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
        $math = $this->adapter->getMath();
        $nTime = $lockTime->getInt();
        if (($math->cmp($txLockTime, $nThreshold) < 0 && $math->cmp($nTime, $nThreshold) < 0) ||
            ($math->cmp($txLockTime, $nThreshold) >= 0 && $math->cmp($nTime, $nThreshold) >= 0)
        ) {
            return false;
        }

        return $math->cmp($nTime, $txLockTime) >= 0;
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
        $math = $this->adapter->getMath();
        $txSequence = $this->transaction->getInput($this->nInput)->getSequence();
        if ($this->transaction->getVersion() < 2) {
            return false;
        }

        if ($math->cmp($math->bitwiseAnd($txSequence, TransactionInputInterface::SEQUENCE_LOCKTIME_DISABLE_FLAG), 0) !== 0) {
            return 0;
        }

        $mask = $math->bitwiseOr(TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG, TransactionInputInterface::SEQUENCE_LOCKTIME_MASK);
        return $this->verifyLockTime(
            $math->bitwiseAnd($txSequence, $mask),
            TransactionInputInterface::SEQUENCE_LOCKTIME_TYPE_FLAG,
            Number::int($math->bitwiseAnd($sequence->getInt(), $mask))
        );
    }
}
