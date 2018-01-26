<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Tests\Block;

use BitWasp\Bitcoin\Block\MerkleRoot;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;

class MerkleRootTest extends AbstractTestCase
{

    /**
     * @expectedException \BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty
     */
    public function testCannotUseEmptyCollection()
    {
        $math = $this->safeMath();
        $root = new MerkleRoot($math, []);
        $root->calculateHash();
    }

    /**
     * @param array $hexes
     * @return array
     */
    private function getTransactionCollection(array $hexes)
    {
        return array_map(
            function ($value) {
                return TransactionFactory::fromHex($value);
            },
            $hexes
        );
    }

    /**
     * @return array
     */
    public function getMerkleVectors()
    {
        $math = new Math();
        $vectors = [];

        // Only one transaction - will equal the transaction txid
        $txHex1 = '010000000462442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff10aa2a8f9211ab71ffc9df03d52450d89a9de648fdfd75c0d20e4dcb1be29cfd020000008b483045022100d088af937bd457903391023c468bdbb9dc46681c3c83ab7b101c26a41524a0e20220369597fa4737aa4408469fec831b5ce53caee8e9fec81282376c6f592be354fb01410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff067b1e03bd8edc0496b41af958fead9d57489fa12d23f4b341ded9b78d8cb114000000008b483045022009538bca3258eb4175faa7121dca68b51d95f2ed7d24278f03e2d88077d92815022100b8706672c585e8607e18d235e69548cd28736adfa9ce4f8f5f3baffc5aad091b01410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff7f6d4bbb8f0d9b8bcad2e431c270aac63aa9caaa880dbd1688e39b6ac0d45ff4020000008b48304502203da091fed8fc71b3c859ee1dfe9c3d0e64915502af057357effa1ae4d1e0dbbf02210090fd964dfe7286b1ab0af3e8d6686c7826039eb0b46bac9803af367f080f38e401410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff0200b79ba7000000001976a914b5ac94f60f833b1e2dab9bc5f7895687bd750e8688acb0720200000000001976a9141b16cf7372a97b42533605e14616b6338caba8e888ac00000000';
        $tx = TransactionFactory::fromHex($txHex1);
        $vectors[] = [
            $math,
            [$txHex1],
            $tx->getTxId()
        ];

        // Only an even number of transactions
        $vectors[] = [
            $math,
            [
                '01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff070456720e1b017bffffffff0100f2052a010000004341048cb16dcb7db3dd9ebee9f677d487c6272a93841e73a96589ba214a0324798048794a9fafe9e8d761e77f3eba3d45b1618f25493eee1ac598bdef3951aaca0a63ac00000000',
                '01000000015132aa73be95888c4ceefd27c2be87df5c03e463afa0be4161e6740e85c9b154010000008b48304502202b69c4847d96f9d6fcdfeb3c277ab16471f45577236eafd15baa504a5655d1ae022100a40c1fcd609e360b98e5d48b58c1afcf7c308b799868b0c47e6b55c726e67125014104d4dfd5815e61e2496856326ea23443b1e6b2067ddb1e14f22c6b700e27043976a73f5d0312a332fc4c6a87513d296e54b12dedb635aa5f7cecfd3a891acdf0acffffffff0280509b1b050000001976a914669b317ee66d134446517794c81cd304e115609088ac00e40b54020000001976a9141a1487913d49504f3e7672868b35caf85b68940888ac00000000'
            ],
            Buffer::hex('e22e7f473e3c50e2a5eab4ee1bc1bd8bb6d21a5485542ccebbda9282bb75491f', 32)
        ];

        // An odd number of transactions
        $vectors[] = [
            $math,
            ['01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff070456720e1b014dffffffff0100f2052a01000000434104e42b336370edbe2ba4c82a4239cecb5584416b5d844c25c58644700fa37512f300294fc802ed0a8525e83c583986ef58bd0fd259318506e600accd9ea39c8a7aac00000000','01000000016d00d07023106a1f15d8e0bebd2340945fe14f48525764952f7698e25dee8f6c000000008b483045022070145ad92706c5525c304e40d307bfe919ea13fb13b3f1d1449cba8cfc97018d022100ecf96a9a3d9b840c2fc944150200e0ba3c79d725b15dece47fd3529bb83e88040141040dc92273d787c973be5649047ef7241c0faa77f5968056630e46fc4e7bdf7d06886c23bf67d42c62ee8274758cfc1906313cb324e1b1b12aabe0b6785a17138affffffff02c05961240b0000001976a9140dcee4bebdccd55fbcc7bdd63a11215667ce868088ac404b4c00000000001976a9141d5cf706fe85aecfdb2b0536fe2682b2e93f8ecf88ac00000000','0100000001e5d7950689c9376d387b280bf1b0145a2dbdd2708f8c40e9e74ee1e65b389855000000004948304502205ef4bfb3e3551f0477c8ad5609690220c58a7de8b9cbd1d0f5730929e366d548022100dc968d62db776912ec3ab1a64640a31559cfdddb9eed4706224be9bfe058d46401ffffffff0100f2052a010000001976a914f74a2698d94e41a97a8ad2e96fd858f9db05559f88ac00000000'],
            Buffer::hex('cb59c3f584d7fd3d391b56674c00830d0c33c164a44ab25df88ba8547c6355cb', 32)
        ];

        return $vectors;
    }

    /**
     * @dataProvider getMerkleVectors
     * @param Math $math
     * @param array $txArray
     * @param BufferInterface $eMerkleRoot
     * @throws \BitWasp\Bitcoin\Exceptions\MerkleTreeEmpty
     */
    public function testWholeSet(Math $math, array $txArray, BufferInterface $eMerkleRoot)
    {
        $transactions = $this->getTransactionCollection($txArray);
        $merkle = new MerkleRoot($math, $transactions);
        $this->assertEquals($eMerkleRoot->getHex(), $merkle->calculateHash()->getHex());

        $this->assertTrue($eMerkleRoot->equals($merkle->calculateHash()));
    }
}
