<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\SignatureHash;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class TxSigHashSerializer
{
    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * @var ScriptInterface
     */
    private $scriptCode;

    /**
     * @var int
     */
    private $nIn;

    /**
     * @var \BitWasp\Buffertools\Types\VarInt
     */
    private $varint;

    /**
     * @var \BitWasp\Buffertools\Types\ByteString
     */
    private $bs32le;

    /**
     * @var bool
     */
    private $anyoneCanPay = false;

    /**
     * @var bool
     */
    private $hashSingle = false;

    /**
     * @var bool
     */
    private $hashNone = false;

    /**
     * @param TransactionInterface $tx
     * @param ScriptInterface $scriptCode
     * @param int $nIn
     * @param int $nHashTypeIn
     */
    public function __construct(TransactionInterface $tx, ScriptInterface $scriptCode, int $nIn, int $nHashTypeIn)
    {
        $this->tx = $tx;
        $this->scriptCode = $scriptCode;
        $this->nIn = $nIn;
        $this->anyoneCanPay = !!($nHashTypeIn & SigHash::ANYONECANPAY);

        $bits = $nHashTypeIn & 0x1f;
        $this->hashSingle = $bits === SigHash::SINGLE;
        $this->hashNone = $bits === SigHash::NONE;

        $this->varint = Types::varint();
        $this->bs32le = Types::bytestringle(32);
    }

    /**
     * @return string
     */
    private function serializeScript(): string
    {
        $script = $this->scriptCode;
        $parser = $script->getScriptParser();

        $nSize = $script->getBuffer()->getSize();
        $nSeparators = 0;
        foreach ($parser as $operation) {
            if ($operation->getOp() === Opcodes::OP_CODESEPARATOR) {
                $nSeparators++;
            }
        }

        $newSize = $nSize - $nSeparators;
        $out = $this->varint->write($newSize);
        $begin = $position = 0;

        foreach ($parser as $operation) {
            if ($operation->getOp() === Opcodes::OP_CODESEPARATOR) {
                $position = $parser->getPosition();
                $out .= $parser->slice($position)->getBinary();
                $begin = $position;
            }
        }

        if ($begin !== $newSize) {
            $out .= $parser->slice($begin, $newSize - $begin)->getBinary();
        }

        return $out;
    }

    /**
     * @param int $nInput
     * @return string
     */
    public function serializeInput(int $nInput): string
    {
        if ($this->anyoneCanPay) {
            $nInput = $this->nIn;
        }

        $txIn = $this->tx->getInput($nInput);
        $outpoint = $txIn->getOutPoint();
        $out = $this->bs32le->write($outpoint->getTxId()) . pack('V', $outpoint->getVout());

        if ($nInput !== $this->nIn) {
            // script length is zero
            $out .= "\x00";
        } else {
            $out .= $this->serializeScript();
        }

        if ($nInput !== $this->nIn && ($this->hashSingle || $this->hashNone)) {
            $out .= pack('V', 0);
        } else {
            $out .= pack('V', $txIn->getSequence());
        }

        return $out;
    }

    /**
     * @param int $nOutput
     * @return string
     */
    public function serializeOutput(int $nOutput): string
    {
        if ($this->hashSingle && $nOutput != $this->nIn) {
            $out = pack('P', -1) . "\x00";
        } else {
            $txOut = $this->tx->getOutput($nOutput);
            $scriptBuf = $txOut->getScript()->getBuffer();
            $out = pack('P', $txOut->getValue()) . $this->varint->write($scriptBuf->getSize()) . $scriptBuf->getBinary();
        }

        return $out;
    }

    /**
     * @return string
     */
    public function serializeTransaction(): string
    {
        $data = Types::int32le()->write($this->tx->getVersion());

        $nInputs = $this->anyoneCanPay ? 1 : count($this->tx->getInputs());
        $data .= $this->varint->write($nInputs);
        for ($i = 0; $i < $nInputs; $i++) {
            $data .= $this->serializeInput($i);
        }

        $nOutputs = $this->hashNone ? 0 : ($this->hashSingle ? $this->nIn + 1 : count($this->tx->getOutputs()));
        $data .= $this->varint->write($nOutputs);
        for ($i = 0; $i < $nOutputs; $i++) {
            $data .= $this->serializeOutput($i);
        }

        $data .= pack('V', $this->tx->getLockTime());
        return $data;
    }
}
