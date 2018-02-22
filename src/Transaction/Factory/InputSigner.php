<?php

namespace BitWasp\Bitcoin\Transaction\Factory;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcSerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface;
use BitWasp\Bitcoin\Crypto\Random\Rfc6979;
use BitWasp\Bitcoin\Exceptions\SignerException;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Script\Classifier\OutputData;
use BitWasp\Bitcoin\Script\Interpreter\CheckerBase;
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
    private $tolerateInvalidPublicKey = false;

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
     * @var CheckerBase
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
     * @param CheckerBase $checker
     * @param TransactionSignatureSerializer|null $sigSerializer
     * @param PublicKeySerializerInterface|null $pubKeySerializer
     */
    public function __construct(EcAdapterInterface $ecAdapter, TransactionInterface $tx, $nInput, TransactionOutputInterface $txOut, SignData $signData, CheckerBase $checker, TransactionSignatureSerializer $sigSerializer = null, PublicKeySerializerInterface $pubKeySerializer = null)
    {
        $this->ecAdapter = $ecAdapter;
        $this->tx = $tx;
        $this->nInput = $nInput;
        $this->txOut = $txOut;
        $this->signData = $signData;
        $defaultFlags = Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY | Interpreter::VERIFY_CHECKSEQUENCEVERIFY | Interpreter::VERIFY_WITNESS;
        $this->flags = $this->signData->hasSignaturePolicy() ? $this->signData->getSignaturePolicy() : $defaultFlags;
        $this->publicKeys = [];
        $this->signatures = [];
        $this->signatureChecker = $checker;
        $this->txSigSerializer = $sigSerializer ?: new TransactionSignatureSerializer(EcSerializer::getSerializer(DerSignatureSerializerInterface::class, true, $ecAdapter));
        $this->pubKeySerializer = $pubKeySerializer ?: EcSerializer::getSerializer(PublicKeySerializerInterface::class, true, $ecAdapter);
        $this->interpreter = new Interpreter($this->ecAdapter);
    }

    /**
     * @return InputSigner
     */
    public function extract()
    {
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
    public function tolerateInvalidPublicKey($setting)
    {
        $this->tolerateInvalidPublicKey = (bool) $setting;
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
     * A snipped from OP_CHECKMULTISIG - verifies signatures according to the
     * order of the given public keys (taken from the script).
     *
     * @param ScriptInterface $script
     * @param BufferInterface[] $signatures
     * @param BufferInterface[] $publicKeys
     * @param int $sigVersion
     * @return \SplObjectStorage
     * @throws \BitWasp\Bitcoin\Exceptions\ScriptRuntimeException
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
     * @throws SignerException
     * @throws \Exception
     */
    public function extractFromValues(OutputData $outputData, array $stack, $sigVersion)
    {
        $type = $outputData->getType();
        $size = count($stack);

        if (ScriptType::P2PKH === $type) {
            $this->requiredSigs = 1;
            if ($size === 2) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new SignerException('Existing signatures are invalid!');
                }
                $this->signatures = [$this->txSigSerializer->parse($stack[0])];
                $this->publicKeys = [$this->parseStepPublicKey($stack[1])];
            }
        } else if (ScriptType::P2PK === $type) {
            $this->requiredSigs = 1;
            if ($size === 1) {
                if (!$this->evaluateSolution($outputData->getScript(), $stack, $sigVersion)) {
                    throw new SignerException('Existing signatures are invalid!');
                }
                $this->signatures = [$this->txSigSerializer->parse($stack[0])];
            }
            $this->publicKeys = [$this->parseStepPublicKey($outputData->getSolution())];
        } else if (ScriptType::MULTISIG === $type) {
            $info = new Multisig($outputData->getScript(), $this->pubKeySerializer);
            $this->requiredSigs = $info->getRequiredSigCount();

            $keyBuffers = $info->getKeyBuffers();
            $this->publicKeys = [];
            for ($i = 0; $i < $info->getKeyCount(); $i++) {
                $this->publicKeys[$i] = $this->parseStepPublicKey($keyBuffers[$i]);
            }

            if ($size > 1) {
                // Check signatures irrespective of scriptSig size, primes Checker cache, and need info
                $check = $this->evaluateSolution($outputData->getScript(), $stack, $sigVersion);
                $sigBufs = array_slice($stack, 1, $size - 1);
                $sigBufCount = count($sigBufs);

                // If we seem to have all signatures but fail evaluation, abort
                if ($sigBufCount === $this->requiredSigs && !$check) {
                    throw new SignerException('Existing signatures are invalid!');
                }

                $keyToSigMap = $this->sortMultiSigs($outputData->getScript(), $sigBufs, $keyBuffers, $sigVersion);

                // Here we learn if any signatures were invalid, it won't be in the map.
                if ($sigBufCount !== count($keyToSigMap)) {
                    throw new SignerException('Existing signatures are invalid!');
                }

                foreach ($keyBuffers as $idx => $key) {
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
     * @throws SignerException
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
     * Pure function to produce a signature hash for a given $scriptCode, $sigHashType, $sigVersion.
     *
     * @param ScriptInterface $scriptCode
     * @param $sigHashType
     * @param $sigVersion
     * @return BufferInterface
     * @throws SignerException
     */
    public function calculateSigHashUnsafe(ScriptInterface $scriptCode, $sigHashType, $sigVersion)
    {
        if (!$this->signatureChecker->isDefinedHashtype($sigHashType)) {
            throw new SignerException('Invalid sigHashType requested');
        }

        return $this->signatureChecker->getSigHash($scriptCode, $sigHashType, $sigVersion);
    }

    /**
     * Calculates the signature hash for the input for the given $sigHashType.
     *
     * @param int $sigHashType
     * @return BufferInterface
     * @throws SignerException
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
     * @throws SignerException
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
        return $this->requiredSigs !== 0 && $this->requiredSigs === count($this->signatures);
    }

    /**
     * Returns the required number of signatures for this input.
     *
     * @return int
     */
    public function getRequiredSigs()
    {
        return $this->requiredSigs;
    }

    /**
     * Returns an array where the values are either null,
     * or a TransactionSignatureInterface.
     *
     * @return TransactionSignatureInterface[]
     */
    public function getSignatures()
    {
        return $this->signatures;
    }

    /**
     * Returns an array where the values are either null,
     * or a PublicKeyInterface.
     *
     * @return PublicKeyInterface[]
     */
    public function getPublicKeys()
    {
        return $this->publicKeys;
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
     * Sign the input using $key and $sigHashTypes
     *
     * @param PrivateKeyInterface $privateKey
     * @param int $sigHashType
     * @return $this
     * @throws SignerException
     */
    public function sign(PrivateKeyInterface $privateKey, $sigHashType = SigHash::ALL)
    {
        if ($this->isFullySigned()) {
            return $this;
        }

        if (SigHash::V1 === $this->sigVersion && !$privateKey->isCompressed()) {
            throw new \RuntimeException('Uncompressed keys are disallowed in segwit scripts - refusing to sign');
        }

        if ($this->signScript->getType() === ScriptType::P2PK) {
            if (!$this->pubKeySerializer->serialize($privateKey->getPublicKey())->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }
            $this->signatures[0] = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
        } else if ($this->signScript->getType() === ScriptType::P2PKH) {
            $publicKey = $privateKey->getPublicKey();
            if (!$publicKey->getPubKeyHash()->equals($this->signScript->getSolution())) {
                throw new \RuntimeException('Signing with the wrong private key');
            }

            if (!array_key_exists(0, $this->signatures)) {
                $this->signatures[0] = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
            }

            $this->publicKeys[0] = $publicKey;
        } else if ($this->signScript->getType() === ScriptType::MULTISIG) {
            $signed = false;
            foreach ($this->publicKeys as $keyIdx => $publicKey) {
                if ($publicKey instanceof PublicKeyInterface) {
                    if ($privateKey->getPublicKey()->equals($publicKey)) {
                        $this->signatures[$keyIdx] = $this->calculateSignature($privateKey, $this->signScript->getScript(), $sigHashType, $this->sigVersion);
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
     * Produces the script stack that solves the $outputType
     *
     * @param string $outputType
     * @return BufferInterface[]
     */
    private function serializeSolution($outputType)
    {
        $result = [];
        if (ScriptType::P2PK === $outputType) {
            if (count($this->signatures) === 1) {
                $result = [$this->txSigSerializer->serialize($this->signatures[0])];
            }
        } else if (ScriptType::P2PKH === $outputType) {
            if (count($this->signatures) === 1 && count($this->publicKeys) === 1) {
                $result = [$this->txSigSerializer->serialize($this->signatures[0]), $this->pubKeySerializer->serialize($this->publicKeys[0])];
            }
        } else if (ScriptType::MULTISIG === $outputType) {
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
