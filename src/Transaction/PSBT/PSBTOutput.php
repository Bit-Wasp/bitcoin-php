<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Transaction\PSBT;

use BitWasp\Bitcoin\Exceptions\InvalidPSBTException;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Types\VarString;

class PSBTOutput
{
    const REDEEM_SCRIPT = 0;
    const WITNESS_SCRIPT = 1;
    const BIP32_DERIVATION = 2;

    use ParseUtil;

    /**
     * @var ScriptInterface
     */
    private $redeemScript;

    /**
     * @var ScriptInterface
     */
    private $witnessScript;

    /**
     * @var PSBTBip32Derivation[]
     */
    private $bip32Derivations;

    /**
     * Remaining PSBTOutput key/value pairs we
     * didn't know how to parse. map[string]BufferInterface
     * @var BufferInterface[]
     */
    private $unknown;

    /**
     * PSBTOutput constructor.
     * @param ScriptInterface|null $redeemScript
     * @param ScriptInterface|null $witnessScript
     * @param PSBTBip32Derivation[] $bip32Derivations
     * @param BufferInterface[] $unknowns
     */
    public function __construct(
        ScriptInterface $redeemScript = null,
        ScriptInterface $witnessScript = null,
        array $bip32Derivations = [],
        array $unknowns = []
    ) {
        foreach ($unknowns as $key => $unknown) {
            if (!is_string($key) || !($unknown instanceof BufferInterface)) {
                throw new \RuntimeException("Unknowns must be a map of string keys to Buffer values");
            }
        }

        $this->redeemScript = $redeemScript;
        $this->witnessScript = $witnessScript;
        $this->bip32Derivations = $bip32Derivations;
        $this->unknown = $unknowns;
    }

    /**
     * @param Parser $parser
     * @param VarString $vs
     * @return PSBTOutput
     * @throws InvalidPSBTException
     * @throws \BitWasp\Bitcoin\Exceptions\P2shScriptException
     * @throws \BitWasp\Bitcoin\Exceptions\WitnessScriptException
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public static function fromParser(Parser $parser, VarString $vs): PSBTOutput
    {
        $redeemScript = null;
        $witnessScript = null;
        $bip32Derivations = [];
        $unknown = [];

        do {
            $key = $vs->read($parser);
            if ($key->getSize() === 0) {
                break;
            }
            $value = $vs->read($parser);
            // Assumes no zero length keys, and no duplicates
            $dataType = ord(substr($key->getBinary(), 0, 1));
            switch ($dataType) {
                case self::REDEEM_SCRIPT:
                    if ($redeemScript != null) {
                        throw new InvalidPSBTException("Duplicate redeem script");
                    } else if ($key->getSize() !== 1) {
                        throw new InvalidPSBTException("Invalid key length");
                    }
                    $redeemScript = new P2shScript(ScriptFactory::fromBuffer($value));
                    // value: must be bytes
                    break;
                case self::WITNESS_SCRIPT:
                    if ($witnessScript != null) {
                        throw new InvalidPSBTException("Duplicate redeem script");
                    } else if ($key->getSize() !== 1) {
                        throw new InvalidPSBTException("Invalid key length");
                    }
                    $witnessScript = new WitnessScript(ScriptFactory::fromBuffer($value));
                    // value: must be bytes
                    break;
                case self::BIP32_DERIVATION:
                    $pubKey = self::parsePublicKeyKey($key);
                    if (array_key_exists($pubKey->getBinary(), $bip32Derivations)) {
                        throw new InvalidPSBTException("Duplicate derivation");
                    }
                    list ($fpr, $path) = self::parseBip32DerivationValue($value);
                    $bip32Derivations[$pubKey->getBinary()] = new PSBTBip32Derivation($pubKey, $fpr, ...$path);
                    break;
                default:
                    if (array_key_exists($key->getBinary(), $unknown)) {
                        throw new InvalidPSBTException("Duplicate unknown key");
                    }
                    $unknown[$key->getBinary()] = $value;
                    break;
            }
        } while ($parser->getPosition() < $parser->getSize());

        return new self($redeemScript, $witnessScript, $bip32Derivations);
    }

    public function getRedeemScript(): ScriptInterface
    {
        if (!$this->redeemScript) {
            throw new \RuntimeException("Output redeem script not known");
        }
        return $this->redeemScript;
    }

    public function hasRedeemScript(): bool
    {
        return $this->redeemScript !== null;
    }

    public function getWitnessScript(): ScriptInterface
    {
        if (!$this->witnessScript) {
            throw new \RuntimeException("Output witness script not known");
        }
        return $this->witnessScript;
    }

    public function hasWitnessScript(): bool
    {
        return $this->witnessScript !== null;
    }

    /**
     * @return PSBTBip32Derivation[]
     */
    public function getBip32Derivations(): array
    {
        return $this->bip32Derivations;
    }

    /**
     * @return BufferInterface[]
     */
    public function getUnknownFields(): array
    {
        return $this->unknown;
    }

    public function writeToParser(Parser $parser, VarString $vs): array
    {
        $map = [];
        if ($this->redeemScript) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::REDEEM_SCRIPT))));
            $parser->appendBinary($vs->write($this->redeemScript->getBuffer()));
        }

        if ($this->witnessScript) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::WITNESS_SCRIPT))));
            $parser->appendBinary($vs->write($this->witnessScript->getBuffer()));
        }

        foreach ($this->bip32Derivations as $key => $value) {
            $parser->appendBinary($vs->write(new Buffer(chr(self::BIP32_DERIVATION) . $key)));
            $parser->appendBinary($vs->write(new Buffer(pack(
                str_repeat("N", 1 + count($value->getPath())),
                $value->getMasterKeyFpr(),
                ...$value->getPath()
            ))));
        }

        foreach ($this->unknown as $key => $value) {
            $parser->appendBinary($vs->write(new Buffer($key)));
            $parser->appendBinary($vs->write($value));
        }

        $parser->appendBinary($vs->write(new Buffer()));
        return $map;
    }
}
