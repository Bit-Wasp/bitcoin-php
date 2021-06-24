<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPSBTException;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Types\VarString;

class PSBTInput
{
    const UTXO_TYPE_NON_WITNESS = 0;
    const UTXO_TYPE_WITNESS = 1;
    const PARTIAL_SIG = 2;
    const SIGHASH_TYPE = 3;
    const REDEEM_SCRIPT = 4;
    const WITNESS_SCRIPT = 5;
    const BIP32_DERIVATION = 6;
    const FINAL_SCRIPTSIG = 7;
    const FINAL_WITNESS = 8;

    use ParseUtil;

    /**
     * @var TransactionInterface
     */
    private $nonWitnessTx;

    /**
     * @var TransactionOutputInterface
     */
    private $witnessTxOut;

    /**
     * Map of public key binary to signature binary
     *
     * @var array
     */
    private $partialSig;

    /**
     * @var int|null
     */
    private $sigHashType;

    /**
     * @var P2shScript|null
     */
    private $redeemScript;

    /**
     * @var WitnessScript|null
     */
    private $witnessScript;

    /**
     * Array of bip32 derivations keyed by raw public key
     * @var PSBTBip32Derivation[]|null
     */
    private $bip32Derivations;

    /**
     * @var ScriptInterface|null
     */
    private $finalScriptSig;

    /**
     * @var ScriptWitnessInterface
     */
    private $finalScriptWitness;

    /**
     * Remaining PSBTInput key/value pairs we
     * didn't know how to parse. map[string]BufferInterface
     * @var BufferInterface[]
     */
    private $unknown;

    /**
     * PSBTInput constructor.
     * @param TransactionInterface|null $nonWitnessTx
     * @param TransactionOutputInterface|null $witnessTxOut
     * @param string[]|null $partialSig
     * @param int|null $sigHashType
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @param PSBTBip32Derivation[]|null $bip32Derivation
     * @param ScriptInterface|null $finalScriptSig
     * @param ScriptWitnessInterface|null $finalScriptWitness
     * @param BufferInterface[]|null $unknowns
     * @throws InvalidPSBTException
     */
    public function __construct(
        TransactionInterface $nonWitnessTx = null,
        TransactionOutputInterface $witnessTxOut = null,
        array $partialSig = null,
        int $sigHashType = null,
        ScriptInterface $redeemScript = null,
        ScriptInterface $witnessScript = null,
        array $bip32Derivation = null,
        ScriptInterface $finalScriptSig = null,
        ScriptWitnessInterface $finalScriptWitness = null,
        array $unknowns = null
    ) {
        $partialSig = $partialSig ?: [];
        $bip32Derivation = $bip32Derivation ?: [];
        $unknowns = $unknowns ?: [];
        if ($nonWitnessTx && $witnessTxOut) {
            throw new InvalidPSBTException("Cannot set non-witness tx as well as witness utxo");
        }

        foreach ($unknowns as $key => $unknown) {
            if (!is_string($key) || !($unknown instanceof BufferInterface)) {
                throw new \RuntimeException("Unknowns must be a map of string keys to Buffer values");
            }
        }

        $this->nonWitnessTx = $nonWitnessTx;
        $this->witnessTxOut = $witnessTxOut;
        $this->partialSig = $partialSig;
        $this->sigHashType = $sigHashType;
        $this->redeemScript = $redeemScript;
        $this->witnessScript = $witnessScript;
        $this->bip32Derivations = $bip32Derivation;
        $this->finalScriptSig = $finalScriptSig;
        $this->finalScriptWitness = $finalScriptWitness;
        $this->unknown = $unknowns;
    }

    /**
     * @param Parser $parser
     * @param VarString $vs
     * @return PSBTInput
     * @throws InvalidPSBTException
     */
    public static function fromParser(Parser $parser, VarString $vs): PSBTInput
    {
        $nonWitnessTx = null;
        $witTxOut = null;
        $partialSig = [];
        $sigHashType = null;
        $redeemScript = null;
        $witnessScript = null;
        $bip32Derivations = [];
        $finalScriptSig = null;
        $finalScriptWitness = null;
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
                    case self::UTXO_TYPE_NON_WITNESS:
                        // for tx / witTxOut, constructor rejects if both passed
                        if ($nonWitnessTx != null) {
                            throw new InvalidPSBTException("Duplicate non-witness tx");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $nonWitnessTx = TransactionFactory::fromBuffer($value);
                        break;
                    case self::UTXO_TYPE_WITNESS:
                        if ($witTxOut != null) {
                            throw new InvalidPSBTException("Duplicate witness txout");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $txOutSer = new TransactionOutputSerializer();
                        $witTxOut = $txOutSer->parse($value);
                        break;
                    case self::PARTIAL_SIG:
                        $pubKey = self::parsePublicKeyKey($key);
                        if (array_key_exists($pubKey->getBinary(), $partialSig)) {
                            throw new InvalidPSBTException("Duplicate partial sig");
                        }
                        $partialSig[$pubKey->getBinary()] = $value;
                        break;
                    case self::SIGHASH_TYPE:
                        if ($sigHashType !== null) {
                            throw new InvalidPSBTException("Duplicate sighash type");
                        } else if ($value->getSize() !== 4) {
                            throw new InvalidPSBTException("Sighash type must be 32 bits");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $sigHashType = unpack("N", $value->getBinary())[1];
                        break;
                    case self::REDEEM_SCRIPT:
                        if ($redeemScript !== null) {
                            throw new InvalidPSBTException("Duplicate redeemScript");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $redeemScript = new P2shScript(new Script($value));
                        break;
                    case self::WITNESS_SCRIPT:
                        if ($witnessScript !== null) {
                            throw new InvalidPSBTException("Duplicate witnessScript");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $witnessScript = new WitnessScript(new Script($value));
                        break;
                    case self::BIP32_DERIVATION:
                        $pubKey = self::parsePublicKeyKey($key);
                        if (array_key_exists($pubKey->getBinary(), $bip32Derivations)) {
                            throw new InvalidPSBTException("Duplicate derivation");
                        }
                        list ($fpr, $path) = self::parseBip32DerivationValue($value);
                        $bip32Derivations[$pubKey->getBinary()] = new PSBTBip32Derivation($pubKey, $fpr, ...$path);
                        break;
                    case self::FINAL_SCRIPTSIG:
                        if ($finalScriptWitness !== null) {
                            throw new InvalidPSBTException("Duplicate final scriptSig");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $finalScriptSig = new Script($value);
                        break;
                    case self::FINAL_WITNESS:
                        if ($finalScriptWitness !== null) {
                            throw new InvalidPSBTException("Duplicate final witness");
                        } else if ($key->getSize() !== 1) {
                            throw new InvalidPSBTException("Invalid key length");
                        }
                        $scriptWitnessSerializer = new ScriptWitnessSerializer();
                        $finalScriptWitness = $scriptWitnessSerializer->fromParser(new Parser($value));
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
            throw new InvalidPSBTException("Failed to parse input", 0, $e);
        }

        return new PSBTInput(
            $nonWitnessTx,
            $witTxOut,
            $partialSig,
            $sigHashType,
            $redeemScript,
            $witnessScript,
            $bip32Derivations,
            $finalScriptSig,
            $finalScriptWitness,
            $unknown
        );
    }

    public function hasNonWitnessTx(): bool
    {
        return null !== $this->nonWitnessTx;
    }

    public function getNonWitnessTx(): TransactionInterface
    {
        if (!$this->nonWitnessTx) {
            throw new InvalidPSBTException("Transaction not known");
        }
        return $this->nonWitnessTx;
    }

    public function hasWitnessTxOut(): bool
    {
        return null !== $this->witnessTxOut;
    }

    public function getWitnessTxOut(): TransactionOutputInterface
    {
        if (!$this->witnessTxOut) {
            throw new InvalidPSBTException("Witness txout not known");
        }
        return $this->witnessTxOut;
    }

    public function getPartialSigs(): array
    {
        return $this->partialSig;
    }

    public function haveSignatureByRawKey(BufferInterface $pubKey): bool
    {
        return array_key_exists($pubKey->getBinary(), $this->partialSig);
    }

    public function getPartialSignatureByRawKey(BufferInterface $pubKey): BufferInterface
    {
        if (!$this->haveSignatureByRawKey($pubKey)) {
            throw new InvalidPSBTException("No partial signature for that key");
        }
        return $this->partialSig[$pubKey->getBinary()];
    }

    public function getSigHashType(): int
    {
        if (null === $this->sigHashType) {
            throw new InvalidPSBTException("SIGHASH type not known");
        }
        return $this->sigHashType;
    }

    public function hasRedeemScript(): bool
    {
        return $this->redeemScript !== null;
    }

    public function getRedeemScript(): ScriptInterface
    {
        if (null === $this->redeemScript) {
            throw new InvalidPSBTException("Redeem script not known");
        }
        return $this->redeemScript;
    }

    public function hasWitnessScript(): bool
    {
        return $this->witnessScript !== null;
    }

    public function getWitnessScript(): ScriptInterface
    {
        if (null === $this->witnessScript) {
            throw new InvalidPSBTException("Witness script not known");
        }
        return $this->witnessScript;
    }

    /**
     * @return PSBTBip32Derivation[]
     */
    public function getBip32Derivations(): array
    {
        return $this->bip32Derivations;
    }

    public function getFinalizedScriptSig(): ScriptInterface
    {
        if (null === $this->finalScriptSig) {
            throw new InvalidPSBTException("Final scriptSig not known");
        }
        return $this->finalScriptSig;
    }

    public function getFinalizedScriptWitness(): ScriptWitnessInterface
    {
        if (null === $this->finalScriptWitness) {
            throw new InvalidPSBTException("Final script witness not known");
        }
        return $this->finalScriptWitness;
    }

    /**
     * @return BufferInterface[]
     */
    public function getUnknownFields(): array
    {
        return $this->unknown;
    }

    public function withNonWitnessTx(TransactionInterface $tx): self
    {
        if ($this->witnessTxOut) {
            throw new \RuntimeException("Already have witness txout");
        }
        $clone = clone $this;
        $clone->nonWitnessTx = $tx;
        return $clone;
    }

    public function withWitnessTxOut(TransactionOutputInterface $txOut): self
    {
        if ($this->nonWitnessTx) {
            throw new \RuntimeException("Already have non-witness tx");
        }
        $clone = clone $this;
        $clone->witnessTxOut = $txOut;
        return $clone;
    }

    public function withRedeemScript(ScriptInterface $script): self
    {
        if ($this->redeemScript) {
            throw new \RuntimeException("Already have redeem script");
        }
        $clone = clone $this;
        $clone->redeemScript = $script;
        return $clone;
    }

    public function withWitnessScript(ScriptInterface $script): self
    {
        if ($this->witnessScript) {
            throw new \RuntimeException("Already have witness script");
        }
        $clone = clone $this;
        $clone->witnessScript = $script;
        return $clone;
    }

    public function withDerivation(PublicKeyInterface $publicKey, PSBTBip32Derivation $derivation)
    {
        $pubKeyBin = $publicKey->getBinary();
        if (array_key_exists($pubKeyBin, $this->bip32Derivations)) {
            throw new \RuntimeException("Duplicate bip32 derivation");
        }

        $clone = clone $this;
        $clone->bip32Derivations[$pubKeyBin] = $derivation;
        return $clone;
    }

    public function writeToParser(Parser $parser, VarString $vs)
    {
        if ($this->nonWitnessTx) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::UTXO_TYPE_NON_WITNESS))));
            $parser->appendBinary($vs->write($this->nonWitnessTx->getBuffer()));
        }

        if ($this->witnessTxOut) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::UTXO_TYPE_WITNESS))));
            $parser->appendBinary($vs->write($this->witnessTxOut->getBuffer()));
        }

        foreach ($this->partialSig as $key => $value) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::PARTIAL_SIG) . $key)));
            $parser->appendBinary($vs->write($value));
        }

        if ($this->sigHashType) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::SIGHASH_TYPE))));
            $parser->appendBinary($vs->write(new Buffer(pack("N", $this->sigHashType))));
        }

        if ($this->redeemScript) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::REDEEM_SCRIPT))));
            $parser->appendBinary($vs->write($this->redeemScript->getBuffer()));
        }

        if ($this->witnessScript) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::WITNESS_SCRIPT))));
            $parser->appendBinary($vs->write($this->witnessScript->getBuffer()));
        }

        foreach ($this->bip32Derivations as $key => $value) {
            $values = $value->getPath();
            array_unshift($values, $value->getMasterKeyFpr());
            $parser->appendBinary($vs->write(new Buffer(chr(self::BIP32_DERIVATION) . $key)));
            $parser->appendBinary($vs->write(new Buffer(pack(
                str_repeat("N", count($values)),
                ...$values
            ))));
        }

        if ($this->finalScriptSig) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::FINAL_SCRIPTSIG))));
            $parser->appendBinary($vs->write($this->finalScriptSig->getBuffer()));
        }

        if ($this->finalScriptWitness) {
            $witnessSerializer = new ScriptWitnessSerializer();
            $parser->appendBinary($vs->write(new Buffer(chr(self::FINAL_WITNESS))));
            $parser->appendBinary($vs->write($witnessSerializer->serialize($this->finalScriptWitness)));
        }

        foreach ($this->unknown as $key => $value) {
            $parser->appendBinary($vs->write(new Buffer($key)));
            $parser->appendBinary($vs->write($value));
        }

        $parser->appendBinary($vs->write(new Buffer()));
    }
}
