<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Interpreter\Number;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\P2shScript;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\WitnessScript;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;

class CltvTest extends AbstractTestCase
{
    /**
     * @param int $locktime
     * @param int $sequence
     * @return TransactionInterface
     */
    public function txFixture(int $locktime, int $sequence)
    {
        $addrCreator = new AddressCreator();
        return (new TxBuilder())
            ->input('abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234abcd1234', 0, null, $sequence)
            ->output(90000000, $addrCreator->fromString("1BQLNJtMDKmMZ4PyqVFfRuBNvoGhjigBKF")->getScriptPubKey())
            ->locktime($locktime)
            ->get()
        ;
    }

    /**
     * @return array
     */
    public function getCltvCases()
    {
        return [
            [
                491111, $this->txFixture(491111, 0), null, null,
            ],
            [
                491111, $this->txFixture(491112, 0), null, null,
            ],
            [
                491111, $this->txFixture(491111, 0xffffffff - 1), null, null,
            ],
            [
                491111, $this->txFixture(491110, 0), \RuntimeException::class, "Output is not yet spendable, must wait until block 491111",
            ],
            [
                491111, $this->txFixture(491111, 0xffffffff), \RuntimeException::class, "Input sequence is set to max, therefore CHECKLOCKTIMEVERIFY would fail",
            ],
            [
                491111, $this->txFixture(491110, 0xffffffff), \RuntimeException::class, "Input sequence is set to max, therefore CHECKLOCKTIMEVERIFY would fail",
            ],
            [
                491111, $this->txFixture(time(), 0), \RuntimeException::class, "CLTV was for block height, but tx locktime was in timestamp range",
            ],
            [
                time(), $this->txFixture(491111, 0), \RuntimeException::class, "CLTV was for timestamp, but tx locktime was in block range",
            ],
        ];
    }

    /**
     * @param int $locktime
     * @param TransactionInterface $unsigned
     * @param null|string $exception
     * @param null|string $exceptionMsg
     * @dataProvider getCltvCases
     */
    public function testCltv(int $locktime, TransactionInterface $unsigned, $exception = null, $exceptionMsg = null)
    {
        /** @var PrivateKeyInterface[] $keys */
        $factory = new PrivateKeyFactory(true);
        $key = $factory->fromHex("4200000042000000420000004200000042000000420000004200000042000000");

        $s = ScriptFactory::sequence([
            Number::int($locktime)->getBuffer(), Opcodes::OP_CHECKLOCKTIMEVERIFY, Opcodes::OP_DROP,
            $key->getPublicKey()->getBuffer(), Opcodes::OP_CHECKSIG,
        ]);

        $ws = new WitnessScript($s);
        $rs = new P2shScript($ws);
        $spk = $rs->getOutputScript();

        $txOut = new TransactionOutput(100000000, $spk);

        $flags = Interpreter::VERIFY_DERSIG | Interpreter::VERIFY_P2SH | Interpreter::VERIFY_CHECKLOCKTIMEVERIFY;

        $signData = (new SignData())
            ->p2sh($rs)
            ->p2wsh($ws)
            ->signaturePolicy($flags)
        ;

        $signer = (new Signer($unsigned))
            ->allowComplexScripts(true)
        ;

        if (null !== $exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($exceptionMsg);
        }

        $input = $signer
            ->input(0, $txOut, $signData)
            ->signStep(1, $key)
        ;

        if ($exception) {
            $this->fail("expected failure before verification can commence");
        }

        $this->assertTrue($input->verify());
    }
}
