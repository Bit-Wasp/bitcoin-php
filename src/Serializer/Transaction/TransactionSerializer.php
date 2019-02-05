<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Serializer\ExtraPayload\ExtraPayloadSerializer;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class TransactionSerializer implements TransactionSerializerInterface
{
    const NO_WITNESS = 1;

    /**
     * @var \BitWasp\Buffertools\Types\Int16
     */
    protected $int16le;

    /**
     * @var \BitWasp\Buffertools\Types\Uint16
     */
    protected $uint16le;

    /**
     * @var \BitWasp\Buffertools\Types\Int32
     */
    protected $int32le;

    /**
     * @var \BitWasp\Buffertools\Types\Uint32
     */
    protected $uint32le;

    /**
     * @var \BitWasp\Buffertools\Types\VarInt
     */
    protected $varint;

    /**
     * @var TransactionInputSerializer
     */
    protected $inputSerializer;

    /**
     * @var TransactionOutputSerializer
     */
    protected $outputSerializer;

    /**
     * @var ScriptWitnessSerializer
     */
    protected $witnessSerializer;

    /**
     * @var ExtraPayloadSerializer
     */
    protected $payloadSerializer;

    public function __construct(TransactionInputSerializer $inputSerializer = null, TransactionOutputSerializer $outputSerializer = null, ScriptWitnessSerializer $witnessSerializer = null, ExtraPayloadSerializer $payloadSerializer = null)
    {
        $this->int16le = Types::int16le();
        $this->uint16le = Types::uint16le();
        $this->int32le = Types::int32le();
        $this->uint32le = Types::uint32le();
        $this->varint = Types::varint();

        if ($inputSerializer === null || $outputSerializer === null) {
            $opcodes = new Opcodes();
            if (!$inputSerializer) {
                $inputSerializer = new TransactionInputSerializer(new OutPointSerializer(), $opcodes);
            }
            if (!$outputSerializer) {
                $outputSerializer = new TransactionOutputSerializer($opcodes);
            }
        }

        if (!$witnessSerializer) {
            $witnessSerializer = new ScriptWitnessSerializer();
        }

        if (!$payloadSerializer) {
            $payloadSerializer = new ExtraPayloadSerializer();
        }

        $this->inputSerializer = $inputSerializer;
        $this->outputSerializer = $outputSerializer;
        $this->witnessSerializer = $witnessSerializer;
        $this->payloadSerializer = $payloadSerializer;
    }

    /**
     * @param Parser $parser
     * @return TransactionInterface
     */
    public function fromParser(Parser $parser): TransactionInterface
    {
        $version = (int) $this->uint16le->read($parser);
        $type = (int) $this->uint16le->read($parser);

        $vin = [];
        $vinCount = $this->varint->read($parser);
        for ($i = 0; $i < $vinCount; $i++) {
            $vin[] = $this->inputSerializer->fromParser($parser);
        }

        $vout = [];
        $flags = 0;
        if (count($vin) === 0) {
            $flags = (int) $this->varint->read($parser);
            if ($flags !== 0) {
                $vinCount = $this->varint->read($parser);
                for ($i = 0; $i < $vinCount; $i++) {
                    $vin[] = $this->inputSerializer->fromParser($parser);
                }

                $voutCount = $this->varint->read($parser);
                for ($i = 0; $i < $voutCount; $i++) {
                    $vout[] = $this->outputSerializer->fromParser($parser);
                }
            }
        } else {
            $voutCount = $this->varint->read($parser);
            for ($i = 0; $i < $voutCount; $i++) {
                $vout[] = $this->outputSerializer->fromParser($parser);
            }
        }

        $vwit = [];
        if (($flags & 1)) {
            $flags ^= 1;
            $witCount = count($vin);
            for ($i = 0; $i < $witCount; $i++) {
                $vwit[] = $this->witnessSerializer->fromParser($parser);
            }
        }

        if ($flags) {
            throw new \RuntimeException('Flags byte was 0');
        }

        $lockTime = (int) $this->uint32le->read($parser);

        $extra_payload = null;

        if ($version >= 3 && $type != 0) {
            $extra_payload = $this->payloadSerializer->fromParser($parser);
        }

        return new Transaction($version, $vin, $vout, $vwit, $lockTime, $type, $extra_payload);
    }

    /**
     * @param BufferInterface $data
     * @return TransactionInterface
     */
    public function parse(BufferInterface $data): TransactionInterface
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param TransactionInterface $transaction
     * @param int $opt
     * @return BufferInterface
     */
    public function serialize(TransactionInterface $transaction, int $opt = 0): BufferInterface
    {
        $version = $transaction->getVersion();
        $type = $transaction->getType();

        $parser = new Parser();
        $parser->appendBinary($this->int16le->write($version));
        $parser->appendBinary($this->int16le->write($type));

        $flags = 0;
        $allowWitness = !($opt & self::NO_WITNESS);
        if ($allowWitness && $transaction->hasWitness()) {
            $flags |= 1;
        }

        if ($flags) {
            $parser->appendBinary(pack("CC", 0, $flags));
        }

        $parser->appendBinary($this->varint->write(count($transaction->getInputs())));
        foreach ($transaction->getInputs() as $input) {
            $parser->appendBuffer($this->inputSerializer->serialize($input));
        }

        $parser->appendBinary($this->varint->write(count($transaction->getOutputs())));
        foreach ($transaction->getOutputs() as $output) {
            $parser->appendBuffer($this->outputSerializer->serialize($output));
        }

        if ($flags & 1) {
            foreach ($transaction->getWitnesses() as $witness) {
                $parser->appendBuffer($this->witnessSerializer->serialize($witness));
            }
        }

        $parser->appendBinary($this->uint32le->write($transaction->getLockTime()));

        if ($version >= 3 && $type != 0) {

            $extraPayload = $transaction->getExtraPayload();

            $extra_payload_size = $extraPayload->getSize();
            $extra_payload = $extraPayload->getBuffer();

            if ($extra_payload_size >= 0) {

                $parser->appendBinary($this->varint->write($extra_payload_size));
                $parser->appendBuffer($extra_payload);

            }
        }

        return $parser->getBuffer();
    }
}
