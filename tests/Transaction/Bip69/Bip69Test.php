<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Transaction\Bip69;

use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Bip69\Bip69;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInputInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\TransactionOutputInterface;
use BitWasp\Buffertools\Buffer;

class Bip69Test extends AbstractTestCase
{
    public function getVectors()
    {
        $bip69 = new Bip69();
        return [
            [$bip69, true, '010000000255605dc6f5c3dc148b6da58442b0b2cd422be385eab2ebea4119ee9c268d28350000000049483045022100aa46504baa86df8a33b1192b1b9367b4d729dc41e389f2c04f3e5c7f0559aae702205e82253a54bf5c4f65b7428551554b2045167d6d206dfe6a2e198127d3f7df1501ffffffff55605dc6f5c3dc148b6da58442b0b2cd422be385eab2ebea4119ee9c268d2835010000004847304402202329484c35fa9d6bb32a55a70c0982f606ce0e3634b69006138683bcd12cbb6602200c28feb1e2555c3210f1dddb299738b4ff8bbe9667b68cb8764b5ac17b7adf0001ffffffff0200e1f505000000004341046a0765b5865641ce08dd39690aade26dfbf5511430ca428a3089261361cef170e3929a68aee3d8d4848b0c5111b0a37b82b86ad559fd2a745b44d8e8d9dfdc0cac00180d8f000000004341044a656f065871a353f216ca26cef8dde2f03e8c16202d2e8ad769f02032cb86a5eb5e56842e92e19141d60a01928f8dd2c875a390f67c1f6c94cfc617c0ea45afac00000000'],
            [$bip69, false, '0100000011aad553bb1650007e9982a8ac79d227cd8c831e1573b11f25573a37664e5f3e64000000006a47304402205438cedd30ee828b0938a863e08d810526123746c1f4abee5b7bc2312373450c02207f26914f4275f8f0040ab3375bacc8c5d610c095db8ed0785de5dc57456591a601210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffc26f3eb7932f7acddc5ddd26602b77e7516079b03090a16e2c2f5485d1fde028000000006b483045022100f81d98c1de9bb61063a5e6671d191b400fda3a07d886e663799760393405439d0220234303c9af4bad3d665f00277fe70cdd26cd56679f114a40d9107249d29c979401210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff456a9e597129f5df2e11b842833fc19a94c563f57449281d3cd01249a830a1f0000000006a47304402202310b00924794ef68a8f09564fd0bb128838c66bc45d1a3f95c5cab52680f166022039fc99138c29f6c434012b14aca651b1c02d97324d6bd9dd0ffced0782c7e3bd01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff571fb3e02278217852dd5d299947e2b7354a639adc32ec1fa7b82cfb5dec530e000000006b483045022100d276251f1f4479d8521269ec8b1b45c6f0e779fcf1658ec627689fa8a55a9ca50220212a1e307e6182479818c543e1b47d62e4fc3ce6cc7fc78183c7071d245839df01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff5d8de50362ff33d3526ac3602e9ee25c1a349def086a7fc1d9941aaeb9e91d38010000006b4830450221008768eeb1240451c127b88d89047dd387d13357ce5496726fc7813edc6acd55ac022015187451c3fb66629af38fdb061dfb39899244b15c45e4a7ccc31064a059730d01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff60ad3408b89ea19caf3abd5e74e7a084344987c64b1563af52242e9d2a8320f3000000006b4830450221009be4261ec050ebf33fa3d47248c7086e4c247cafbb100ea7cee4aa81cd1383f5022008a70d6402b153560096c849d7da6fe61c771a60e41ff457aac30673ceceafee01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffe9b483a8ac4129780c88d1babe41e89dc10a26dedbf14f80a28474e9a11104de010000006b4830450221009bc40eee321b39b5dc26883f79cd1f5a226fc6eed9e79e21d828f4c23190c57e022078182fd6086e265589105023d9efa4cba83f38c674a499481bd54eee196b033f01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffe28db9462d3004e21e765e03a45ecb147f136a20ba8bca78ba60ebfc8e2f8b3b000000006a47304402200fb572b7c6916515452e370c2b6f97fcae54abe0793d804a5a53e419983fae1602205191984b6928bf4a1e25b00e5b5569a0ce1ecb82db2dea75fe4378673b53b9e801210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff7a1ef65ff1b7b7740c662ab6c9735ace4a16279c23a1db5709ed652918ffff54010000006a47304402206bc218a925f7280d615c8ea4f0131a9f26e7fc64cff6eeeb44edb88aba14f1910220779d5d67231bc2d2d93c3c5ab74dcd193dd3d04023e58709ad7ffbf95161be6201210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff850cecf958468ca7ffa6a490afe13b8c271b1326b0ddc1fdfdf9f3c7e365fdba000000006a473044022047df98cc26bd2bfdc5b2b97c27aead78a214810ff023e721339292d5ce50823d02205fe99dc5f667908974dae40cc7a9475af7fa6671ba44f64a00fcd01fa12ab523012102ca46fa75454650afba1784bc7b079d687e808634411e4beff1f70e44596308a1ffffffff8640e312040e476cf6727c60ca3f4a3ad51623500aacdda96e7728dbdd99e8a5000000006a47304402205566aa84d3d84226d5ab93e6f253b57b3ef37eb09bb73441dae35de86271352a02206ee0b7f800f73695a2073a2967c9ad99e19f6ddf18ce877adf822e408ba9291e01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff91c1889c5c24b93b56e643121f7a05a34c10c5495c450504c7b5afcb37e11d7a000000006b483045022100df61d45bbaa4571cdd6c5c822cba458cdc55285cdf7ba9cd5bb9fc18096deb9102201caf8c771204df7fd7c920c4489da7bc3a60e1d23c1a97e237c63afe53250b4a01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff2470947216eb81ea0eeeb4fe19362ec05767db01c3aa3006bb499e8b6d6eaa26010000006a473044022031501a0b2846b8822a32b9947b058d89d32fc758e009fc2130c2e5effc925af70220574ef3c9e350cef726c75114f0701fd8b188c6ec5f84adce0ed5c393828a5ae001210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff0abcd77d65cc14363f8262898335f184d6da5ad060ff9e40bf201741022c2b40010000006b483045022100a6ac110802b699f9a2bff0eea252d32e3d572b19214d49d8bb7405efa2af28f1022033b7563eb595f6d7ed7ec01734e17b505214fe0851352ed9c3c8120d53268e9a01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffa43bebbebf07452a893a95bfea1d5db338d23579be172fe803dce02eeb7c037d010000006b483045022100ebc77ed0f11d15fe630fe533dc350c2ddc1c81cfeb81d5a27d0587163f58a28c02200983b2a32a1014bab633bfc9258083ac282b79566b6b3fa45c1e6758610444f401210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffb102113fa46ce949616d9cda00f6b10231336b3928eaaac6bfe42d1bf3561d6c010000006a473044022010f8731929a55c1c49610722e965635529ed895b2292d781b183d465799906b20220098359adcbc669cd4b294cc129b110fe035d2f76517248f4b7129f3bf793d07f01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffffb861fab2cde188499758346be46b5fbec635addfc4e7b0c8a07c0a908f2b11b4000000006a47304402207328142bb02ef5d6496a210300f4aea71f67683b842fa3df32cae6c88b49a9bb022020f56ddff5042260cfda2c9f39b7dec858cc2f4a76a987cd2dc25945b04e15fe01210391064d5b2d1c70f264969046fcff853a7e2bfde5d121d38dc5ebd7bc37c2b210ffffffff027064d817000000001976a9144a5fba237213a062f6f57978f796390bdcf8d01588ac00902f50090000001976a9145be32612930b8323add2212a4ec03c1562084f8488ac00000000']
        ];
    }

    /**
     * @dataProvider getVectors
     * @param Bip69 $bip69
     * @param $expectedCheck
     * @param $txHex
     */
    public function testCheck(Bip69 $bip69, bool $expectedCheck, string $txHex)
    {
        $tx = TransactionFactory::fromHex($txHex);
        $this->assertEquals($expectedCheck, $bip69->check($tx));

        if ($expectedCheck === false) {
            $fixed = $bip69->mutate($tx);
            $this->assertEquals(true, $bip69->check($fixed));
        }
    }

    /**
     * @return \stdClass
     */
    private function getBitcoinJsVectors()
    {
        $file = $this->dataFile('bip69.bitcoinjs.json');
        return json_decode($file);
    }

    /**
     * @return array
     */
    public function getOutputVectors()
    {
        $bitcoinjs = $this->getBitcoinJsVectors();
        $outputFixtures = $bitcoinjs->outputs;
        $outputVectors = [];

        foreach ($outputFixtures as $fixture) {
            $outputs = $fixture->outputs;
            $expected = $fixture->expected;
            $array = [];
            foreach ($outputs as $i) {
                $array[] = new TransactionOutput($i->value, new Script(Buffer::hex($i->script)));
            }

            $outputVectors[] = [$array, $expected];
        }

        return $outputVectors;
    }

    /**
     * @return array
     */
    public function getInputVectors()
    {
        $bitcoinjs = $this->getBitcoinJsVectors();
        $inputFixtures = $bitcoinjs->inputs;
        $inputVectors = [];

        foreach ($inputFixtures as $fixture) {
            $inputs = $fixture->inputs;
            $expected = $fixture->expected;
            $array = [];
            foreach ($inputs as $i) {
                $array[] = new TransactionInput(new OutPoint(Buffer::hex($i->txId, 32), $i->vout), new Script());
            }

            $inputVectors[] = [$array, $expected];
        }

        return $inputVectors;
    }

    /**
     * @param TransactionInputInterface[] $vin
     * @param array $vexpected
     * @dataProvider getInputVectors
     */
    public function testInputsSort(array $vin, array $vexpected)
    {
        $bip69 = new Bip69();
        $result = $bip69->sortInputs($vin);

        foreach ($vexpected as $i => $k) {
            $this->assertTrue($result[$i]->equals($vin[$k]));
        }
    }

    /**
     * @param TransactionOutputInterface[] $vout
     * @param array $vexpected
     * @dataProvider getOutputVectors
     */
    public function testOutputsSort(array $vout, array $vexpected)
    {
        $bip69 = new Bip69();
        $result = $bip69->sortOutputs($vout);

        foreach ($vexpected as $i => $k) {
            $this->assertTrue($result[$i]->equals($vout[$k]));
        }
    }
}
