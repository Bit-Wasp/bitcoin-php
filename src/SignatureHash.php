<?php

namespace Bitcoin;

use Bitcoin\Util\Hash;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\SignatureHashInterface;
use Bitcoin\TransactionOutputInterface;

/**
 * Class SigHashBuilder
 * @package Bitcoin
 */
class SignatureHash implements SignatureHashInterface
{

    protected $transaction;

    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    public function calculateHash(TransactionOutputInterface $txOut, $inputToSign, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        $copy    =  $this->getTransaction();
        $inputs  = &$copy->getInputsReference();
        $outputs = &$copy->getOutputsReference();

        if ($inputToSign > count($inputs)) {
            throw new \Exception('Input does not exist');
        }

        // Default SIGHASH_ALL procedure:
        foreach ($inputs as &$input) {
            $input->setScriptBuf(new Buffer());
        }

        $inputs[$inputToSign]->setScript($txOut->getScript());

        // Additional steps for other hash types
        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_NONE) {
            $outputs = array();

            foreach ($inputs as &$input) {
                $input->setSequence(0);
            }
        } else if ($sighashType & 31 == SignatureHashInterface::SIGHASH_SINGLE) {
            // TODO
        }

        if ($sighashType & 31 == SignatureHashInterface::SIGHASH_ANYONECANPAY) {
            $input  = $inputs[$inputToSign];
            $inputs = array($input);
        }

        $txParser = new Parser($copy->serialize('hex'));
        $txParser->writeInt(4, $sighashType, true);

        $hash     = Hash::sha256d($txParser->getBuffer()->serialize('hex'));
        $buffer   = Buffer::hex($hash);
        return $buffer;

    }
}
