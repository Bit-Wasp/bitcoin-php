<?php

namespace BitWasp\Bitcoin\Transaction\Mutator;


use BitWasp\Bitcoin\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;

class InputCollectionMutator
{
    /**
     * @var TransactionInputInterface[]
     */
    private $inputs;

    /**
     * @param TransactionInputCollection $inputs
     */
    public function __construct(TransactionInputCollection $inputs)
    {
        $this->inputs = $inputs->getInputs();
    }

    /**
     * @param int $i
     * @return \BitWasp\Bitcoin\Transaction\TransactionInputInterface
     */
    public function getInput($i)
    {
        if (!isset($this->inputs[$i])) {
            throw new \RuntimeException('Input does not exist');
        }

        return $this->inputs[$i];
    }

    /**
     * @param int|string $i
     * @return InputMutator
     */
    public function inputMutator($i)
    {
        return new InputMutator($this->getInput($i));
    }

    /**
     * @return TransactionInputCollection
     */
    public function get()
    {
        return new TransactionInputCollection($this->inputs);
    }

    /**
     * @return $this
     */
    public function null()
    {
        $this->inputs = [];
        return $this;
    }

    /**
     * @param int|string $start
     * @param int|string $length
     * @return $this
     */
    public function slice($start, $length)
    {
        $end = count($this->inputs);
        if ($start > $end || $length > $end) {
            throw new \RuntimeException('Invalid start or length');
        }

        $this->inputs = array_slice($this->inputs, $start, $length);
        return $this;
    }

    /**
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function add(TransactionInputInterface $input)
    {
        $this->inputs[] = $input;
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function set($i, TransactionInputInterface $input)
    {
        $this->inputs[$i] = $input;
        return $this;
    }

    /**
     * @param int $i
     * @param TransactionInputInterface $input
     * @return $this
     */
    public function update($i, TransactionInputInterface $input)
    {
        $this->getInput($i);
        $this->set($i, $input);
        return $this;
    }

    /**
     * @param int $i
     * @param \Closure $closure
     * @return $this
     */
    public function apply($i, \Closure $closure)
    {
        $mutator = $this->inputMutator($i);
        $closure($mutator);
        $this->update($i, $mutator->get());
        return $this;
    }
}