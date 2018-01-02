<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Factory;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Script\Interpreter\Checker;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\ScriptWitness;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\InputSigner;
use BitWasp\Bitcoin\Transaction\Factory\SignData;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Buffertools\Buffer;

class InputSignerTest extends AbstractTestCase
{
    /**
     * @param array $signDataArr
     * @return SignData
     */
    private function decodeSignData(array $signDataArr)
    {
        $signData = new SignData();
        if (isset($signDataArr['redeemScript'])) {
            $signData->p2sh(ScriptFactory::fromHex($signDataArr['redeemScript']));
        }
        if (isset($signDataArr['witnessScript'])) {
            $signData->p2wsh(ScriptFactory::fromHex($signDataArr['witnessScript']));
        }
        if (isset($signDataArr['signaturePolicy'])) {
            $signData->signaturePolicy($signDataArr['signaturePolicy']);
        }
        return $signData;
    }

    /**
     * @param array $txOutArr
     * @return TransactionOutput
     */
    private function decodeTxOut(array $txOutArr)
    {
        return new TransactionOutput((int) $txOutArr['value'], ScriptFactory::fromHex($txOutArr['script']));
    }

    /**
     * @return array
     */
    public function getVectors()
    {
        $fixtures = json_decode($this->dataFile('signer_fixtures.json'), true)['invalid_solve'];
        $vectors = [];
        $ec = Bitcoin::getEcAdapter();
        foreach ($fixtures as $fixture) {
            $txb = new TxBuilder();
            if (isset($fixture['inputs'])) {
                $witnesses = array_fill(0, count($fixture['inputs']), new ScriptWitness());
                foreach ($fixture['inputs'] as $i => $input) {
                    $txb->input(new Buffer('', 32), 0, ScriptFactory::fromHex($input['scriptSig']));
                    if (isset($input['witness'])) {
                        $witnesses[$i] = new ScriptWitness(...array_map([Buffer::class, 'hex'], $input['witness']));
                    }
                }
                $txb->witnesses($witnesses);
            }

            $description = isset($fixture['description']) ? $fixture['description'] : '';
            $tx = $txb->get();
            $vectors[] = [$description, $ec, $tx, $this->decodeTxOut($fixture['txOut']), $this->decodeSignData($fixture['signData']), $fixture['exception']['type'], $fixture['exception']['message']];
        }

        return $vectors;
    }

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param TransactionInterface $tx
     * @param TransactionOutput $txOut
     * @param SignData $signData
     * @param string $exception
     * @param string $exceptionMsg
     * @dataProvider getVectors
     */
    public function testInvalidSolveSignData($description, EcAdapterInterface $ecAdapter, TransactionInterface $tx, TransactionOutput $txOut, SignData $signData, $exception, $exceptionMsg)
    {
        $checker = new Checker($ecAdapter, $tx, 0, $txOut->getValue());
        try {
            (new InputSigner($ecAdapter, $tx, 0, $txOut, $signData, $checker))
                ->extract();
        } catch (\Exception $caught) {
            $this->assertInstanceOf($exception, $caught);
            $this->assertEquals($exceptionMsg, $caught->getMessage());
            return;
        }

        throw new \RuntimeException("Didn't lead to exception: expected {$exception} with message {$exceptionMsg}");
    }
}
