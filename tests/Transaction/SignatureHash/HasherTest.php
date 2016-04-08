<?php

namespace BitWasp\Bitcoin\Tests\Transaction\SignatureHash;

use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Tests\AbstractTestCase;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\SignatureHash\Hasher;
use BitWasp\Bitcoin\Transaction\TransactionFactory;

class HasherTest extends AbstractTestCase
{

    /**
     * @var Transaction
     */
    protected $tx;

    public function setUp()
    {
        $this->tx = TransactionFactory::fromHex('0100000014e3b8f4a75dd3a033744d8245148d5a8b734e6ebb157ac12d49e65d4f01f6c86c000000006c493046022100e8a2df24fd890121d8dd85c249b742d0585ec17d18b1bf97050e72eaaceb1580022100d7c37967048a617d7551c8249ea5e58cbf71a0508a6c459dd3a9dfefba3c592f0121030c51892ad8c9df7590c84bc2475576d6dc0815a5bf3ca37f1c58fe82e45d9ef5ffffffff0f1408fa2773334487d1d37e45cb399d049c7e46db3faadfcc204656bce57f5e000000006b483045022100cc339da0e9330b2375124a5fae678b130a4e5215310d85a1db2c7da32dd9633a02205fb02c932eab91733920bab341ad61097f2ff5dc73e46577ce3b70fd0e2ecc4b0121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff72a329f6d5cb92a3cd9e8aa252f0c683f28cb57e8884cfff42ffdddaca86f2e4000000006a473044022013f6b4e159ba9f88825746f9c7d1131cb14667a83d6b3871b5bab2a8f9f75759022024e29aa4bb7c7b994468c0a7a7add2bb180fc9a48b0187d2e06239b358cce8eb0121037cf462b312b1696f2654a21100a6a726238b91455d0f50f69526335d9022fdc5ffffffff49d1bb27b1027249326f3ad35a06b3c7fc9af2c0318caa02c1a90ae2e2a46cf8000000006c493046022100b6381612d4a5b1c75d57fdf93ce8fe39d4541a97af5cd312dbd91925cbd2037e022100a32954780c7d711059524f03ed78cf2e234554977a7e2139e7e7c6949835da660121025a4098ac03d3f706bfdaa795f80350aad15b5a6a578cee2991c16edae0255e76ffffffffc6b749366d13fca59f264b2714bc090660eb291361f23d79b6515f48f35dfd2c010000006b48304502205421f94ee54d829f921785860d4603b82caf8f3722f768e26970f4baa625c0dc022100f3c0cee26b1a98558386fbfcb568100582acc7bc8423e3402313298e0a3291520121026bff9f45e1645a6a67f70e80439d982d3d6dd0fe31258f93ae65161a14b54648ffffffff1f48a79c65634eb19c4a7eaca76a3f8f7c47b649cf54c8fbb5be6f87f6a42fa7010000006a47304402207907ec39e5ff6a85c5c0e5b8d7a81750e1e450e85839d87cdde4cfba405f6ee602205439aa642218bb676d2d2b9c0c63dd44395ad85fad2954ac1794bb4b35e134fc0121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff3a339577e45201a85797937aa44fe7a31f9240ba8a9169e46c32bbd82fbc2ae3000000006a473044022018e0dbfb5ee617fb7d1c28c672fb5428cbc1edb6ed8cc76bc68682432c4bfe450220187a7d4e7b2af69a8e7c7ebfd1b6f02143cf7a32c1041c01bc5324bcd97101880121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff7d0e4f252c644d051e35ee839abde736f26dd2046c7ca18711c759e3c86fb15a220000006a473044022004dfb719c5afca95db100f4e55fb4dd27f4f9faf1a6eff390d9bbdbf9b28fa4b02206dfd0d5f74b4c6d8424d44461c6a230fc174902d2be8d1792e029c3d67a1be9601210388da4b2db35387cf3bfc786453e8ca952b5386c95ce1b39e7ff5cf62cb7033fbfffffffff009c6f8a9b86d97989eaaacf3ada9f9428bae3a1a01339b85fa541680271c3a000000006b48304502203c17278401d3f7e6ab56597b6b88c783db5aa534897954e92a95732b5d714a860221008be28d72988a8ba69ff3889337468df0e83612e9cfbead9118fb8f4decfb3e93012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff75068f094fd516d1658dac8c88ae027852a84423831b780925fb3d591a308227010000006b48304502203ce1a111748534bc601cc4a879f4097b098f7622d28a3da89ac7b90f3b29ee52022100b7de6a03241f6fea37179c3b2f68f45b69c963edc19dc0b3b0350bb8ffbd9063012102c64a6d411eef9f79890d317ad72329608be5f58f6757e1c5c6d4f21076345ddcfffffffffc844c99e1adab4677dda6b5def33ce549293a40d08da6a8c36fb9274c76ba43000000006c493046022100ee2b946560aced0633a5151f1fa5dd0249d68bef420e43e1f7edfd0e83619cfd022100a433121fb022efb69043310bd982a842e7f5be737069d3535962ecd78c4954070121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff32f65f550d4ef09e451303af1d2f9964b4f62e7b6562d5dbe23d4be89ae32e36010000006b483045022100bb4b1af8c22e51e9a3f71fb2cd883746e3812cbfd6a0695bbd22e53d573cb6f20220539e478519411e1d277a844810ab756004cc7755ff585cda7aa2c9d93ab4570b01210290a17b828f33417e7e3cb179bd50a621c8381964e6e239e91eacf3f414018770fffffffff6a6dafe0ae32f6c891de86c4aa12b318eeb89ca4d8792906ae03fd0db04c76f010000006b48304502203dfe2f1d58fdcab2f67f079c14a26c69e88718d38615d5de8230ee74c1e55d9e022100bc7651cf77f142366b502c65c59790a3d2cfa52852feab04212d3ea68e040be0012103214083806ce8aebf35544845c73f1e9d4dcad2fc6057e6cda963498f5420fef9ffffffff11c3e0e2a520bd829fab9b4311e603de79ce1304e1f28204334f79d1fd2c9138010000006b48304502206196ca600a02bf7fd210489e520b915b521dfa84a37bf56f2022aa8e89d62e6f022100949aabaafe92aaa8e207f0699a4745179fdc34a475d5c783785a58744ed5dd410121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff54dffe807c0b935528100313322949738717dc2af2f374f2a72af24b82291f70000000006b483045022100b84abff6f636082d2b85da4de25de13388a7901f0df5b87d871305e694667b4f0220477619c2140bc2d7dfab0d2b7d49f0729afb951831c7a64ee4bede7d8a46960b012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff9923148b409a0888e42612e964216eb575b8fe4d0cfe5aad84323962c219373b000000006b483045022100cc88b67cce1655c38c60ada140b9c34343411cc309c45410edf2b4f133520c03022044c3554a294f6a412219b710689d67c5d8519fc6a139ee311985a14b8b55fd6f012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff5bb09bbcbdd756af1f08457f7c1319aa67d5488f359b58e6f36920b808c789bb010000006a473044022051b255884c8f118780e394c054c5df12d813e3b9983a9854c89e7ffd015cbf5702200ed58fac38a91e19d755d30d910a04aa29848608b72f99aef68e0ecedcbdca53012103f57996ef25762717a75b6d75f0166a33f775783766513118b5476933d7af8078ffffffffc3c978e2e413506b75b7882cc38cf6f40d199c7226f73b5e4ab8295703fd4b03d50000006b48304502201a07c4ce0f76c4f5d96c45811bfa08c00483c987e1297324f40136a4c452c306022100d553873b6b3c20ae2b6a588704bae9daa5093b17283e0b196a27d6e4022ff8a50121032beebbe7e386f1fd27a9a3e59640deaa5f60835ab789012175c0f517149c77e3ffffffff3944343f32f93d43752bea7d916571e236295fbff53c7a9133d09f741116fac5000000006b483045022074aac638f77fed744feb1e99f4a71f20278686fc2f91264058facd9983cde9fb0221008250b8d62dbb8d3954517f9f80e236d1c74720723459b5d69656dadf781ee2e9012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff781119976951dfc6f8a617077068fcb623475acef7eff09808a5ef3281a0ef70010000006b48304502200236e5c6b0787756449043fd413307c176be2b39fb7da11a18e10708a72877fe022100836b99f32532039e6326cbf282cbf78140c53883c474443bd6e6145e52dfb08d012103dd033367f07aee4f365a47cdf3c45147887492c6d20788970296d3b94eb1cfd8ffffffff1365487900000000001976a9143f6cd41f7caeda87b86bc9e009295f179612f45488acc7440f00000000001976a914df90390bee06889ed003b3c4d024a9fd211cf96788ace0ee4504000000001976a9141d8aa01e6628f333fd6fd9d3b88c3f761869caa488acd5080200000000001976a91412d24a8a61e1cd37825fd31bd61eebc2c32ad77688ac0842b582000000001976a9147fb7cccd54bf322ddae63b6b2e20a3624622091888acd5113916000000001976a914e75fa783b5b319578a53f2f18c37d88513d060b088ac80969800000000001976a9140ce8c479dad1b78baeea2217af17655b908b59b888ace8fb1204000000001976a9141a80a0ee9b4f1d03c3fb2220e4124d441431d41888ac00e1f505000000001976a914b1fdf35dd37a1a5359ad829f5f43760cd1ea61d588ac80626a94000000001976a9142a929cae46f0b4e5742ae38d6040f11a2b70e7d188ac109d0a02000000001976a914a4b5bfbe26ef9ac8d6e06738b50065b25f3dcce288acab110400000000001976a9146fbc4dd98c194853dc9a3e6f195bddad8eef609788ac00e1f505000000001976a914e1733c6b9e4c98f4ddc19370dd5cae0727af04e788ac5d44d502000000001976a9147514615f455bdfb8b9da74c7707ef0426606c33288ac404b4c00000000001976a914166e78015832ba760593bb292993391d4554a39a88acf0190b01000000001976a91409859ea62e00e0531873c135e37efbeca610d36788ace2fcae8f000000001976a914b40d92da29c478049a8d15bfd85b6ce230863df288acc07a3e22000000001976a9141e12d7845e76857bfba94079c1faca68b3ae24c088ac9e130e01000000001976a9144bdb0b7712726c2684461cd4e5b08934def0187c88ac00000000');
    }

    public function testCreateNew()
    {
        $sighash = new Hasher($this->tx);
        $this->assertInstanceOf('BitWasp\Bitcoin\Transaction\SignatureHash\Hasher', $sighash);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Input does not exist
     */
    public function testFailsWithInvalidInputToSign()
    {
        $sighash = new \BitWasp\Bitcoin\Transaction\SignatureHash\Hasher($this->tx);
        $sighash->calculate(new Script(), 99);
    }

    public function testCalculateHash()
    {
        $f    = file_get_contents(__DIR__ . '/../../Data/signaturehash.hash.json');
        $json = json_decode($f);
        foreach ($json->test as $test) {
            $script = new Script(Buffer::hex($test->outScript));

            $t = TransactionFactory::fromHex($test->tx);
            $h = $t->getSignatureHash()->calculate($script, 0);

            $this->assertEquals($h->getHex(), $test->sighash);
        }
    }

    public function testSighashSingle()
    {
        $network = NetworkFactory::bitcoinTestnet();
        $ecAdapter = Bitcoin::getEcAdapter();

        //  bitcoin-cli -testnet=1 createrawtransaction '[{"txid":"2a61a399351922ab1e3b5d6f5f8fe0fcc6a3edbb7f267cc330ad2835d529fb2f", "vout":1}]' '{"mgnMnbj9HHgGkJ8sf9s7wPSQQe2uTrwkuK": 0.15, "moFRKYGsQWQfDPmRUNrzsGwqTzdBNyaKfe": 0.1}'
        //  bitcoin-cli -testnet=1 signrawtransaction "01000000012ffb29d53528ad30c37c267fbbeda3c6fce08f5f6f5d3b1eab22193599a3612a0100000000ffffffff02c0e1e400000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac809698000000000019

        $spend = '0100000001e2deba2aaf49ccbca9bafabc9049917db0d219cd9a44cfc751369d0a1c538b4e010000006a47304402205f73e04abdc4687e12ded30916f747cee200e113158c85fda953344bdc02d43b02207b86e6b2862aca49b727afee0a6ff7d52795d19cae4d357419f31fd987ec7b220121034087874436d20c49f5afdf14f7cbf6b8c03f28629ef299e4cb454070d563e6e9feffffff028cb7d31b000000001976a9144df845f26149b78004fa15bd9afbe6a70895a1a488ac80c3c901000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac28fb0500';
        $priv = PrivateKeyFactory::fromWif('cQnFidqYxEoi8xZz1hDtFRcEkzpXF5tbofpWbgWdEk9KHhAo7RxD', $ecAdapter);

        $tx = TransactionFactory::fromHex($spend);

        $b = new TxBuilder();
        $new = $b
            ->spendOutputFrom($tx, 1)
            ->payToAddress(15000000, $priv->getAddress())
            ->payToAddress(10000000, \BitWasp\Bitcoin\Address\AddressFactory::fromString('moFRKYGsQWQfDPmRUNrzsGwqTzdBNyaKfe', $network))
            ->get();

        $builder = new Signer($new, $ecAdapter);
        $single = \BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::SINGLE;
        $builder->sign(0, $priv, $tx->getOutput(1), null, null, $single);

        $expected = '01000000012ffb29d53528ad30c37c267fbbeda3c6fce08f5f6f5d3b1eab22193599a3612a010000006b483045022100dad4bd28448e626ecb1ade42a09c43559d50b61b57a06fac992a5ecdd73deb740220524082f83560e2df9afaa283c699dec4c5b01687484d73e7b280e5a506caf1c4032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff02c0e1e400000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac80969800000000001976a91454d0e925d5ee0ee26768a237067dee793d01a70688ac00000000';
        //  bitcoin-cli -testnet=1 signrawtransaction "01000000012ffb29d53528ad30c37c267fbbeda3c6fce08f5f6f5d3b1eab22193599a3612a0100000000ffffffff0140787d01000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac00000000" '[{"txid":"2a61a399351922ab1e3b5d6f5f8fe0fcc6a3edbb7f267cc330ad2835d529fb2f","vout":1,"scriptPubKey":"76a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac"}]' '["cQnFidqYxEoi8xZz1hDtFRcEkzpXF5tbofpWbgWdEk9KHhAo7RxD"]' SINGLE
        $this->assertEquals($expected, $builder->get()->getHex());
    }

    public function testSigHashTypes()
    {
        $ecAdapter = Bitcoin::getEcAdapter();

        // Send some inputs to this test address 12GQVYeAUGF1yBfFwatk7UE5YeSCb1J41p
        $privateKey = PrivateKeyFactory::fromWif('KzRGFiqhXB7SyX6idHQkt77B8mX7adnujdg3VG47jdVK2x4wbUYg', $ecAdapter);

        // 043f61697d1b48d69394879a3e94a2957a7d1a21a38df2ddd6de45a3b2f0b77d / 1
        $tx1 = '0100000001652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05010000006a47304402202e96d42f625f6024eb4ca8d519ec002de45f3c4d931131706878162f200161f802204f7be25bf1f8584e9f157dfd90e0061cf5e17b8a25b170ad15d259d70c0bb1ad012103c704cbf34c686068287649687bd85c5bdd9f9917b1ea27dd12bdeb4a5bcd369affffffff0268804900000000001976a914a29e26543e3c2a1233005fe9f43a90fc0602881c88ac409c0000000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac00000000';
        $tx1NOut = 1;
        // 057ef664f0e1108f270729e53a62e4cb1a7480d9a87f543c6a1a785a1e492c65 / 0
        $tx2 = '0100000001b9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35010000006a47304402200163ec246a23d42e66fa2ef7d4515a9b8d53e18dc62c2ce97950dca587465d0e0220138fc154238f9baebdd46cd5f9e17a4b8c3ba253dcd98807a66462f7feb72daa012102e7ecb15a814ea95ae932455f9efcfc0fa8d4d79f2ac8aff963fd7d5f93769190ffffffff02409c0000000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888ac90204a00000000001976a914eca1358918fab23f3a570030295a2091e8c56a6e88ac00000000';
        $tx2NOut = 0;
        // 35db95c66634c3497a277c1f08ed71a0cb53195ffecbf9798cdde4a30f27fab9 / 0
        $tx3 = '0100000001fe7a2ef677030b877c493bbe53fa566a6830d864f2228e03ed4ec7ceb676deb7010000006b48304502210099e43b9afb19ef44cc207b33fb993a79297fc48308a996989de9703c898a99d602206ceca41218b44ffcc8c1956b0afb2fa41814cd832d99f48f67d57073aa3bc6930121030d800fa11cd76362554a9e8d355ada401c4669245f6268d8409e95be4f6dafb6ffffffff02409c0000000000001976a9140de1f9b92d2ab6d8ead83f9a0ff5cf518dcb03b888acb8c04a00000000001976a91466e6f04c9ff34e73a77600dca525d2a9437b14d488ac00000000';
        $tx3NOut = 0;

        // All tests will involve spending to these addresses
        $addr1 = AddressFactory::fromString('1FUmHWNktw9mPqPdyA4DPGYX5kJo1emerT'); // 0.0002 / 20000 satoshis
        $addr2 = AddressFactory::fromString('1PuVsHqAo3PUcuuuLRMsRvxUNWQGFrd87r'); // 0.0003 / 30000 satoshis
        $addr3 = AddressFactory::fromString('1SNw3ViPq8nFgyrJFH15ahd89mYfCRYVq');  // 0.0005 / 50000 satoshis

        $transaction1 = TransactionFactory::fromHex($tx1);
        $transaction2 = TransactionFactory::fromHex($tx2);
        $transaction3 = TransactionFactory::fromHex($tx3);

        // bitcoin-cli createrawtransaction '[{"txid":"043f61697d1b48d69394879a3e94a2957a7d1a21a38df2ddd6de45a3b2f0b77d","vout":1},{"txid":"057ef664f0e1108f270729e53a62e4cb1a7480d9a87f543c6a1a785a1e492c65","vout":0},{"txid":"35db95c66634c3497a277c1f08ed71a0cb53195ffecbf9798cdde4a30f27fab9","vout":0}]' '{"1FUmHWNktw9mPqPdyA4DPGYX5kJo1emerT":0.0002,"1PuVsHqAo3PUcuuuLRMsRvxUNWQGFrd87r":0.0003,"1SNw3ViPq8nFgyrJFH15ahd89mYfCRYVq":0.0005}'
        $expectedUnsignedTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f040100000000ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e050000000000ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db350000000000ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigAllTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006a47304402206abb0622b8b6ca83f1f4de84830cf38bf4615dc9e47a7dcdcc489905f26aa9cb02201d2d8a7815242b88e4cd66390ca46da802238f9b1395e0d118213d30dad38184012102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006b483045022100de13b42804f87a09bb46def12ab4608108d8c2db41db4bc09064f9c46fcf493102205e5c759ab7b2895c9b0447e56029f6895ff7bb20e0847c564a88a3cfcf080c4f012102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006b4830450221009100a3f5b30182d1cb0172792af6947b6d8d42badb0539f2c209aece5a0628f002200ae91702ca63347e344c85fcb536f30ee97b75cdf4900de534ed5e040e71a548012102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigAllAnyonecanpayTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100bd2829550e9b3a081747281029b5f5a96bbd83bb6a92fa2f8310f1bd0d53abc90220071b469417c55cdb3b04171fd7900d2768981b7ab011553d84d24ea85d277079812102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a47304402206295e17c45c6356ffb20365b696bcbb869db7e8697f4b8a684098ee2bff85feb02202905c441abe39ec9c480749236b84fdd3ebd91ecd25b559136370aacfcf2815c812102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006b483045022100f58e7c98ac8412944d575bcdece0e5966d4018f05988b5b60b6f46b8cb7a543102201c5854d3361e29b58123f34218cec2c722f5ec7a08235ebd007ec637b07c193a812102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigNoneTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100e7f0a1ddd2c0b81e093e029b8a503afa27fe43549b0668d2141abf35eb3a63be022037f12d12cd50fc94a135f933406a8937557de9b9566a8841ff1548c1b6984531022102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a473044022008451123ec2535dab545ade9d697519e63b28df5e311ea05e0ce28d39877a7c8022061ce5dbfb7ab478dd9e05b0acfd959ac3eb2641f61958f5d352f37621073d7c0022102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006a47304402205c001bcdfb35c70d8aa3bdbc75399afb72eb7cf1926ca7c1dfcddcb4d4d3e0f8022028992fffdcd4e9f34ab726f97c24157917641c2ef99361f588e3d4147d46eea5022102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigNoneAnyTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006a47304402204ed272952177aaa5a1b171c2ca5a7a3d300ffcd7e04b040c0baaa4e3561862a502207e65a5b8f99c8a632b186c8a60496a12bf3116f51909b7497413aefdc3be7bf6822102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a47304402203ec365300cc67602f4cc5be027959d3667b48db34c6c87d267c94a7e210d5c1f02204843350311c0a9711cad1960b17ce9e323a1ce6f37deefc3ffe63082d480be92822102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006b48304502210084f86f905c36372eff9c54ccd509a519a3325bcace8abfeed7ed3f0d579979e902201ff330dd2402e5ca9989a8a294fa36d6cf3a093edb18d29c9d9644186a3efeb4822102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigSingleTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100e822f152bb15a1d623b91913cd0fb915e9f85a8dc6c26d51948208bbc0218e800220255f78549d9614c88eac9551429bc00224f22cdcb41a3af70d52138f7e98d333032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a47304402206f37f79adeb86e0e2da679f79ff5c3ba206c6d35cd9a21433f0de34ee83ddbc00220118cabbac5d83b3aa4c2dc01b061e4b2fe83750d85a72ae6a1752300ee5d9aff032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006a473044022042ac843d220a56b3de05f24c85a63e71efa7e5fc7c2ec766a2ffae82a88572b0022051a816b317313ea8d90010a77c3e02d41da4a500e67e6a5347674f836f528d82032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSigSingleAnyoneTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100d05a3b6cf2f0301000b0e45c09054f2c61570ce8798ebf571eef72da3b1c94a1022016d7ef3c133fa703bae2c75158ea08d335ac698506f99b3c369c37a9e8fc4beb832102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006b483045022100ee6bf07b051001dcbfa062692a40adddd070303286b714825b3fb4693dd8fcdb022056610885e5053e5d47f2be3433051305abe7978ead8f7cf2d0368947aff6b307832102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006b483045022100cfc930d5b5272d0220d9da98fabec97b9e66306f735efa837f43f6adc675cad902202f9dff76b8b9ec8f613d46094f17f64d875804292d8804aa59fd295b6fc1416b832102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff03204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';
        $expectedSingleBugTx = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100e822f152bb15a1d623b91913cd0fb915e9f85a8dc6c26d51948208bbc0218e800220255f78549d9614c88eac9551429bc00224f22cdcb41a3af70d52138f7e98d333032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006a47304402206f37f79adeb86e0e2da679f79ff5c3ba206c6d35cd9a21433f0de34ee83ddbc00220118cabbac5d83b3aa4c2dc01b061e4b2fe83750d85a72ae6a1752300ee5d9aff032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffffb9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006a473044022019a2a3322dcdb0e0c25df9f03f264f2c88f43b3b648fec7a28cb85620393a9750220135ff3a6668c6d6c05f32069e47a1feda10979935af2470c97fcb388f96f9738032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d4ffffffff02204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac00000000';
        $regressionSigSingle = '01000000037db7f0b2a345ded6ddf28da3211a7d7a95a2943e9a879493d6481b7d69613f04010000006b483045022100e822f152bb15a1d623b91913cd0fb915e9f85a8dc6c26d51948208bbc0218e800220255f78549d9614c88eac9551429bc00224f22cdcb41a3af70d52138f7e98d333032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d400000000652c491e5a781a6a3c547fa8d980741acbe4623ae52907278f10e1f064f67e05000000006b48304502210096b797c910fcfcfedfb789a06eca534af89e8b3759e094c1ebe21e2a42f06575022043506b17cbd0b0bbbde51113dde4d38cb7cb56bf25055a0bbfbe300a4166e078032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d400000000b9fa270fa3e4dd8c79f9cbfe5f1953cba071ed081f7c277a49c33466c695db35000000006a47304402203121f1c57c67340c1fbd97dbfce3210dca2c0876f9bbcfcd21fb7f395dfdcc7f022028cca8ce5852d67f269aab748c9b9be7720d110f9e427b886bf2125c3f0e509e032102f1c7eac9200f8dee7e34e59318ff2076c8b3e3ac7f43121e57569a1aec1803d40000000003204e0000000000001976a9149ed1f577c60e4be1dbf35318ec12f51d25e8577388ac30750000000000001976a914fb407e88c48921d5547d899e18a7c0a36919f54d88ac50c30000000000001976a91404ccb4eed8cfa9f6e394e945178960f5ccddb38788ac00000000';

        // Test builds unsigned transaction
        $b = new TxBuilder();
        $b
            ->spendOutputFrom($transaction1, $tx1NOut)
            ->spendOutputFrom($transaction2, $tx2NOut)
            ->spendOutputFrom($transaction3, $tx3NOut)
            ->payToAddress(20000, $addr1)
            ->payToAddress(30000, $addr2)
            ->payToAddress(50000, $addr3);
        $unsigned = $b->get();
        $this->assertEquals($expectedUnsignedTx, $unsigned->getHex());

        // Test signs sighash_all transaction properly
        $sighashAll = \BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::ALL;
        $regularSigning = new Signer($unsigned, $ecAdapter);
        $regularSigning
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $sighashAll)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $sighashAll)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $sighashAll);
        $this->assertEquals($expectedSigAllTx, $regularSigning->get()->getHex());

        // Test signs SIGHASH_ALL|ANYONECANPAY
        $regularSigningAnyone = new Signer($unsigned, $ecAdapter);
        $allAnyone = $ecAdapter->getMath()->bitwiseXor(\BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::ANYONECANPAY, $sighashAll);
        $regularSigningAnyone
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $allAnyone)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $allAnyone)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $allAnyone);
        $this->assertEquals($expectedSigAllAnyonecanpayTx, $regularSigningAnyone->get()->getHex());

        // Test signs SIGHASH_SINGLE transaction properly
        $sighashSingle = \BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::SINGLE;
        $singleSigning = new Signer($unsigned, $ecAdapter);
        $singleSigning
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $sighashSingle)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $sighashSingle)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $sighashSingle);
        $hex = $singleSigning->get()->getHex();
        if ($hex == $regressionSigSingle) {
            $this->fail('Regression in Sighash Single handling (clone, object references?)');
        }
        $this->assertEquals($expectedSigSingleTx, $hex);

        // Test signs SIGHASH_SINGLE where inputToSign >= count(outputs)
        $buggy = new Transaction(
            $unsigned->getVersion(),
            $unsigned->getInputs(),
            new TransactionOutputCollection(array_slice($unsigned->getOutputs()->all(), 0, 2)),
            null,
            $unsigned->getLockTime()
        );

        $singleSigningBug = new Signer($buggy, $ecAdapter);
        $singleSigningBug
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $sighashSingle)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $sighashSingle)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $sighashSingle);
        $this->assertEquals($expectedSingleBugTx, $singleSigningBug->get()->getHex());

        // Test handling of SIGHASH_SINGLE|SIGHASH_ANYONECANPAY
        $singleAny = $ecAdapter->getMath()->bitwiseXor(\BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::ANYONECANPAY, $sighashSingle);
        $singleAnyone = new Signer($unsigned, $ecAdapter);
        $singleAnyone
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $singleAny)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $singleAny)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $singleAny);
        $this->assertEquals($expectedSigSingleAnyoneTx, $singleAnyone->get()->getHex());

        // Test signs SIGHASH_NONE transaction properly
        $sighashNone = \BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::NONE;
        $noneSigning = new Signer($unsigned, $ecAdapter);
        $noneSigning
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $sighashNone)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $sighashNone)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $sighashNone);
        $this->assertEquals($expectedSigNoneTx, $noneSigning->get()->getHex());


        // Test signs SIGHASH_NONE transaction properly
        $noneAny = $ecAdapter->getMath()->bitwiseXor(\BitWasp\Bitcoin\Transaction\SignatureHash\SigHashInterface::ANYONECANPAY, $sighashNone);
        $noneAnyone = new Signer($unsigned, $ecAdapter);
        $noneAnyone
            ->sign(0, $privateKey, $transaction1->getOutput($tx1NOut), null, null, $noneAny)
            ->sign(1, $privateKey, $transaction2->getOutput($tx2NOut), null, null, $noneAny)
            ->sign(2, $privateKey, $transaction3->getOutput($tx3NOut), null, null, $noneAny);

        $this->assertEquals($expectedSigNoneAnyTx, $noneAnyone->get()->getHex());
    }
}
