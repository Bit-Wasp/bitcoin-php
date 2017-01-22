<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
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
use BitWasp\Bitcoin\Script\ScriptType;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Serializer\Signature\TransactionSignatureSerializer;
use BitWasp\Bitcoin\Signature\TransactionSignature;
use BitWasp\Bitcoin\Signature\TransactionSignatureInterface;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
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
     * @var Interpreter
     */
    private $interpreter;

    /**
     * @var Checker
     */
    private $signatureChecker;

    /**
     * InputSigner constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @param $nInput
     * @param TransactionOutputInterface $txOut
     * @param SignData $signData
     * @param TransactionSignatureSerializer|null $sigSerializer
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, SignData $signData, TransactionSignatureSerializer $sigSerializer = null, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        $inputs = $tx->getInputs();
        if (!isset($inputs[$nInput])) {
            throw new \RuntimeException('No input at this index');
        }

        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->flags = $signData->hasSignaturePolicy() ? $signData->getSignaturePolicy() : Interpreter::VERIFY_NONE;
        $this->publicKeys = [];
        $this->signatures = [];

        $this->txSigSerializer = $sigSerializer ?: new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, $ecAdapter));
        $this->pubKeySerializer = $pubKeySerializer ?: EcSerializer::getSerializer(PublicKeySerializerInterface::class, $ecAdapter);
        $this->signatureChecker = new Checker($this->ecAdapter, $this->tx, $nInput, $txOut->getValue(), $this->txSigSerializer, $this->pubKeySerializer);
        $this->interpreter = new Interpreter($this->ecAdapter);

        $this->solve($signData, $txOut->getScript(), $inputs[$nInput]->getScript(), isset($tx->getWitnesses()[$nInput]) ? $tx->getWitnesses()[$nInput]->all() : []);
    }

    /**
     * A snipped from OP_CHECKMULTISIG - verifies signatures according to the
     * order of the given public keys (taken from the script).
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

            $first = ord($buffer->getBinary());
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
     * Evaluates a scriptPubKey against the provided chunks.
     *
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
     *
     * @param OutputData $outputData
     * @param array $stack
     * @param int $sigVersion
     * @return string
     */
    public function extractFromValues(OutputData $outputData, array $stack, $sigVersion)
    {
        $type = $outputData->getType();
        $size = count($stack);

        if ($type === ScriptType::P2PKH) {
            $this->requiredSigs = 1;
            if ($size === 2) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
                $this->signatures = [$this->txSigSerializer->parse($stack[0])];
                $this->publicKeys = [$this->pubKeySerializer->parse($stack[1])];
            }
        } else if ($type === ScriptType::P2PK) {
            $this->requiredSigs = 1;
            if ($size === 1) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }
                $this->signatures = [$this->txSigSerializer->parse($stack[0])];
            }
            $this->publicKeys = [$this->pubKeySerializer->parse($outputData->getSolution())];
        } else if ($type === ScriptType::MULTISIG) {
            $info = new Multisig($outputData->getScript(), $this->pubKeySerializer);
            $this->requiredSigs = $info->getRequiredSigCount();
            $this->publicKeys = $info->getKeys();
            $keyBufs = $info->getKeyBuffers();
            if ($size > 1) {
                // Check signatures irrespective of scriptSig size, primes Checker cache, and need info
                $check = $this->evaluateSolution($outputData->getScript(), $stack, $sigVersion);
                $sigBufs = array_slice($stack, 1, $size - 1);
                $sigBufCount = count($sigBufs);

                // If we seem to have all signatures but fail evaluation, abort
                if ($sigBufCount === $this->requiredSigs && !$check) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }

                $keyToSigMap = $this->sortMultiSigs($outputData->getScript(), $sigBufs, $keyBufs, $sigVersion);

                // Here we learn if any signatures were invalid, it won't be in the map.
                if ($sigBufCount !== count($keyToSigMap)) {
                    throw new \RuntimeException('Existing signatures are invalid!');
                }

                foreach ($keyBufs as $idx => $key) {
                    if (isset($keyToSigMap[$key])) {
                        $this->signatures[$idx] = $this->txSigSerializer->parse($keyToSigMap[$key]);
                    }
                }
            }
        } else {
            throw new \RuntimeException('Unsupported output type passed to extractFromValues');
        }

        return $type;
    }

    /**
     * @param BufferInterface[] $chunks
     * @param SignData $signData
     * @return ScriptInterface
     */
    private function findRedeemScript(array $chunks, SignData $signData)
    {
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

        return $redeemScript;
    }

    /**
     * @param BufferInterface[] $witness
     * @param SignData $signData
     * @return ScriptInterface
     */
    private function findWitnessScript(array $witness, SignData $signData)
    {
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

        return $witnessScript;
    }

    /**
     * Called upon instance creation.
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
        $sigChunks = [];
        $solution = $this->scriptPubKey = $classifier->decode($scriptPubKey);
        if ($solution->getType() !== ScriptType::P2SH && !in_array($solution->getType(), self::$validP2sh)) {
            throw new \RuntimeException('scriptPubKey not supported');
        }

        if ($solution->canSign()) {
            $sigChunks = $this->evalPushOnly($scriptSig);
        }

        if ($solution->getType() === ScriptType::P2SH) {
            $chunks = $this->evalPushOnly($scriptSig);
            $redeemScript = $this->findRedeemScript($chunks, $signData);
            if (!$this->verifySolution(Interpreter::VERIFY_SIGPUSHONLY, ScriptFactory::sequence([$redeemScript->getBuffer()]), $solution->getScript())) {
                throw new \RuntimeException('Redeem script fails to solve pay-to-script-hash');
            }

            $solution = $this->redeemScript = $classifier->decode($redeemScript);
            if (!in_array($solution->getType(), self::$validP2sh)) {
                throw new \RuntimeException('Unsupported pay-to-script-hash script');
            }

            $sigChunks = array_slice($chunks, 0, -1);
        }

        if ($solution->getType() === ScriptType::P2WKH) {
            $sigVersion = SigHash::V1;
            $solution = $this->witnessKeyHash = $classifier->decode(ScriptFactory::scriptPubKey()->payToPubKeyHash($solution->getSolution()));
            $sigChunks = $witness;
        } else if ($solution->getType() === ScriptType::P2WSH) {
            $sigVersion = SigHash::V1;
            $witnessScript = $this->findWitnessScript($witness, $signData);

            // Essentially all the reference implementation does
            if (!$witnessScript->getWitnessScriptHash()->equals($solution->getSolution())) {
                throw new \RuntimeException('Witness script fails to solve witness-script-hash');
            }

            $solution = $this->witnessScript = $classifier->decode($witnessScript);
            if (!in_array($this->witnessScript->getType(), self::$canSign)) {
                throw new \RuntimeException('Unsupported witness-script-hash script');
            }

            $sigChunks = array_slice($witness, 0, -1);
        }

        $this->sigVersion = $sigVersion;
        $this->signScript = $solution;

        $this->extractFromValues($solution, $sigChunks, $this->sigVersion);

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

        return $this->signatureChecker->getSigHash($scriptCode, $sigHashType, $sigVersion);
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
     * Sign the input using $key and $sigHashTypes
     *
     * @param PrivateKeyInterface $key
     * @param int $sigHashType
     * @return $this
     */
    public function sign(PrivateKeyInterface $key, $sigHashType = SigHash::ALL)
    {
        if ($this->isFullySigned()) {
            return $this;
        }

        if ($this->sigVersion === 1 && !$key->isCompressed()) {
            throw new \RuntimeException('Uncompressed keys are disallowed in segwit scripts - refusing to sign');
        }

        if ($this->signScript->getType() === ScriptType::P2PK) {
            if (!$this->pubKeySerializer->serialize($key->getPublicKey())->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
        } else if ($this->signScript->getType() === ScriptType::P2PKH) {
            if (!$key->getPubKeyHash($this->pubKeySerializer)->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
            $this->publicKeys[0] = $key->getPublicKey();
        } else if ($this->signScript->getType() === ScriptType::MULTISIG) {
            $info = new Multisig($this->signScript->getScript(), $this->pubKeySerializer);

            $signed = false;
            foreach ($info->getKeys() as $keyIdx => $publicKey) {
                if ($key->getPublicKey()->equals($publicKey)) {
                    $this->signatures[$keyIdx] = $this->calculateSignature($key, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
                    $signed = true;
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
        if ($this->sigVersion === 1) {
            $flags |= Interpreter::VERIFY_WITNESS;
        }

        $sig = $this->serializeSignatures();

        // Take serialized signatures, and use mutator to add this inputs sig data
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
     * Produces the script stack that solves the $outputType
     *
     * @param string $outputType
     * @return BufferInterface[]
     */
    private function serializeSolution($outputType)
    {
        $result = [];
        if ($outputType === ScriptType::P2PK) {
            if (count($this->signatures) === 1) {
                $result = [$this->txSigSerializer->serialize($this->signatures[0])];
            }
        } else if ($outputType === ScriptType::P2PKH) {
            if (count($this->signatures) === 1 && count($this->publicKeys) === 1) {
                $result = [$this->txSigSerializer->serialize($this->signatures[0]), $this->pubKeySerializer->serialize($this->publicKeys[0])];
            }
        } else if ($outputType === ScriptType::MULTISIG) {
            $result[] = new Buffer();
            for ($i = 0, $nPubKeys = count($this->publicKeys); $i < $nPubKeys; $i++) {
                if (isset($this->signatures[$i])) {
                    $result[] = $this->txSigSerializer->serialize($this->signatures[$i]);
                }
            }
        } else {
            throw new \RuntimeException('Parameter 0 for serializeSolution was a non-standard input type');
        }

        return $result;
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

        $scriptSigChunks = [];
        $witness = [];
        if ($this->scriptPubKey->canSign()) {
            $scriptSigChunks = $this->serializeSolution($this->scriptPubKey->getType());
        }

        $solution = $this->scriptPubKey;
        $p2sh = false;
        if ($solution->getType() === ScriptType::P2SH) {
            $p2sh = true;
            if ($this->redeemScript->canSign()) {
                $scriptSigChunks = $this->serializeSolution($this->redeemScript->getType());
            }
            $solution = $this->redeemScript;
        }

        if ($solution->getType() === ScriptType::P2WKH) {
            $witness = $this->serializeSolution(ScriptType::P2PKH);
        } else if ($solution->getType() === ScriptType::P2WSH) {
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
