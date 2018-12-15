<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Exceptions\InvalidPSBTException;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class PSBT
{
    const PSBT_GLOBAL_UNSIGNED_TX = 0;

    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * Remaining PSBTGlobals key/value pairs we
     * didn't know how to parse. map[string]BufferInterface
     * @var BufferInterface[]
     */
    private $unknown = [];

    /**
     * @var PSBTInput[]
     */
    private $inputs;

    /**
     * @var PSBTOutput[]
     */
    private $outputs;

    /**
     * PSBT constructor.
     * @param TransactionInterface $tx
     * @param BufferInterface[] $unknowns
     * @param PSBTInput[] $inputs
     * @param PSBTOutput[] $outputs
     * @throws InvalidPSBTException
     */
    public function __construct(TransactionInterface $tx, array $unknowns, array $inputs, array $outputs)
    {
        if (count($tx->getInputs()) !== count($inputs)) {
            throw new InvalidPSBTException("Invalid number of inputs");
        }
        if (count($tx->getOutputs()) !== count($outputs)) {
            throw new InvalidPSBTException("Invalid number of outputs");
        }
        $numInputs = count($tx->getInputs());
        $witnesses = $tx->getWitnesses();
        for ($i = 0; $i < $numInputs; $i++) {
            $input = $tx->getInput($i);
            if ($input->getScript()->getBuffer()->getSize() > 0 || (array_key_exists($i, $witnesses) && count($witnesses[$i]) > 0)) {
                throw new InvalidPSBTException("Unsigned tx does not have empty script sig or witness");
            }
        }
        foreach ($unknowns as $key => $unknown) {
            if (!is_string($key) || !($unknown instanceof BufferInterface)) {
                throw new \InvalidArgumentException("Unknowns must be a map of string keys to Buffer values");
            }
        }
        $this->tx = $tx;
        $this->unknown = $unknowns;
        $this->inputs = $inputs;
        $this->outputs = $outputs;
    }

    /**
     * @param BufferInterface $in
     * @return PSBT
     * @throws InvalidPSBTException
     */
    public static function fromBuffer(BufferInterface $in): PSBT
    {
        $byteString5 = Types::bytestring(5);
        $vs = Types::varstring();
        $parser = new Parser($in);

        try {
            $prefix = $byteString5->read($parser);
            if ($prefix->getBinary() !== "psbt\xff") {
                throw new InvalidPSBTException("Incorrect bytes");
            }
        } catch (\Exception $e) {
            throw new InvalidPSBTException("Invalid PSBT magic", 0, $e);
        }

        $tx = null;
        $unknown = [];
        try {
            do {
                $key = $vs->read($parser);
                if ($key->getSize() === 0) {
                    break;
                }
                $value = $vs->read($parser);
                $dataType = ord(substr($key->getBinary(), 0, 1));
                switch ($dataType) {
                    case self::PSBT_GLOBAL_UNSIGNED_TX:
                        if ($tx !== null) {
                            throw new \RuntimeException("Duplicate global tx");
                        } else if ($key->getSize() !== 1) {
                            throw new \RuntimeException("Invalid key length");
                        }
                        $tx = TransactionFactory::fromBuffer($value);
                        break;
                    default:
                        if (array_key_exists($key->getBinary(), $unknown)) {
                            throw new InvalidPSBTException("Duplicate unknown key");
                        }
                        $unknown[$key->getBinary()] = $value;
                        break;
                }
            } while ($parser->getPosition() < $parser->getSize());
        } catch (\Exception $e) {
            throw new InvalidPSBTException("Failed to parse global section", 0, $e);
        }

        if (!$tx) {
            throw new InvalidPSBTException("Missing global tx");
        }

        $numInputs = count($tx->getInputs());
        $inputs = [];
        for ($i = 0; $parser->getPosition() < $parser->getSize() && $i < $numInputs; $i++) {
            try {
                $input = PSBTInput::fromParser($parser, $vs);
                $inputs[] = $input;
            } catch (\Exception $e) {
                throw new InvalidPSBTException("Failed to parse inputs section", 0, $e);
            }
        }
        if ($numInputs !== count($inputs)) {
            throw new InvalidPSBTException("Missing inputs");
        }

        $numOutputs = count($tx->getOutputs());
        $outputs = [];
        for ($i = 0; $parser->getPosition() < $parser->getSize() && $i < $numOutputs; $i++) {
            try {
                $output = PSBTOutput::fromParser($parser, $vs);
                $outputs[] = $output;
            } catch (\Exception $e) {
                throw new InvalidPSBTException("Failed to parse outputs section", 0, $e);
            }
        }

        if ($numOutputs !== count($outputs)) {
            throw new InvalidPSBTException("Missing outputs");
        }

        return new PSBT($tx, $unknown, $inputs, $outputs);
    }

    /**
     * @return TransactionInterface
     */
    public function getTransaction(): TransactionInterface
    {
        return $this->tx;
    }
    /**
     * @return string[]
     */
    public function getUnknowns(): array
    {
        return $this->unknown;
    }

    /**
     * @return PSBTInput[]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @return PSBTOutput[]
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    public function updateInput(int $input, \Closure $modifyPsbtIn)
    {
        if (!array_key_exists($input, $this->inputs)) {
            throw new \RuntimeException("No input at this index");
        }

        $updatable = new UpdatableInput($this, $input, $this->inputs[$input]);
        $modifyPsbtIn($updatable);
        $this->inputs[$input] = $updatable->input();
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer(): BufferInterface
    {
        $vs = Types::varstring();
        $parser = new Parser();
        $parser->appendBinary("psbt\xff");
        $parser->appendBinary($vs->write(new Buffer(chr(self::PSBT_GLOBAL_UNSIGNED_TX))));
        $parser->appendBinary($vs->write($this->tx->getBuffer()));
        foreach ($this->unknown as $key => $value) {
            $parser->appendBinary($vs->write(new Buffer($key)));
            $parser->appendBinary($vs->write($value));
        }
        $parser->appendBinary($vs->write(new Buffer()));

        $numInputs = count($this->tx->getInputs());
        for ($i = 0; $i < $numInputs; $i++) {
            $this->inputs[$i]->writeToParser($parser, $vs);
        }
        $numOutputs = count($this->tx->getOutputs());
        for ($i = 0; $i < $numOutputs; $i++) {
            $this->outputs[$i]->writeToParser($parser, $vs);
        }
        return $parser->getBuffer();
    }
}
