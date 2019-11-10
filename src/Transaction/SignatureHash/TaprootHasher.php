<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Interpreter\ExecutionContext;
use BitWasp\Bitcoin\Script\PrecomputedData;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializerInterface;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;

class TaprootHasher extends SigHash
{
    /**
     * @var TransactionInterface
     */
    protected $tx;

    /**
     * @var int
     */
    protected $sigVersion;

    /**
     * @var TransactionOutputSerializer
     */
    protected $outputSerializer;

    /**
     * @var OutPointSerializerInterface
     */
    protected $outpointSerializer;

    /**
     * @var ExecutionContext
     */
    protected $execContext;

    /**
     * @var PrecomputedData
     */
    protected $precomputedData;

    const TAPROOT_ANNEX_BYTE = 0x50;

    /**
     * V1Hasher constructor.
     * @param TransactionInterface $transaction
     * @param int $sigVersion
     * @param PrecomputedData $precomputedData
     * @param ExecutionContext $execContext
     * @param OutPointSerializerInterface $outpointSerializer
     * @param TransactionOutputSerializer|null $outputSerializer
     */
    public function __construct(
        TransactionInterface $transaction,
        int $sigVersion,
        PrecomputedData $precomputedData,
        ExecutionContext $execContext,
        OutPointSerializerInterface $outpointSerializer = null,
        TransactionOutputSerializer $outputSerializer = null
    ) {
        $this->sigVersion = $sigVersion;
        $this->execContext = $execContext;
        $this->precomputedData = $precomputedData;
        $this->outputSerializer = $outputSerializer ?: new TransactionOutputSerializer();
        $this->outpointSerializer = $outpointSerializer ?: new OutPointSerializer();
        if (!($precomputedData->isReady() && $precomputedData->haveSpentOutputs())) {
            throw new \RuntimeException("precomputed data not ready");
        }
        if (!$execContext->isAnnexCheckDone()) {
            throw new \RuntimeException("annex check must be already complete");
        }
        parent::__construct($transaction);
    }

    /**
     * Calculate the hash of the current transaction, when you are looking to
     * spend $txOut, and are signing $inputToSign. The SigHashType defaults to
     * SIGHASH_ALL
     *
     * Note: this function doesn't use txOutScript, as we have access to it via
     * spentOutputs.
     *
     * @param ScriptInterface $txOutScript
     * @param int $inputToSign
     * @param int $sighashType
     * @return BufferInterface
     * @throws \Exception
     */
    public function calculate(
        ScriptInterface $txOutScript,
        int $inputToSign,
        int $sighashType = SigHash::ALL
    ): BufferInterface {
        if (($sighashType > 3) && ($sighashType < 0x81 || $sighashType > 0x83)) {
            throw new \RuntimeException("invalid hash type");
        }
        $epoch = 0;
        $input = $this->tx->getInput($inputToSign);

        $ss = '';
        $ss .= pack("C", $epoch);
        $ss .= pack('CVV', $sighashType, $this->tx->getVersion(), $this->tx->getLockTime());

        $inputType = $sighashType & SigHash::TAPINPUTMASK;
        $outputType = $sighashType & SigHash::TAPOUTPUTMASK;

        if ($inputType === SigHash::TAPDEFAULT) {
            $ss .= $this->precomputedData->getPrevoutsSha256()->getBinary();
            $ss .= $this->precomputedData->getSpentAmountsSha256()->getBinary();
            $ss .= $this->precomputedData->getSequencesSha256()->getBinary();
        }
        if ($outputType === SigHash::TAPDEFAULT || $outputType === SigHash::ALL) {
            $ss .= $this->precomputedData->getOutputsSha256()->getBinary();
        }

        $scriptPubKey = $this->precomputedData->getSpentOutputs()[$inputToSign]->getScript()->getBuffer();
        $spendType = 0;
        if ($this->execContext->hasAnnex()) {
            $spendType |= 1;
        }
        if ($this->sigVersion === SigHash::TAPSCRIPT) {
            $spendType |= 2;
        }

        $ss .= pack('C', $spendType);
        $ss .= Buffertools::numToVarIntBin($scriptPubKey->getSize()) . $scriptPubKey->getBinary();

        if ($inputType === SigHash::ANYONECANPAY) {
            $ss .= $this->outpointSerializer->serialize($input->getOutPoint())->getBinary();
            $ss .= pack('P', $this->precomputedData->getSpentOutputs()[$inputToSign]->getValue());
            $ss .= pack('V', $input->getSequence());
        } else {
            $ss .= pack('V', $inputToSign);
        }
        if ($this->execContext->hasAnnex()) {
            $ss .= $this->execContext->getAnnexHash()->getBinary();
        }

        if ($outputType == SigHash::SINGLE) {
            $outputs = $this->tx->getOutputs();
            if ($inputToSign >= count($outputs)) {
                throw new \RuntimeException("sighash single input > #outputs");
            }
            $ss .= Hash::sha256($this->outputSerializer->serialize($outputs[$inputToSign]))->getBinary();
        }

        if ($this->sigVersion == SigHash::TAPSCRIPT) {
            assert($this->execContext->hasTapLeaf());
            $ss .= $this->execContext->getTapLeafHash()->getBinary();
            $ss .= "\x00"; // key version
            $ss .= pack("V", $this->execContext->getCodeSeparatorPosition());
        }

        return Hash::taggedSha256('TapSighash', new Buffer($ss));
    }
}
