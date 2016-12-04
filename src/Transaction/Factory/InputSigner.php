<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Stack;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInfo\Multisig;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Signature\SignatureSort;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureFactory;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class InputSigner
{
    /**
     * @var array
     */
    protected static $canSign = [
        OutputClassifier::PAYTOPUBKEYHASH,
        OutputClassifier::PAYTOPUBKEY,
        OutputClassifier::MULTISIG
    ];

    /**
     * @var array
     */
    protected static $validP2sh = [
        OutputClassifier::WITNESS_V0_KEYHASH,
        OutputClassifier::WITNESS_V0_SCRIPTHASH,
        OutputClassifier::PAYTOPUBKEYHASH,
        OutputClassifier::PAYTOPUBKEY,
        OutputClassifier::MULTISIG
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
     * @var int
     */
    private $sigVersion;

    /**
     * @var OutputData $witnessKeyHash
     */
    private $witnessKeyHash;

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
     * @var PublicKeyInterface[]
     */
    private $publicKeys = [];

    /**
     * @var TransactionSignatureInterface[]
     */
    private $signatures = [];

    /**
     * @var int
     */
    private $requiredSigs = 0;

    /**
     * @var OutputClassifier
     */
    private $classifier;

    /**
     * @var Interpreter
     */
    private $interpreter;

    /**
     * @var Checker
     */
    private $signatureChecker;

    /**
     * TxInputSigning constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @param int $nInput
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, SignData $signData)
    {
        $inputs = $tx->getInputs();
        if (!isset($inputs[$nInput])) {
            throw new \RuntimeException('No input at this index');
        }

        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->classifier = new OutputClassifier();
        $this->interpreter = new Interpreter();
        $this->signatureChecker = new Checker($this->ecAdapter, $this->tx, $nInput, $txOut->getValue());
        $this->flags = $signData->hasSignaturePolicy() ? $signData->getSignaturePolicy() : Interpreter::VERIFY_NONE;
        $this->publicKeys = [];
        $this->signatures = [];

        $scriptSig = $inputs[$nInput]->getScript();
        $witness = isset($tx->getWitnesses()[$nInput]) ? $tx->getWitnesses()[$nInput]->all() : [];

        $this->solve($signData, $txOut->getScript(), $scriptSig, $witness);
    }

    /**
     * @param TransactionSignatureInterface[] $stack
     * @param PublicKeyInterface[] $publicKeys
     * @return \SplObjectStorage
     */
    private function sortMultiSigs($stack, array $publicKeys)
    {
        $sigSort = new SignatureSort($this->ecAdapter);
        $sigs = new \SplObjectStorage;

        foreach ($stack as $txSig) {
            $hash = $this->getSigHash($txSig->getHashType());
            $linked = $sigSort->link([$txSig->getSignature()], $publicKeys, $hash);
            foreach ($publicKeys as $key) {
                if ($linked->contains($key)) {
                    $sigs[$key] = $txSig;
                }
            }
        }

        return $sigs;
    }

    /**
     * @param ScriptInterface $script
     * @return \BitWasp\Buffertools\BufferInterface[]
     */
    private function evalPushOnly(ScriptInterface $script)
    {
        $stack = new Stack();
        $interpreter = new Interpreter();
        $interpreter->evaluate($script, $stack, SigHash::V0, $this->flags | Interpreter::VERIFY_SIGPUSHONLY, $this->signatureChecker);
        return $stack->all();
    }

    /**
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

            $first = ord($buffer->getBinary());
            if ($size === 1 && $first >= 1 && $first <= 16) {
                return \BitWasp\Bitcoin\Script\encodeOpN($first);
            } else {
                return $buffer;
            }
        }, $buffers));
    }

    /**
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
     * @param ScriptInterface $scriptPubKey
     * @param array $chunks
     * @param int $sigVersion
     * @return bool
     */
    private function evaluateSolution(ScriptInterface $scriptPubKey, array $chunks, $sigVersion)
    {
        $stack = new Stack($chunks);
        if (!$this->interpreter->evaluate($scriptPubKey, $stack, $sigVersion, $this->flags, $this->signatureChecker)) {
            return false;
        }

        if ($stack->isEmpty()) {
            return false;
        }

        if (false === $this->interpreter->castToBool($stack[-1])) {
            return false;
        }

        return true;
    }

    /**
     * This function is strictly for $canSign types.
     * It will extract signatures/publicKeys when given $outputData, and $stack.
     * $stack is the result of decompiling a scriptSig, or taking the witness data.
     * @param OutputData $outputData
     * @param array $stack
     * @param $sigVersion
     * @return mixed
     */
    public function extractFromValues(OutputData $outputData, array $stack, $sigVersion)
    {
        $type = $outputData->getType();
        $size = count($stack);
        if ($type === OutputClassifier::PAYTOPUBKEYHASH) {
            $this->requiredSigs = 1;
            if ($size === 2) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
                $this->signatures = [TransactionSignatureFactory::fromHex($stack[0], $this->ecAdapter)];
                $this->publicKeys = [PublicKeyFactory::fromHex($stack[1], $this->ecAdapter)];
            }
        }

        if ($type === OutputClassifier::PAYTOPUBKEY && count($stack) === 1) {
            $this->requiredSigs = 1;
            if ($size === 1) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }

                $this->signatures = [TransactionSignatureFactory::fromHex($stack[0], $this->ecAdapter)];
                $this->publicKeys = [PublicKeyFactory::fromHex($outputData->getSolution())];
            }
        }

        if ($type === OutputClassifier::MULTISIG) {
            $info = new Multisig($outputData->getScript());
            $this->requiredSigs = $info->getRequiredSigCount();
            $this->publicKeys = $info->getKeys();
            if ($size > 1) {
                $vars = [];
                for ($i = 1, $j = $size - 1; $i <= $j; $i++) {
                    $vars[] = TransactionSignatureFactory::fromHex($stack[$i], $this->ecAdapter);
                }

                $this->signatures = array_fill(0, count($this->publicKeys), null);
                $sigs = $this->sortMultiSigs($vars, $this->publicKeys);
                $count = 0;
                foreach ($this->publicKeys as $idx => $key) {
                    if (isset($sigs[$key])) {
                        $this->signatures[$idx] = $sigs[$key];
                        $count++;
                    }
                }

                if (count($vars) !== $count) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
                // Don't evaluate, already checked sigs during sort. Todo: fix this.
            }
        }

        return $type;
    }

    /**
     * Called upon instance creation.
     * This function must throw an exception whenever execution
     * does not yield a signable script.
     *
     * It ensures:
     *  - the scriptPubKey can be directly signed, or leads to P2SH/P2WSH/P2WKH
     *  - the P2SH script covers signable types and P2WSH/P2WKH
     *  - the witnessScript covers signable types only
     *  - violating the above prevents instance creation
     * @param SignData $signData
     * @param ScriptInterface $scriptPubKey
     * @param ScriptInterface $scriptSig
     * @param BufferInterface[] $witness
     * @return $this
     * @throws \Exception
     */
    private function solve(SignData $signData, ScriptInterface $scriptPubKey, ScriptInterface $scriptSig, array $witness)
    {
        $sigVersion = SigHash::V0;
        $sigChunks = [];

        $solution = $this->scriptPubKey = $this->classifier->decode($scriptPubKey);
        if ($solution->getType() !== OutputClassifier::PAYTOSCRIPTHASH && !in_array($solution->getType(), self::$validP2sh)) {
            throw new \RuntimeException('scriptPubKey not supported');
        }

        if ($solution->canSign()) {
            $sigChunks = $this->evalPushOnly($scriptSig);
        }

        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $chunks = $this->evalPushOnly($scriptSig);
            $redeemScript = null;
            if (count($chunks) > 0) {
                $redeemScript = new Script($chunks[count($chunks) - 1]);
            } else {
                if (!$signData->hasRedeemScript()) {
                    throw new \RuntimeException('Redeem script not provided in sign data or scriptSig');
                }
            }

            if ($signData->hasRedeemScript()) {
                if ($redeemScript === null) {
                    $redeemScript = $signData->getRedeemScript();
                }

                if (!$redeemScript->equals($signData->getRedeemScript())) {
                    throw new \RuntimeException('Extracted redeemScript did not match sign data');
                }
            }

            if (!$this->verifySolution(Interpreter::VERIFY_SIGPUSHONLY, ScriptFactory::sequence([$redeemScript->getBuffer()]), $solution->getScript())) {
                throw new \RuntimeException('Redeem script fails to solve pay-to-script-hash');
            }

            $solution = $this->redeemScript = $this->classifier->decode($redeemScript);
            if (!in_array($solution->getType(), self::$validP2sh)) {
                throw new \RuntimeException('Unsupported pay-to-script-hash script');
            }

            $sigChunks = array_slice($chunks, 0, -1);
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $sigVersion = SigHash::V1;
            $solution = $this->witnessKeyHash = $this->classifier->decode(ScriptFactory::scriptPubKey()->payToPubKeyHash($solution->getSolution()));
            $sigChunks = $witness;
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            $sigVersion = SigHash::V1;

            $witnessScript = null;
            if (count($witness) > 0) {
                $witnessScript = new Script($witness[count($witness) - 1]);
            } else {
                if (!$signData->hasWitnessScript()) {
                    throw new \RuntimeException('Witness script not provided in sign data or witness');
                }
            }

            if ($signData->hasWitnessScript()) {
                if ($witnessScript === null) {
                    $witnessScript = $signData->getWitnessScript();
                } else {
                    if (!$witnessScript->equals($signData->getWitnessScript())) {
                        throw new \RuntimeException('Extracted witnessScript did not match sign data');
                    }
                }
            }

            // Essentially all the reference implementation does
            if (!$witnessScript->getWitnessScriptHash()->equals($solution->getSolution())) {
                throw new \RuntimeException('Witness script fails to solve witness-script-hash');
            }

            $solution = $this->witnessScript = $this->classifier->decode($witnessScript);
            if (!in_array($this->witnessScript->getType(), self::$canSign)) {
                throw new \RuntimeException('Unsupported witness-script-hash script');
            }

            $sigChunks = array_slice($witness, 0, -1);
        }

        $this->sigVersion = $sigVersion;
        $this->signScript = $solution;

        $this->extractFromValues($solution, $sigChunks, $sigVersion);

        return $this;
    }

    /**
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

        if ($sigVersion === SigHash::V1) {
            $hasher = new V1Hasher($this->tx, $this->txOut->getValue());
        } else {
            $hasher = new Hasher($this->tx);
        }

        return $hasher->calculate($scriptCode, $this->nInput, $sigHashType);
    }

    /**
     * @param int $sigHashType
     * @return BufferInterface
     */
    public function getSigHash($sigHashType)
    {
        return $this->calculateSigHashUnsafe($this->signScript->getScript(), $sigHashType, $this->sigVersion);
    }

    /**
     * @param PrivateKeyInterface $key
     * @param ScriptInterface $scriptCode
     * @param int $sigHashType
     * @param int $sigVersion
     * @return TransactionSignature
     */
    private function calculateSignature(PrivateKeyInterface $key, ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        $hash = $this->calculateSigHashUnsafe($scriptCode, $sigHashType, $sigVersion);
        $ecSignature = $this->ecAdapter->sign($hash, $key, new Rfc6979($this->ecAdapter, $key, $hash, 'sha256'));
        return new TransactionSignature($this->ecAdapter, $ecSignature, $sigHashType);
    }

    /**
     * @return bool
     */
    public function isFullySigned()
    {
        return $this->requiredSigs !== 0 && $this->requiredSigs === count($this->signatures);
    }

    /**
     * @return int
     */
    public function getRequiredSigs()
    {
        return $this->requiredSigs;
    }

    /**
     * @return TransactionSignatureInterface[]
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * @return PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
    }

    /**
     * @param PrivateKeyInterface $key
     * @param int $sigHashType
     * @return $this
     */
    public function sign(PrivateKeyInterface $key, $sigHashType = SigHash::ALL)
    {
        if ($this->isFullySigned()) {
            return $this;
        }

        if ($this->signScript->getType() === OutputClassifier::PAYTOPUBKEY) {
            if (!$key->getPublicKey()->getBuffer()->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
            $this->publicKeys[0] = $key->getPublicKey();
            $this->requiredSigs = 1;
        } else if ($this->signScript->getType() === OutputClassifier::PAYTOPUBKEYHASH) {
            if (!$key->getPubKeyHash()->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
            $this->publicKeys[0] = $key->getPublicKey();
            $this->requiredSigs = 1;
        } else if ($this->signScript->getType() === OutputClassifier::MULTISIG) {
            $info = new Multisig($this->signScript->getScript());
            $this->publicKeys = $info->getKeys();
            $this->requiredSigs = $info->getRequiredSigCount();

            $myKey = $key->getPublicKey()->getBuffer();
            $signed = false;
            foreach ($info->getKeys() as $keyIdx => $publicKey) {
                if ($myKey->equals($publicKey->getBuffer())) {
                    $this->signatures[$keyIdx] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
                    $signed = true;
                }
            }

            if (!$signed) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
        }

        return $this;
    }

    /**
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
        if ($this->sigVersion === 1) {
            $flags |= Interpreter::VERIFY_WITNESS;
        }

        $sig = $this->serializeSignatures();

        $mutator = TransactionFactory::mutate($this->tx);
        $mutator->inputsMutator()[$this->nInput]->script($sig->getScriptSig());
        if ($this->sigVersion === 1) {
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
     * @param string $outputType
     * @return BufferInterface[]
     */
    private function serializeSolution($outputType)
    {
        $result = [];
        if ($outputType === OutputClassifier::PAYTOPUBKEY) {
            if (count($this->signatures) === 1) {
                $result = [$this->signatures[0]->getBuffer()];
            }
        } else if ($outputType === OutputClassifier::PAYTOPUBKEYHASH) {
            if (count($this->signatures) === 1 && count($this->publicKeys) === 1) {
                $result = [$this->signatures[0]->getBuffer(), $this->publicKeys[0]->getBuffer()];
            }
        } else if ($outputType === OutputClassifier::MULTISIG) {
            $result[] = new Buffer();
            for ($i = 0, $nPubKeys = count($this->publicKeys); $i < $nPubKeys; $i++) {
                if (isset($this->signatures[$i])) {
                    $result[] = $this->signatures[$i]->getBuffer();
                }
            }
        }

        return $result;
    }

    /**
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

        $scriptSigChunks = [];
        $witness = [];
        if ($this->scriptPubKey->canSign()) {
            $scriptSigChunks = $this->serializeSolution($this->scriptPubKey->getType());
        }

        $solution = $this->scriptPubKey;
        $p2sh = false;
        if ($solution->getType() === OutputClassifier::PAYTOSCRIPTHASH) {
            $p2sh = true;
            if ($this->redeemScript->canSign()) {
                $scriptSigChunks = $this->serializeSolution($this->redeemScript->getType());
            }
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === OutputClassifier::WITNESS_V0_KEYHASH) {
            $witness = $this->serializeSolution(OutputClassifier::PAYTOPUBKEYHASH);
        } else if ($solution->getType() === OutputClassifier::WITNESS_V0_SCRIPTHASH) {
            if ($this->witnessScript->canSign()) {
                $witness = $this->serializeSolution($this->witnessScript->getType());
                $witness[] = $this->witnessScript->getScript()->getBuffer();
            }
        }

        if ($p2sh) {
            $scriptSigChunks[] = $this->redeemScript->getScript()->getBuffer();
        }

        return new SigValues($this->pushAll($scriptSigChunks), new ScriptWitness($witness));
    }
}
