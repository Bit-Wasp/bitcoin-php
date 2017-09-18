<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Locktime;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Script\Interpreter\BitcoinCashChecker;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Parser\Operation;
use BitWasp\Bitcoin\Script\Path\BranchInterpreter;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkey;
use BitWasp\Bitcoin\Script\ScriptInfo\PayToPubkeyHash;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\Factory\ScriptInfo\CheckLocktimeVerify;
use BitWasp\Bitcoin\Transaction\Factory\ScriptInfo\CheckSequenceVerify;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class InputSigner implements InputSignerInterface
{
    /**
     * @var array
     */
    protected static $canSign = [
        ScriptType::P2PKH,
        ScriptType::P2PK,
        ScriptType::MULTISIG
    ];

    /**
     * @var array
     */
    protected static $validP2sh = [
        ScriptType::P2WKH,
        ScriptType::P2WSH,
        ScriptType::P2PKH,
        ScriptType::P2PK,
        ScriptType::MULTISIG
    ];

    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var OutputData $scriptPubKey
     */
    private $scriptPubKey;

    /**
     * @var OutputData $redeemScript
     */
    private $redeemScript;

    /**
     * @var OutputData $witnessScript
     */
    private $witnessScript;

    /**
     * @var OutputData
     */
    private $signScript;

    /**
     * @var bool
     */
    private $padUnsignedMultisigs = false;

    /**
     * @var bool
     */
    private $tolerateInvalidPublicKey = false;

    /**
     * @var bool
     */
    private $redeemBitcoinCash = false;

    /**
     * @var bool
     */
    private $allowComplexScripts = false;

    /**
     * @var SignData
     */
    private $signData;

    /**
     * @var int
     */
    private $sigVersion;

    /**
     * @var int
     */
    private $flags;

    /**
     * @var TransactionInterface
     */
    private $tx;

    /**
     * @var int
     */
    private $nInput;

    /**
     * @var TransactionOutputInterface
     */
    private $txOut;

    /**
     * @var Interpreter
     */
    private $interpreter;

    /**
     * @var Checker
     */
    private $signatureChecker;

    /**
     * @var TransactionSignatureSerializer
     */
    private $txSigSerializer;

    /**
     * @var PublicKeySerializerInterface
     */
    private $pubKeySerializer;

    /**
     * @var Conditional[]|Checksig[]
     */
    private $steps = [];

    /**
     * InputSigner constructor.
     *
     * Note, the implementation of this class is considered internal
     * and only the methods exposed on InputSignerInterface should
     * be depended on to avoid BC breaks.
     *
     * The only recommended way to produce this class is using Signer::input()
     *
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @param int $nInput
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     * @param TransactionSignatureSerializer|null $sigSerializer
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, SignData $signData, TransactionSignatureSerializer $sigSerializer = null, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->signData = $signData;

        $this->txSigSerializer = $sigSerializer ?: new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter));
        $this->pubKeySerializer = $pubKeySerializer ?: EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $this->interpreter = new Interpreter($this->ecAdapter);
    }

    /**
     * @return InputSigner
     */
    public function extract()
    {
        $defaultFlags = Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY | Interpreter::VERIFY_CHECKSEQUENCEVERIFY | Interpreter::VERIFY_WITNESS;
        $checker = new Checker($this->ecAdapter, $this->tx, $this->nInput, $this->txOut->getValue(), $this->txSigSerializer, $this->pubKeySerializer);

        if ($this->redeemBitcoinCash) {
            // unset VERIFY_WITNESS default
            $defaultFlags = $defaultFlags & (~Interpreter::VERIFY_WITNESS);

            if ($this->signData->hasSignaturePolicy()) {
                if ($this->signData->getSignaturePolicy() & Interpreter::VERIFY_WITNESS) {
                    throw new \RuntimeException("VERIFY_WITNESS is not possible for bitcoin cash");
                }
            }

            $checker = new BitcoinCashChecker($this->ecAdapter, $this->tx, $this->nInput, $this->txOut->getValue(), $this->txSigSerializer, $this->pubKeySerializer);
        }

        $this->flags = $this->signData->hasSignaturePolicy() ? $this->signData->getSignaturePolicy() : $defaultFlags;
        $this->signatureChecker = $checker;

        $witnesses = $this->tx->getWitnesses();
        $witness = array_key_exists($this->nInput, $witnesses) ? $witnesses[$this->nInput]->all() : [];

        return $this->solve(
            $this->signData,
            $this->txOut->getScript(),
            $this->tx->getInput($this->nInput)->getScript(),
            $witness
        );
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function padUnsignedMultisigs($setting)
    {
        $this->padUnsignedMultisigs = (bool) $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function tolerateInvalidPublicKey($setting)
    {
        $this->tolerateInvalidPublicKey = (bool) $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function redeemBitcoinCash($setting)
    {
        $this->redeemBitcoinCash = (bool) $setting;
        return $this;
    }

    /**
     * @param bool $setting
     * @return $this
     */
    public function allowComplexScripts($setting)
    {
        $this->allowComplexScripts = (bool) $setting;
        return $this;
    }

    /**
     * @param BufferInterface $vchPubKey
     * @return PublicKeyInterface|null
     * @throws \Exception
     */
    protected function parseStepPublicKey(BufferInterface $vchPubKey)
    {
        try {
            return $this->pubKeySerializer->parse($vchPubKey);
        } catch (\Exception $e) {
            if ($this->tolerateInvalidPublicKey) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * A snippet from OP_CHECKMULTISIG - links keys to signatures
     *
     * @param ScriptInterface $script
     * @param BufferInterface[] $signatures
     * @param BufferInterface[] $publicKeys
     * @param int $sigVersion
     * @return \SplObjectStorage
     */
    private function sortMultisigs(ScriptInterface $script, array $signatures, array $publicKeys, $sigVersion)
    {
        $sigCount = count($signatures);
        $keyCount = count($publicKeys);
        $ikey = $isig = 0;
        $fSuccess = true;
        $result = new \SplObjectStorage;

        while ($fSuccess && $sigCount > 0) {
            // Fetch the signature and public key
            $sig = $signatures[$isig];
            $pubkey = $publicKeys[$ikey];

            if ($this->signatureChecker->checkSig($script, $sig, $pubkey, $sigVersion, $this->flags)) {
                $result[$pubkey] = $sig;
                $isig++;
                $sigCount--;
            }

            $ikey++;
            $keyCount--;

            // If there are more signatures left than keys left,
            // then too many signatures have failed. Exit early,
            // without checking any further signatures.
            if ($sigCount > $keyCount) {
                $fSuccess = false;
            }
        }

        return $result;
    }

    /**
     * @param ScriptInterface $script
     * @return \BitWasp\Buffertools\BufferInterface[]
     */
    private function evalPushOnly(ScriptInterface $script)
    {
        $stack = new Stack();
        $this->interpreter->evaluate($script, $stack, SigHash::V0, $this->flags | Interpreter::VERIFY_SIGPUSHONLY, $this->signatureChecker);
        return $stack->all();
    }

    /**
     * Create a script consisting only of push-data operations.
     * Suitable for a scriptSig.
     *
     * @param BufferInterface[] $buffers
     * @return ScriptInterface
     */
    private function pushAll(array $buffers)
    {
        return ScriptFactory::sequence(array_map(function ($buffer) {
            if (!($buffer instanceof BufferInterface)) {
                throw new \RuntimeException('Script contained a non-push opcode');
            }

            $size = $buffer->getSize();
            if ($size === 0) {
                return Opcodes::OP_0;
            }

            $first = ord($buffer->getBinary()[0]);
            if ($size === 1 && $first >= 1 && $first <= 16) {
                return \BitWasp\Bitcoin\Script\encodeOpN($first);
            } else {
                return $buffer;
            }
        }, $buffers));
    }

    /**
     * Verify a scriptSig / scriptWitness against a scriptPubKey.
     * Useful for checking the outcome of certain things, like hash locks (p2sh)
     *
     * @param int $flags
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param ScriptWitnessInterface|null $scriptWitness
     * @return bool
     */
    private function verifySolution($flags, ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, ScriptWitnessInterface $scriptWitness = null)
    {
        return $this->interpreter->verify($scriptSig, $scriptPubKey, $flags, $this->signatureChecker, $scriptWitness);
    }

    /**
     * @param array $decoded
     * @param null $solution
     * @return null|TimeLock|Checksig
     */
    private function classifySignStep(array $decoded, &$solution = null)
    {
        try {
            $details = Multisig::fromDecodedScript($decoded, $this->pubKeySerializer, true);
            $solution = $details->getKeyBuffers();
            return new Checksig($details);
        } catch (\Exception $e) {
        }

        try {
            $details = PayToPubkey::fromDecodedScript($decoded, true);
            $solution = $details->getKeyBuffer();
            return new Checksig($details);
        } catch (\Exception $e) {
        }

        try {
            $details = PayToPubkeyHash::fromDecodedScript($decoded, true);
            $solution = $details->getPubKeyHash();
            return new Checksig($details);
        } catch (\Exception $e) {
        }

        try {
            $details = CheckLocktimeVerify::fromDecodedScript($decoded);
            return new TimeLock($details);
        } catch (\Exception $e) {
        }

        try {
            $details = CheckSequenceVerify::fromDecodedScript($decoded);
            return new TimeLock($details);
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * @param Operation[] $scriptOps
     * @return Checksig[]
     */
    public function parseSequence(array $scriptOps)
    {
        $j = 0;
        $l = count($scriptOps);
        $result = [];
        while ($j < $l) {
            $step = null;
            $slice = null;

            // increment the $last, and break if it's valid
            for ($i = 0; $i < ($l - $j) + 1; $i++) {
                $slice = array_slice($scriptOps, $j, $i);
                $step = $this->classifySignStep($slice, $solution);
                if ($step !== null) {
                    break;
                }
            }

            if (null === $step) {
                throw new \RuntimeException("Invalid script");
            } else {
                $j += $i;
                $result[] = $step;
            }
        }

        return $result;
    }

    /**
     * @param Operation $operation
     * @param Stack $mainStack
     * @param bool[] $pathData
     * @return Conditional
     */
    public function extractConditionalOp(Operation $operation, Stack $mainStack, array &$pathData)
    {
        $opValue = null;

        if (!$mainStack->isEmpty()) {
            if (count($pathData) === 0) {
                throw new \RuntimeException("Extracted conditional op (including mainstack) without corresponding element in path data");
            }

            $opValue = $this->interpreter->castToBool($mainStack->pop());
            $dataValue = array_shift($pathData);
            if ($opValue !== $dataValue) {
                throw new \RuntimeException("Current stack doesn't follow branch path");
            }
        } else {
            if (count($pathData) === 0) {
                throw new \RuntimeException("Extracted conditional op without corresponding element in path data");
            }

            $opValue = array_shift($pathData);
        }

        $conditional = new Conditional($operation->getOp());

        if ($opValue !== null) {
            if (!is_bool($opValue)) {
                throw new \RuntimeException("Sanity check, path value (likely from pathData) was not a bool");
            }

            $conditional->setValue($opValue);
        }

        return $conditional;
    }

    /**
     * @param int $idx
     * @return Checksig|Conditional
     */
    public function step($idx)
    {
        if (!array_key_exists($idx, $this->steps)) {
            throw new \RuntimeException("Out of range index for input sign step");
        }

        return $this->steps[$idx];
    }

    /**
     * @param OutputData $solution
     * @param array $sigChunks
     * @param SignData $signData
     */
    public function extractScript(OutputData $solution, array $sigChunks, SignData $signData)
    {
        $logicInterpreter = new BranchInterpreter();
        $tree = $logicInterpreter->getScriptTree($solution->getScript());

        if ($tree->hasMultipleBranches()) {
            $logicalPath = $signData->getLogicalPath();
            // we need a function like findWitnessScript to 'check'
            // partial signatures against _our_ path
        } else {
            $logicalPath = [];
        }

        $scriptSections = $tree
            ->getBranchByPath($logicalPath)
            ->getScriptSections();

        $vfStack = new Stack();
        $stack = new Stack($sigChunks);

        $pathCopy = $logicalPath;
        $steps = [];
        foreach ($scriptSections as $i => $scriptSection) {
            /** @var Operation[] $scriptSection */
            $fExec = !$this->interpreter->checkExec($vfStack, false);
            if (count($scriptSection) === 1 && $scriptSection[0]->isLogical()) {
                $op = $scriptSection[0];
                switch ($op->getOp()) {
                    case Opcodes::OP_IF:
                    case Opcodes::OP_NOTIF:
                        $value = false;
                        if ($fExec) {
                            // Pop from mainStack if $fExec
                            $step = $this->extractConditionalOp($op, $stack, $pathCopy);

                            // the Conditional has a value in this case:
                            $value = $step->getValue();

                            // Connect the last operation (if there is one)
                            // with the last step with isRequired==$value
                            // todo: check this part out..
                            for ($j = count($steps) - 1; $j >= 0; $j--) {
                                if ($steps[$j] instanceof Checksig && $value === $steps[$j]->isRequired()) {
                                    $step->providedBy($steps[$j]);
                                    break;
                                }
                            }
                        } else {
                            $step = new Conditional($op->getOp());
                        }

                        $steps[] = $step;

                        if ($op->getOp() === Opcodes::OP_NOTIF) {
                            $value = !$value;
                        }

                        $vfStack->push($value);
                        break;
                    case Opcodes::OP_ENDIF:
                        $vfStack->pop();
                        break;
                    case Opcodes::OP_ELSE:
                        $vfStack->push(!$vfStack->pop());
                        break;
                }
            } else {
                $templateTypes = $this->parseSequence($scriptSection);

                // Detect if effect on mainStack is `false`
                $resolvesFalse = count($pathCopy) > 0 && !$pathCopy[0];
                if ($resolvesFalse) {
                    if (count($templateTypes) > 1) {
                        throw new \RuntimeException("Unsupported script, multiple steps to segment which is negated");
                    }
                }

                foreach ($templateTypes as $k => $checksig) {
                    if ($fExec) {
                        if ($checksig instanceof Checksig) {
                            $this->extractChecksig($solution->getScript(), $checksig, $stack, $this->sigVersion, $resolvesFalse);

                            // If this statement results is later consumed
                            // by a conditional which would be false, mark
                            // this operation as not required
                            if ($resolvesFalse) {
                                $checksig->setRequired(false);
                            }
                        } else if ($checksig instanceof TimeLock) {
                            $this->checkTimeLock($checksig);
                        }

                        $steps[] = $checksig;
                    }
                }
            }
        }

        $this->steps = $steps;
    }

    /**
     * @param int $verify
     * @param int $input
     * @param int $threshold
     * @return int
     */
    private function compareRangeAgainstThreshold($verify, $input, $threshold)
    {
        if ($verify <= $threshold && $input > $threshold) {
            return -1;
        }

        if ($verify > $threshold && $input <= $threshold) {
            return 1;
        }

        return 0;
    }

    /**
     * @param TimeLock $timelock
     */
    public function checkTimeLock(TimeLock $timelock)
    {
        $info = $timelock->getInfo();
        if (($this->flags & Interpreter::VERIFY_CHECKLOCKTIMEVERIFY) != 0 && $info instanceof CheckLocktimeVerify) {
            $verifyLocktime = $info->getLocktime();
            if (!$this->signatureChecker->checkLockTime(Number::int($verifyLocktime))) {
                $input = $this->tx->getInput($this->nInput);
                if ($input->isFinal()) {
                    throw new \RuntimeException("Input sequence is set to max, therefore CHECKLOCKTIMEVERIFY would fail");
                }

                $locktime = $this->tx->getLockTime();
                $cmp = $this->compareRangeAgainstThreshold($verifyLocktime, $locktime, Locktime::BLOCK_MAX);
                if ($cmp === -1) {
                    throw new \RuntimeException("CLTV was for block height, but tx locktime was in timestamp range");
                } else if ($cmp === 1) {
                    throw new \RuntimeException("CLTV was for timestamp, but tx locktime was in block range");
                }

                $requiredTime = ($info->isLockedToBlock() ? "block {$info->getLocktime()}" : "{$info->getLocktime()}s (median time past)");
                throw new \RuntimeException("Output is not yet spendable, must wait until {$requiredTime}");
            }
        }

        if (($this->flags & Interpreter::VERIFY_CHECKSEQUENCEVERIFY) != 0 && $info instanceof CheckSequenceVerify) {
            // Future soft-fork extensibility, NOP if disabled flag
            if (($info->getRelativeLockTime() & TransactionInput::SEQUENCE_LOCKTIME_DISABLE_FLAG) != 0) {
                return;
            }

            if (!$this->signatureChecker->checkSequence(Number::int($info->getRelativeLockTime()))) {
                if ($this->tx->getVersion() < 2) {
                    throw new \RuntimeException("Transaction version must be 2 or greater for CSV");
                }

                $input = $this->tx->getInput($this->nInput);
                if ($input->isFinal()) {
                    throw new \RuntimeException("Sequence LOCKTIME_DISABLE_FLAG is set - not allowed on CSV output");
                }

                $cmp = $this->compareRangeAgainstThreshold($info->getRelativeLockTime(), $input->getSequence(), TransactionInput::SEQUENCE_LOCKTIME_TYPE_FLAG);
                if ($cmp === -1) {
                    throw new \RuntimeException("CSV was for block height, but txin sequence was in timestamp range");
                } else if ($cmp === 1) {
                    throw new \RuntimeException("CSV was for timestamp, but txin sequence was in block range");
                }

                $masked = $info->getRelativeLockTime() & TransactionInput::SEQUENCE_LOCKTIME_MASK;
                $requiredLock = "{$masked} " . ($info->isRelativeToBlock() ? " (blocks)" : "(seconds after txOut)");
                throw new \RuntimeException("Output unspendable with this sequence, must be locked for {$requiredLock}");
            }
        }
    }

    /**
     * This function is strictly for $canSign types.
     * It will extract signatures/publicKeys when given $outputData, and $stack.
     * $stack is the result of decompiling a scriptSig, or taking the witness data.
     *
     * @param ScriptInterface $script
     * @param Checksig $checksig
     * @param Stack $stack
     * @param int $sigVersion
     * @param bool $expectFalse
     */
    public function extractChecksig(ScriptInterface $script, Checksig $checksig, Stack $stack, $sigVersion, $expectFalse)
    {
        $size = count($stack);

        if ($checksig->getType() === ScriptType::P2PKH) {
            if ($size > 1) {
                $vchPubKey = $stack->pop();
                $vchSig = $stack->pop();

                $value = false;
                if (!$expectFalse) {
                    $value = $this->signatureChecker->checkSig($script, $vchSig, $vchPubKey, $this->sigVersion, $this->flags);
                    if (!$value) {
                        throw new \RuntimeException('Existing signatures are invalid!');
                    }
                }

                if (!$checksig->isVerify()) {
                    $stack->push($value ? new Buffer("\x01") : new Buffer());
                }

                if (!$expectFalse) {
                    $checksig
                        ->setSignature(0, $this->txSigSerializer->parse($vchSig))
                        ->setKey(0, $this->parseStepPublicKey($vchPubKey))
                    ;
                }
            }
        } else if ($checksig->getType() === ScriptType::P2PK) {
            if ($size > 0) {
                $vchSig = $stack->pop();

                $value = false;
                if (!$expectFalse) {
                    $value = $this->signatureChecker->checkSig($script, $vchSig, $checksig->getSolution(), $this->sigVersion, $this->flags);
                    if (!$value) {
                        throw new \RuntimeException('Existing signatures are invalid!');
                    }
                }

                if (!$checksig->isVerify()) {
                    $stack->push($value ? new Buffer("\x01") : new Buffer());
                }

                if (!$expectFalse) {
                    $checksig->setSignature(0, $this->txSigSerializer->parse($vchSig));
                }
            }

            $checksig->setKey(0, $this->parseStepPublicKey($checksig->getSolution()));
        } else if (ScriptType::MULTISIG === $checksig->getType()) {
            /** @var Multisig $info */
            $info = $checksig->getInfo();
            $keyBuffers = $info->getKeyBuffers();
            foreach ($keyBuffers as $idx => $keyBuf) {
                $checksig->setKey($idx, $this->parseStepPublicKey($keyBuf));
            }

            $value = false;
            if ($this->padUnsignedMultisigs) {
                // Multisig padding is only used for partially signed transactions,
                // never fully signed. It is recognized by a scriptSig with $keyCount+1
                // values (including the dummy), with one for each candidate signature,
                // such that $this->signatures state is captured.
                // The feature serves to skip validation/sorting an incomplete multisig.

                if ($size === 1 + $info->getKeyCount()) {
                    $sigBufCount = 0;
                    $null = new Buffer();
                    $keyToSigMap = new \SplObjectStorage();

                    // Reproduce $keyToSigMap and $sigBufCount
                    for ($i = 0; $i < $info->getKeyCount(); $i++) {
                        if (!$stack[-1 - $i]->equals($null)) {
                            $keyToSigMap[$keyBuffers[$i]] = $stack[-1 - $i];
                            $sigBufCount++;
                        }
                    }

                    // We observed $this->requiredSigs sigs, therefore we can
                    // say the implementation is incompatible
                    if ($sigBufCount === $checksig->getRequiredSigs()) {
                        throw new \RuntimeException("Padding is forbidden for a fully signed multisig script");
                    }

                    $toDelete = 1 + $info->getKeyCount();
                    $value = true;
                }
            }

            if (!isset($toDelete) || !isset($keyToSigMap)) {
                // Check signatures irrespective of scriptSig size, primes Checker cache, and need info
                $sigBufs = [];
                $max = min($checksig->getRequiredSigs(), $size - 1);
                for ($i = 0; $i < $max; $i++) {
                    $vchSig = $stack[-1 - $i];
                    $sigBufs[] = $vchSig;
                }

                $sigBufs = array_reverse($sigBufs);
                $sigBufCount = count($sigBufs);

                if (!$expectFalse) {
                    if ($sigBufCount > 0) {
                        $keyToSigMap = $this->sortMultiSigs($script, $sigBufs, $keyBuffers, $sigVersion);
                        // Here we learn if any signatures were invalid, it won't be in the map.
                        if ($sigBufCount !== count($keyToSigMap)) {
                            throw new \RuntimeException('Existing signatures are invalid!');
                        }
                        $toDelete = 1 + count($keyToSigMap);
                    } else {
                        $toDelete = 0;
                        $keyToSigMap = new \SplObjectStorage();
                    }
                    $value = true;
                } else {
                    // should check that all signatures are zero
                    $keyToSigMap = new \SplObjectStorage();
                    $toDelete = min($stack->count(), 1 + $info->getRequiredSigCount());
                    $value = false;
                }
            }

            while ($toDelete--) {
                $stack->pop();
            }

            foreach ($keyBuffers as $idx => $key) {
                if (isset($keyToSigMap[$key])) {
                    $checksig->setSignature($idx, $this->txSigSerializer->parse($keyToSigMap[$key]));
                }
            }

            if (!$checksig->isVerify()) {
                $stack->push($value ? new Buffer("\x01") : new Buffer());
            }
        } else {
            throw new \RuntimeException('Unsupported output type passed to extractFromValues');
        }
    }

    /**
     * Checks $chunks (a decompiled scriptSig) for it's last element,
     * or defers to SignData. If both are provided, it checks the
     * value from $chunks against SignData.
     *
     * @param BufferInterface[] $chunks
     * @param SignData $signData
     * @return ScriptInterface
     */
    private function findRedeemScript(array $chunks, SignData $signData)
    {
        if (count($chunks) > 0) {
            $redeemScript = new Script($chunks[count($chunks) - 1]);
            if ($signData->hasRedeemScript()) {
                if (!$redeemScript->equals($signData->getRedeemScript())) {
                    throw new \RuntimeException('Extracted redeemScript did not match sign data');
                }
            }
        } else {
            if (!$signData->hasRedeemScript()) {
                throw new \RuntimeException('Redeem script not provided in sign data or scriptSig');
            }
            $redeemScript = $signData->getRedeemScript();
        }

        return $redeemScript;
    }

    /**
     * Checks $witness (a witness structure) for it's last element,
     * or defers to SignData. If both are provided, it checks the
     * value from $chunks against SignData.
     *
     * @param BufferInterface[] $witness
     * @param SignData $signData
     * @return ScriptInterface
     */
    private function findWitnessScript(array $witness, SignData $signData)
    {
        if (count($witness) > 0) {
            $witnessScript = new Script($witness[count($witness) - 1]);
            if ($signData->hasWitnessScript()) {
                if (!$witnessScript->equals($signData->getWitnessScript())) {
                    throw new \RuntimeException('Extracted witnessScript did not match sign data');
                }
            }
        } else {
            if (!$signData->hasWitnessScript()) {
                throw new \RuntimeException('Witness script not provided in sign data or witness');
            }
            $witnessScript = $signData->getWitnessScript();
        }

        return $witnessScript;
    }

    /**
     * Needs to be called before using the instance. By `extract`.
     *
     * It ensures that violating the following prevents instance creation
     *  - the scriptPubKey can be directly signed, or leads to P2SH/P2WSH/P2WKH
     *  - the P2SH script covers signable types and P2WSH/P2WKH
     *  - the witnessScript covers signable types only
     *
     * @param SignData $signData
     * @param ScriptInterface $scriptPubKey
     * @param ScriptInterface $scriptSig
     * @param BufferInterface[] $witness
     * @return $this
     */
    private function solve(SignData $signData, ScriptInterface $scriptPubKey, ScriptInterface $scriptSig, array $witness)
    {
        $classifier = new OutputClassifier();
        $sigVersion = SigHash::V0;
        $solution = $this->scriptPubKey = $classifier->decode($scriptPubKey);

        if (!$this->allowComplexScripts) {
            if ($solution->getType() !== ScriptType::P2SH && !in_array($solution->getType(), self::$validP2sh)) {
                throw new \RuntimeException('scriptPubKey not supported');
            }
        }

        $sigChunks = $this->evalPushOnly($scriptSig);

        if ($solution->getType() === ScriptType::P2SH) {
            $redeemScript = $this->findRedeemScript($sigChunks, $signData);
            if (!$this->verifySolution(Interpreter::VERIFY_SIGPUSHONLY, ScriptFactory::sequence([$redeemScript->getBuffer()]), $solution->getScript())) {
                throw new \RuntimeException('Redeem script fails to solve pay-to-script-hash');
            }

            $solution = $this->redeemScript = $classifier->decode($redeemScript);
            if (!$this->allowComplexScripts) {
                if (!in_array($solution->getType(), self::$validP2sh)) {
                    throw new \RuntimeException('Unsupported pay-to-script-hash script');
                }
            }

            $sigChunks = array_slice($sigChunks, 0, -1);
        }

        if ($solution->getType() === ScriptType::P2WKH) {
            $sigVersion = SigHash::V1;
            $solution = $classifier->decode(ScriptFactory::scriptPubKey()->payToPubKeyHash($solution->getSolution()));
            $sigChunks = $witness;
        } else if ($solution->getType() === ScriptType::P2WSH) {
            $sigVersion = SigHash::V1;
            $witnessScript = $this->findWitnessScript($witness, $signData);

            // Essentially all the reference implementation does
            if (!$witnessScript->getWitnessScriptHash()->equals($solution->getSolution())) {
                throw new \RuntimeException('Witness script fails to solve witness-script-hash');
            }

            $solution = $this->witnessScript = $classifier->decode($witnessScript);
            if (!$this->allowComplexScripts) {
                if (!in_array($this->witnessScript->getType(), self::$canSign)) {
                    throw new \RuntimeException('Unsupported witness-script-hash script');
                }
            }

            $sigChunks = array_slice($witness, 0, -1);
        }

        $this->sigVersion = $sigVersion;
        $this->signScript = $solution;

        $this->extractScript($solution, $sigChunks, $signData);

        return $this;
    }

    /**
     * Pure function to produce a signature hash for a given $scriptCode, $sigHashType, $sigVersion.
     *
     * @param ScriptInterface $scriptCode
     * @param int $sigHashType
     * @param int $sigVersion
     * @return BufferInterface
     */
    public function calculateSigHashUnsafe(ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        if (!$this->signatureChecker->isDefinedHashtype($sigHashType)) {
            throw new \RuntimeException('Invalid sigHashType requested');
        }

        return $this->signatureChecker->getSigHash($scriptCode, $sigHashType, $sigVersion);
    }

    /**
     * Calculates the signature hash for the input for the given $sigHashType.
     *
     * @param int $sigHashType
     * @return BufferInterface
     */
    public function getSigHash($sigHashType)
    {
        return $this->calculateSigHashUnsafe($this->signScript->getScript(), $sigHashType, $this->sigVersion);
    }

    /**
     * Pure function to produce a signature for a given $key, $scriptCode, $sigHashType, $sigVersion.
     *
     * @param PrivateKeyInterface $key
     * @param ScriptInterface $scriptCode
     * @param int $sigHashType
     * @param int $sigVersion
     * @return TransactionSignatureInterface
     */
    private function calculateSignature(PrivateKeyInterface $key, ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        $hash = $this->calculateSigHashUnsafe($scriptCode, $sigHashType, $sigVersion);
        $ecSignature = $this->ecAdapter->sign($hash, $key, new Rfc6979($this->ecAdapter, $key, $hash, 'sha256'));
        return new TransactionSignature($this->ecAdapter, $ecSignature, $sigHashType);
    }

    /**
     * Returns whether all required signatures have been provided.
     *
     * @return bool
     */
    public function isFullySigned()
    {
        foreach ($this->steps as $step) {
            if ($step instanceof Conditional) {
                if (!$step->hasValue()) {
                    return false;
                }
            } else if ($step instanceof Checksig) {
                if (!$step->isFullySigned()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the required number of signatures for this input.
     *
     * @return int
     */
    public function getRequiredSigs()
    {
        $count = 0;
        foreach ($this->steps as $step) {
            if ($step instanceof Checksig) {
                $count += $step->getRequiredSigs();
            }
        }
        return $count;
    }

    /**
     * Returns an array where the values are either null,
     * or a TransactionSignatureInterface.
     *
     * @return TransactionSignatureInterface[]
     */
    public function getSignatures()
    {
        return $this->steps[0]->getSignatures();
    }

    /**
     * Returns an array where the values are either null,
     * or a PublicKeyInterface.
     *
     * @return PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->steps[0]->getKeys();
    }

    /**
     * OutputData for the script to be signed (will be
     * equal to getScriptPubKey, or getRedeemScript, or
     * getWitnessScript.
     *
     * @return OutputData
     */
    public function getSignScript()
    {
        return $this->signScript;
    }

    /**
     * OutputData for the txOut script.
     *
     * @return OutputData
     */
    public function getScriptPubKey()
    {
        return $this->scriptPubKey;
    }

    /**
     * Returns OutputData for the P2SH redeemScript.
     *
     * @return OutputData
     */
    public function getRedeemScript()
    {
        if (null === $this->redeemScript) {
            throw new \RuntimeException("Input has no redeemScript, cannot call getRedeemScript");
        }

        return $this->redeemScript;
    }

    /**
     * Returns OutputData for the P2WSH witnessScript.
     *
     * @return OutputData
     */
    public function getWitnessScript()
    {
        if (null === $this->witnessScript) {
            throw new \RuntimeException("Input has no witnessScript, cannot call getWitnessScript");
        }

        return $this->witnessScript;
    }

    /**
     * Returns whether the scriptPubKey is P2SH.
     *
     * @return bool
     */
    public function isP2SH()
    {
        if ($this->scriptPubKey->getType() === ScriptType::P2SH && ($this->redeemScript instanceof OutputData)) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether the scriptPubKey or redeemScript is P2WSH.
     *
     * @return bool
     */
    public function isP2WSH()
    {
        if ($this->redeemScript instanceof OutputData) {
            if ($this->redeemScript->getType() === ScriptType::P2WSH && ($this->witnessScript instanceof OutputData)) {
                return true;
            }
        }

        if ($this->scriptPubKey->getType() === ScriptType::P2WSH && ($this->witnessScript instanceof OutputData)) {
            return true;
        }

        return false;
    }

    /**
     * @param int $stepIdx
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return $this
     */
    public function signStep($stepIdx, PrivateKeyInterface $privateKey, $sigHashType = SigHash::ALL)
    {
        if (!array_key_exists($stepIdx, $this->steps)) {
            throw new \RuntimeException("Unknown step index");
        }

        $checksig = $this->steps[$stepIdx];

        if (!($checksig instanceof Checksig)) {
            throw new \RuntimeException("That index is a conditional, so cannot be signed");
        }

        if ($checksig->isFullySigned()) {
            return $this;
        }

        if (SigHash::V1 === $this->sigVersion && !$privateKey->isCompressed()) {
            throw new \RuntimeException('Uncompressed keys are disallowed in segwit scripts - refusing to sign');
        }

        if ($checksig->getType() === ScriptType::P2PK) {
            if (!$this->pubKeySerializer->serialize($privateKey->getPublicKey())->equals($checksig->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }

            if (!$checksig->hasSignature(0)) {
                $signature = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
                $checksig->setSignature(0, $signature);
            }
        } else if ($checksig->getType() === ScriptType::P2PKH) {
            $publicKey = $privateKey->getPublicKey();
            if (!$publicKey->getPubKeyHash()->equals($checksig->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }

            if (!$checksig->hasSignature(0)) {
                $signature = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
                $checksig->setSignature(0, $signature);
            }

            if (!$checksig->hasKey(0)) {
                $checksig->setKey(0, $publicKey);
            }
        } else if ($checksig->getType() === ScriptType::MULTISIG) {
            $signed = false;
            foreach ($checksig->getKeys() as $keyIdx => $publicKey) {
                if (!$checksig->hasSignature($keyIdx)) {
                    if ($publicKey instanceof PublicKeyInterface && $privateKey->getPublicKey()->equals($publicKey)) {
                        $signature = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
                        $checksig->setSignature($keyIdx, $signature);
                        $signed = true;
                    }
                }
            }

            if (!$signed) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
        } else {
            throw new \RuntimeException('Unexpected error - sign script had an unexpected type');
        }

        return $this;
    }

    /**
     * Sign the input using $key and $sigHashTypes
     *
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return $this
     */
    public function sign(PrivateKeyInterface $privateKey, $sigHashType = SigHash::ALL)
    {
        return $this->signStep(0, $privateKey, $sigHashType);
    }

    /**
     * Verifies the input using $flags for script verification
     *
     * @param int $flags
     * @return bool
     */
    public function verify($flags = null)
    {
        $consensus = ScriptFactory::consensus();

        if ($flags === null) {
            $flags = $this->flags;
        }

        $flags |= Interpreter::VERIFY_P2SH;
        if (SigHash::V1 === $this->sigVersion) {
            $flags |= Interpreter::VERIFY_WITNESS;
        }

        $sig = $this->serializeSignatures();

        // Take serialized signatures, and use mutator to add this inputs sig data
        $mutator = TransactionFactory::mutate($this->tx);
        $mutator->inputsMutator()[$this->nInput]->script($sig->getScriptSig());

        if (SigHash::V1 === $this->sigVersion) {
            $witness = [];
            for ($i = 0, $j = count($this->tx->getInputs()); $i < $j; $i++) {
                if ($i === $this->nInput) {
                    $witness[] = $sig->getScriptWitness();
                } else {
                    $witness[] = new ScriptWitness([]);
                }
            }

            $mutator->witness($witness);
        }

        return $consensus->verify($mutator->done(), $this->txOut->getScript(), $flags, $this->nInput, $this->txOut->getValue());
    }

    /**
     * @return array
     */
    private function serializeSteps()
    {
        $results = [];
        for ($i = 0, $n = count($this->steps); $i < $n; $i++) {
            $step = $this->steps[$i];

            if ($step instanceof Conditional) {
                $results[] = $step->serialize();
            } else if ($step instanceof Checksig) {
                if ($step->isRequired()) {
                    if (count($step->getSignatures()) === 0) {
                        break;
                    }
                }

                $results[] = $step->serialize($this->txSigSerializer, $this->pubKeySerializer);

                if (!$step->isFullySigned()) {
                    break;
                }
            }
        }

        $values = [];
        foreach (array_reverse($results) as $v) {
            foreach ($v as $value) {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Produces a SigValues instance containing the scriptSig & script witness
     *
     * @return SigValues
     */
    public function serializeSignatures()
    {
        static $emptyScript = null;
        static $emptyWitness = null;
        if (is_null($emptyScript) || is_null($emptyWitness)) {
            $emptyScript = new Script();
            $emptyWitness = new ScriptWitness([]);
        }

        $witness = [];
        $scriptSigChunks = $this->serializeSteps();

        $solution = $this->scriptPubKey;
        $p2sh = false;
        if ($solution->getType() === ScriptType::P2SH) {
            $p2sh = true;
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === ScriptType::P2WKH) {
            $witness = $scriptSigChunks;
            $scriptSigChunks = [];
        } else if ($solution->getType() === ScriptType::P2WSH) {
            $witness = $scriptSigChunks;
            $witness[] = $this->witnessScript->getScript()->getBuffer();
            $scriptSigChunks = [];
        }

        if ($p2sh) {
            $scriptSigChunks[] = $this->redeemScript->getScript()->getBuffer();
        }

        return new SigValues($this->pushAll($scriptSigChunks), new ScriptWitness($witness));
    }

    public function getSteps()
    {
        return $this->steps;
    }
}
