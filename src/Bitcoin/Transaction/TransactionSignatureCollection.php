<?php

namespace BitWasp\Bitcoin\Transaction;

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\ScriptInterface;

class TransactionSignatureCollection implements \Countable
{
    /**
     * @var TransactionSignature[]
     */
    private $signatures = [];

    /**
     * Initialize a new collection with a list of inputs.
     *
     * @param TransactionSignature[] $signatures
     */
    public function __construct(array $signatures = [])
    {
        $this->addSignatures($signatures);
    }

    /**
     * Initialize from an input script.
     *
     * @param ScriptInterface $script
     * @return $this
     */
    public function fromTxInScript(ScriptInterface $script)
    {
        $parsed = $script->getScriptParser()->parse();

        foreach ($parsed as $data) {
            try {
                if ($data instanceof Buffer) {
                    $signature = SignatureFactory::fromHex($data->getHex());
                    $this->addSignature($signature);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $this;
    }

    /**
     * Adds an input to the collection.
     *
     * @param TransactionSignature $input
     */
    public function addSignature(TransactionSignature $input)
    {
        $this->signatures[] = $input;
    }

    /**
     * Adds a list of inputs to the collection.
     *
     * @param TransactionSignature[] $inputs
     */
    public function addSignatures(array $inputs)
    {
        foreach ($inputs as $input) {
            $this->addSignature($input);
        }
    }

    /**
     * Gets an input at the given index.
     *
     * @param int $index
     * @throws \OutOfRangeException when $index is less than 0 or greater than the number of inputs.
     * @return TransactionSignature
     */
    public function getSignature($index)
    {
        if ($index < 0 || $index >= count($this->signatures)) {
            throw new \OutOfRangeException();
        }

        return $this->signatures[$index];
    }

    /**
     * Returns all the signatures in the collection.
     *
     * @return TransactionSignature[]
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count()
    {
        return count($this->signatures);
    }

    /**
     * Returns a new sliced collection
     *
     * @param int $start
     * @param int $length
     * @return \BitWasp\Bitcoin\Transaction\TransactionSignatureCollection
     */
    public function slice($start, $length)
    {
        return new self(array_slice($this->signatures, $start, $length));
    }

    /**
     * @return \BitWasp\Buffertools\Buffer[]
     */
    public function getBuffer()
    {
        return array_map(
            function (TransactionSignature $signature) {
                return $signature->getBuffer();
            },
            $this->getSignatures()
        );
    }
}
