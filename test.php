<?php

use Bitcoin\Bitcoin;
use Bitcoin\Util\Base58;
use Bitcoin\Script\ScriptInterpreter;
use Bitcoin\Signature\K\KInterface;
use Bitcoin\Signature\SignatureHash;
use Bitcoin\Util\Buffer;
use Bitcoin\Block\BlockHeader;
use Bitcoin\Util\Parser;
use Bitcoin\Network;
use Bitcoin\Transaction\Transaction;
use Bitcoin\Transaction\TransactionInput;
use Bitcoin\Transaction\TransactionOutput;
use Bitcoin\Key\HierarchicalKey;
use Bitcoin\Key\PrivateKey;
use Bitcoin\Script\Script;
use Bitcoin\Block\MerkleRoot;
use Bitcoin\Crypto\Hash;
use Bitcoin\Block\Block;
require_once "vendor/autoload.php";

$math = Bitcoin::getMath();

$d = new  \Bitcoin\Chain\Difficulty($math);
$b = Buffer::hex('1b0404cb');
echo $d->getTargetHash($b)."\n";
echo $d->getDifficulty($b);
    /*
$key = new PrivateKey('01');
$message = new Buffer(hash('sha256', hash('sha256', 'Satoshi Nakamoto', true), true));
$k = new \Bitcoin\Signature\K\DeterministicK($key, $message);
$kCopy = $k;
$s = $key->sign($message, $k);
print_r($s);

echo $math->decHex($s->getR()).".";
echo $math->decHEx($s->getS())."\n";



$key = PrivateKey::fromWIF('KyB2Zrzedq5HLjXQy4p34r7Px3BiTeXF5JURKfzzhntYtrEYPCEz');

$txOut = new TransactionOutput;
$txOut->setScript((new Script)->op('OP_DUP')->op('OP_HASH160')->push('497f62ab09cc01fc0c892693a7acc52617ce6022')->op('OP_EQUALVERIFY')->op('OP_CHECKSIG'));
$txOut->setValue(21000);

$transaction = new Transaction;
$input = new TransactionInput;
$input->setTransactionId('64b80627dbf97e05c833dbc78c3b158ed63d7c316c29fc2bb982fbc17d9a17ab')
    ->setVout('1');
$transaction->addInput($input);

$output = new TransactionOutput;
$output->setScript(Script::payToPubKeyHash($key->getPublicKey()));
$output->setValue(10000);
$transaction->addOutput($output);

$kProvider = new \Bitcoin\Signature\K\RandomK();

$hash = (new SignatureHash($transaction))
    ->calculateHash($txOut, 0);

$sig = $key->sign($hash, $kProvider);
    */
//print_r($key);
//print_r($transaction);
//print_r($sig);
/*
echo Math::add(1,2);
echo "\n";
echo Buffer::hex('4141');
echo "\n";
$bitcoin = new Network('00','05','80');
$tx = new Transaction($bitcoin);

$input = new \Bitcoin\TransactionInput();
$input
    ->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000')
    ->setVout('0');

$tx->addInput($input);
print_r($tx);

echo "\n";

$hex = '4141';
$b58 = '15b6VUQY62YhGTmijBsJmmwcA4k519Bac3';

echo Base58::decode($b58) . "\n";
*/

//echo "\n";
//$script = new Script();
//$script
//    ->op('DUP')
 //   ->op('HASH160')
  //  ->push('07ab93b637394b70463458df2bff32ed2550fefd')
   // ->op('EQUALVERIFY');
//echo $script;
/*
$rs = new Script();
$rs
    ->op('OP_2')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->op('OP_3')
    ->op('OP_CHECKMULTISIG');
*/

/*
$s1 = new Script();
$s1->op('OP_0')
    ->push('3045022057e65d83fb50768f310953300cdf09e8c551a716de81eb9e9bea2b055cffce53022100830c1636104d5ba704ef92849db0415182c364278b7f2a53097b65beb1c755c001')
    ->push('3045022100b16c0cf3d6e16a9f9a2559c0043c083e46a8557df1f22755e396b94b08e8624202203b6a9927ceb70eda3e71f584dffe108ef0fcc5040538de45f85c1645b115168601')
    ->push('3044022006135422817bd9f8cd24004c9797114838944a7594b6d9d7da043c93700c58bf0220009c226d944fc1d2c4a29d9b687aab04f2f65f9688c468594a0747067fa7178001')
    ->push('304602210093f6c1402fdefd71e890168f8a2eb34ff18b50a9babdfd1b4a69c8895b10a9bb022100b7fea02dbc6391ac6403f628afe576c2e8b42f7d31c7c38d959766b45e114f6e01')
    ->push('3045022100f6d4fa96d2d221cc0368b0da1fafc889c5212e1a65a5d7b5937d374993568bb002206fc78de031d1cd34b203abedac0ef628ad6c863a0c505533da12cf34bf74fdba01')
    ->push('3045022100b52f4d6f1e69554f15b9e02be1a3f03d96943c2aa21544047d9156b91a2eace5022023b41bef3725b1a6cab9c509b95e3a2f839536325597a2359ea0c14786adf2a801')
    ->push('5621025d951ab5a9c3656aa25b4facf7b9824ca3cca7f9eaf3b84551d3aef8b0803a5721027b7eb1910184738f54b00ee7c5f695598d0f21b8ea87bface1e9d901fa5193802102e8537cc8081358b9bbcbd221da7f10ec167fbadcb03b8ff2980c8a78aca076712102f2d0f1996cf932b766032ea1da0051d8e7688516eb005b9ffd6acfbf032627c321030bd27f6a978bc03748b301e20531dd76f27ddcc25e51c09e65a6e4dafa8abbaf21037bd4c27021916bd09f7af32433a0eb542087cf0ae51cd4289c1c6d35ebfab79856ae');


echo $s->getVarInt()."\n";

$hex = $s1->serialize('hex');

$i = new ScriptInterpreter($s1);
echo "\n";

print_r($i->run());
*/
/*
$priv = PrivateKey::generateNew(true);

$b = 'xprv9s21ZrQH143K3QTDL4LXw2F7HEK3wJUD2nW2nRk4stbPy6cq3jPPqjiChkVvvNKmPGJxWUtg6LnF5kejMRNNU3TGtRBeJgk33yuGBxrMPHi';
$network = new Network('00','05','08',true);
$network->setHDPrivByte('0488ade4');
$network->setHDPubByte('0488b21e');

$b = Base58::decodeCheck($b);
$k = new HierarchicalKey($b, $network);
var_dump(
    array(
        "magic_bytes" => $k->getBytes(),
        "depth" => $k->getDepth(),
        "fingerprint" => $k->getFingerprint(),
        "i" => $k->getSequence(),
        "chaincode" => $k->getChainCode()->serialize('hex'),
        "key" => $k->getKeyData()->serialize('hex'),
        "extended" => $k->getExtendedPrivateKey()
    )
);

$k = $k->deriveChild("2147483648");
var_dump(
    array(
        "magic_bytes" => $k->getBytes(),
        "depth" => $k->getDepth(),
        "fingerprint" => $k->getFingerprint(),
        "i" => $k->getSequence(),
        "chaincode" => $k->getChainCode()->serialize('hex'),
        "key" => $k->getKeyData()->serialize('hex'),
        "extended" => $k->getExtendedPrivateKey()
    )
);*/

//$a = $k->decodePath("m/0/1h/2/3h");
//print_r($a);
//print_r($k);
/*$tx = '0100000014e3b8f4a75dd3a033744d8245148d5a8b734e6ebb157ac12d49e65d4f01f6c86c000000006c493046022100e8a2df24fd890121d8dd85c249b742d0585ec17d18b1bf97050e72eaaceb1580022100d7c37967048a617d7551c8249ea5e58cbf71a0508a6c459dd3a9dfefba3c592f0121030c51892ad8c9df7590c84bc2475576d6dc0815a5bf3ca37f1c58fe82e45d9ef5ffffffff0f1408fa2773334487d1d37e45cb399d049c7e46db3faadfcc204656bce57f5e000000006b483045022100cc339da0e9330b2375124a5fae678b130a4e5215310d85a1db2c7da32dd9633a02205fb02c932eab91733920bab341ad61097f2ff5dc73e46577ce3b70fd0e2ecc4b0121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff72a329f6d5cb92a3cd9e8aa252f0c683f28cb57e8884cfff42ffdddaca86f2e4000000006a473044022013f6b4e159ba9f88825746f9c7d1131cb14667a83d6b3871b5bab2a8f9f75759022024e29aa4bb7c7b994468c0a7a7add2bb180fc9a48b0187d2e06239b358cce8eb0121037cf462b312b1696f2654a21100a6a726238b91455d0f50f69526335d9022fdc5ffffffff49d1bb27b1027249326f3ad35a06b3c7fc9af2c0318caa02c1a90ae2e2a46cf8000000006c493046022100b6381612d4a5b1c75d57fdf93ce8fe39d4541a97af5cd312dbd91925cbd2037e022100a32954780c7d711059524f03ed78cf2e234554977a7e2139e7e7c6949835da660121025a4098ac03d3f706bfdaa795f80350aad15b5a6a578cee2991c16edae0255e76ffffffffc6b749366d13fca59f264b2714bc090660eb291361f23d79b6515f48f35dfd2c010000006b48304502205421f94ee54d829f921785860d4603b82caf8f3722f768e26970f4baa625c0dc022100f3c0cee26b1a98558386fbfcb568100582acc7bc8423e3402313298e0a3291520121026bff9f45e1645a6a67f70e80439d982d3d6dd0fe31258f93ae65161a14b54648ffffffff1f48a79c65634eb19c4a7eaca76a3f8f7c47b649cf54c8fbb5be6f87f6a42fa7010000006a47304402207907ec39e5ff6a85c5c0e5b8d7a81750e1e450e85839d87cdde4cfba405f6ee602205439aa642218bb676d2d2b9c0c63dd44395ad85fad2954ac1794bb4b35e134fc0121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff3a339577e45201a85797937aa44fe7a31f9240ba8a9169e46c32bbd82fbc2ae3000000006a473044022018e0dbfb5ee617fb7d1c28c672fb5428cbc1edb6ed8cc76bc68682432c4bfe450220187a7d4e7b2af69a8e7c7ebfd1b6f02143cf7a32c1041c01bc5324bcd97101880121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff7d0e4f252c644d051e35ee839abde736f26dd2046c7ca18711c759e3c86fb15a220000006a473044022004dfb719c5afca95db100f4e55fb4dd27f4f9faf1a6eff390d9bbdbf9b28fa4b02206dfd0d5f74b4c6d8424d44461c6a230fc174902d2be8d1792e029c3d67a1be9601210388da4b2db35387cf3bfc786453e8ca952b5386c95ce1b39e7ff5cf62cb7033fbfffffffff009c6f8a9b86d97989eaaacf3ada9f9428bae3a1a01339b85fa541680271c3a000000006b48304502203c17278401d3f7e6ab56597b6b88c783db5aa534897954e92a95732b5d714a860221008be28d72988a8ba69ff3889337468df0e83612e9cfbead9118fb8f4decfb3e93012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff75068f094fd516d1658dac8c88ae027852a84423831b780925fb3d591a308227010000006b48304502203ce1a111748534bc601cc4a879f4097b098f7622d28a3da89ac7b90f3b29ee52022100b7de6a03241f6fea37179c3b2f68f45b69c963edc19dc0b3b0350bb8ffbd9063012102c64a6d411eef9f79890d317ad72329608be5f58f6757e1c5c6d4f21076345ddcfffffffffc844c99e1adab4677dda6b5def33ce549293a40d08da6a8c36fb9274c76ba43000000006c493046022100ee2b946560aced0633a5151f1fa5dd0249d68bef420e43e1f7edfd0e83619cfd022100a433121fb022efb69043310bd982a842e7f5be737069d3535962ecd78c4954070121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff32f65f550d4ef09e451303af1d2f9964b4f62e7b6562d5dbe23d4be89ae32e36010000006b483045022100bb4b1af8c22e51e9a3f71fb2cd883746e3812cbfd6a0695bbd22e53d573cb6f20220539e478519411e1d277a844810ab756004cc7755ff585cda7aa2c9d93ab4570b01210290a17b828f33417e7e3cb179bd50a621c8381964e6e239e91eacf3f414018770fffffffff6a6dafe0ae32f6c891de86c4aa12b318eeb89ca4d8792906ae03fd0db04c76f010000006b48304502203dfe2f1d58fdcab2f67f079c14a26c69e88718d38615d5de8230ee74c1e55d9e022100bc7651cf77f142366b502c65c59790a3d2cfa52852feab04212d3ea68e040be0012103214083806ce8aebf35544845c73f1e9d4dcad2fc6057e6cda963498f5420fef9ffffffff11c3e0e2a520bd829fab9b4311e603de79ce1304e1f28204334f79d1fd2c9138010000006b48304502206196ca600a02bf7fd210489e520b915b521dfa84a37bf56f2022aa8e89d62e6f022100949aabaafe92aaa8e207f0699a4745179fdc34a475d5c783785a58744ed5dd410121033fd9e31bd2bdc7029d6f1cca55655c4b484aca7fdea11547b37a4aeaf347e132ffffffff54dffe807c0b935528100313322949738717dc2af2f374f2a72af24b82291f70000000006b483045022100b84abff6f636082d2b85da4de25de13388a7901f0df5b87d871305e694667b4f0220477619c2140bc2d7dfab0d2b7d49f0729afb951831c7a64ee4bede7d8a46960b012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff9923148b409a0888e42612e964216eb575b8fe4d0cfe5aad84323962c219373b000000006b483045022100cc88b67cce1655c38c60ada140b9c34343411cc309c45410edf2b4f133520c03022044c3554a294f6a412219b710689d67c5d8519fc6a139ee311985a14b8b55fd6f012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff5bb09bbcbdd756af1f08457f7c1319aa67d5488f359b58e6f36920b808c789bb010000006a473044022051b255884c8f118780e394c054c5df12d813e3b9983a9854c89e7ffd015cbf5702200ed58fac38a91e19d755d30d910a04aa29848608b72f99aef68e0ecedcbdca53012103f57996ef25762717a75b6d75f0166a33f775783766513118b5476933d7af8078ffffffffc3c978e2e413506b75b7882cc38cf6f40d199c7226f73b5e4ab8295703fd4b03d50000006b48304502201a07c4ce0f76c4f5d96c45811bfa08c00483c987e1297324f40136a4c452c306022100d553873b6b3c20ae2b6a588704bae9daa5093b17283e0b196a27d6e4022ff8a50121032beebbe7e386f1fd27a9a3e59640deaa5f60835ab789012175c0f517149c77e3ffffffff3944343f32f93d43752bea7d916571e236295fbff53c7a9133d09f741116fac5000000006b483045022074aac638f77fed744feb1e99f4a71f20278686fc2f91264058facd9983cde9fb0221008250b8d62dbb8d3954517f9f80e236d1c74720723459b5d69656dadf781ee2e9012102cda10f1505ff6a3613f6aa31becba07e44eddc25110ae083e360577556f0178dffffffff781119976951dfc6f8a617077068fcb623475acef7eff09808a5ef3281a0ef70010000006b48304502200236e5c6b0787756449043fd413307c176be2b39fb7da11a18e10708a72877fe022100836b99f32532039e6326cbf282cbf78140c53883c474443bd6e6145e52dfb08d012103dd033367f07aee4f365a47cdf3c45147887492c6d20788970296d3b94eb1cfd8ffffffff1365487900000000001976a9143f6cd41f7caeda87b86bc9e009295f179612f45488acc7440f00000000001976a914df90390bee06889ed003b3c4d024a9fd211cf96788ace0ee4504000000001976a9141d8aa01e6628f333fd6fd9d3b88c3f761869caa488acd5080200000000001976a91412d24a8a61e1cd37825fd31bd61eebc2c32ad77688ac0842b582000000001976a9147fb7cccd54bf322ddae63b6b2e20a3624622091888acd5113916000000001976a914e75fa783b5b319578a53f2f18c37d88513d060b088ac80969800000000001976a9140ce8c479dad1b78baeea2217af17655b908b59b888ace8fb1204000000001976a9141a80a0ee9b4f1d03c3fb2220e4124d441431d41888ac00e1f505000000001976a914b1fdf35dd37a1a5359ad829f5f43760cd1ea61d588ac80626a94000000001976a9142a929cae46f0b4e5742ae38d6040f11a2b70e7d188ac109d0a02000000001976a914a4b5bfbe26ef9ac8d6e06738b50065b25f3dcce288acab110400000000001976a9146fbc4dd98c194853dc9a3e6f195bddad8eef609788ac00e1f505000000001976a914e1733c6b9e4c98f4ddc19370dd5cae0727af04e788ac5d44d502000000001976a9147514615f455bdfb8b9da74c7707ef0426606c33288ac404b4c00000000001976a914166e78015832ba760593bb292993391d4554a39a88acf0190b01000000001976a91409859ea62e00e0531873c135e37efbeca610d36788ace2fcae8f000000001976a914b40d92da29c478049a8d15bfd85b6ce230863df288acc07a3e22000000001976a9141e12d7845e76857bfba94079c1faca68b3ae24c088ac9e130e01000000001976a9144bdb0b7712726c2684461cd4e5b08934def0187c88ac00000000';



//$t = Transaction::fromHex($tx);

*//*
$priv = PrivateKey::generateNew();
// Tx for sighash test!
$script = new Script;
$script->set('76a91416489ece44cc457e14f4e882fd9a0ae082fdf6c688ac');
$o = new TransactionOutput();
$o->setScript($script);

$tx = '01000000010a74a5750934ce563a9f18812b73dea945e3796d08be5e2c7e817197b4b0665b000000006a47304402203e2b56c1728f6cdcd531d006f7a17e6608513432113290229762de1d1bc0e76902205a9a41c196845d40dc98b67641fa2a1ae52f714094c9ad1e6b99514fd567d187012103161f0ec2a99876733c7b7f63bdb3cede0980e39f18abd50adad2774bd8fe0917ffffffff02426f0f00000000001976a91402a82b3afaff3c4113d86005f7029301c770c61188acbd0e3f0e010000001976a9146284bcf16e0507a35d28c1608ee1708ed26c839488ac00000000';
$t = Transaction::fromHex($tx);

$s = new \Bitcoin\Signature\SignatureHash($t);
$h = $s->calculateHash($o, 0);

while(true) {
    $sig = $priv->sign($h);
    if (ord(substr($sig->serialize(), 5, 1)) == 0x00) {
        echo "found\n";
    }
    Bitcoin\Signature\Signature::isCanonical(new Buffer($sig->serialize()));


}


//$h = Bitcoin\Signature\Signature::fromHex('304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b01');

//$sig = new Bitcoin\Signature\Signature('15148391597642804072346119047125209977057190235171731969261106466169304622925', '29241524176690745465970782157695275252863180202254265092780741319779241938696');
//echo $sig->serialize('hex')."\n";


/*
echo "\nTEST DETERMINSTIC\n";
$priv = new PrivateKey('f8b8af8ce3c7cca5e300d33939540c10d45ce001b8f252bfbc57ba0342904181');
$message = new Buffer('Alan Turing');
$k = new \Bitcoin\Signature\K\Deterministic($priv, $message);

$kk= $k->getK();
var_dump($kk->serialize('int'));
//echo $k->getK()."\n";
(/)*/
/*
$tx = '01000000'.
    '01'.
    '0000000000000000000000000000000000000000000000000000000000000000FFFFFFFF'.
    '4D'.
    '04FFFF001D0104455468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73'.
    'FFFFFFFF'.
    '01'.
    '00F2052A01000000'.
    '43'.
    '4104678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5FAC'.
    '00000000';

$header = '01000000'.
    '0000000000000000000000000000000000000000000000000000000000000000' .
    '3BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A' .
    '29AB5F49'.
    'FFFF001D'.
    '1DAC2B7C';

$block = $header.
    '01'.
    $tx;

$s = Script::payToPubKey(\Bitcoin\Key\PublicKey::fromHex('04678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5F'));

$b = Block::fromHex($block);
$tx = Transaction::fromHex($tx);
echo "Satoshis tx: ".$tx->getTransactionId()."\n";

$m = new MerkleRoot($b);
echo "merkle: ".$m->calculateHash()."\n";
*/
/*8
$inputs = $tx->getInputs();

echo $inputs[0]->getScript()->getAsm()."\n";

$genesisMining = new \Bitcoin\Block\GenesisMiningBlockHeader();
$genesisMining->setBits(Buffer::hex('FFFF001D'));

$string = Buffer::hex("5468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73");
$miner = new \Bitcoin\Miner\Miner($genesisMining, $s, $string, '1231006505');
$miner->run();

//print_r($miner);
//print_r($b);

//$tx = '01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff2703510c05062f503253482f0477b46d54085000538d500100b00d4254434368696e6120506f6f6c000000000172480f95000000001976a9142c30a6aaac6d96687291475d7d52f4b469f665a688ac00000000';



$genesis = new BlockHeader;
$genesis
    ->setVersion('1')
    ->setPrevBlock ('0000000000000000000000000000000000000000000000000000000000000000')
    ->setMerkleRoot(Buffer::hex('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b'))
    ->setTimestamp('1231006505')
    ->setBits(Buffer::hex('1d00ffff'))
    ->setNonce('2083236893');
echo $genesis->serialize('hex')."\n";
echo "Satoshi Genesis - \n".$genesis->getBlockHash()."\n\n";

$genesis = new BlockHeader;
$genesis
    ->setVersion('1')
    ->setPrevBlock ('0000000000000000000000000000000000000000000000000000000000000000')
    ->setMerkleRoot('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b')
    ->setTimestamp('1231006505')
    ->setBits(Buffer::hex('1d00ffff'))
    ->setNonce('2083236893');
echo $genesis->serialize('hex')."\n";
echo "Satoshi Genesis - \n".$genesis->getBlockHash()."\n\n";
echo " satoshi merkle - \n".$genesis->getMerkleRoot()."\n";
//print_r($genesis);



*/

//echo $block."\n";
//$b = Bitcoin\Block\Block::fromHex($block);
//print_r($b);
/*
$buffer = Buffer::hex($tx);
$parser = new Parser($buffer);
$transaction = new \Bitcoin\Transaction\Transaction();
$transaction->fromParser($parser);*/
/*

$input = new TransactionInput;
$input->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');

$outScript = new Script;
$outScript
    ->op('OP_HASH160')
    ->push('000000000000000000000000000000000000000000000000')
    ->op('OP_EQUAL');

$output = new TransactionOutput;
$output
    ->setScript($outScript)
    ->setValue($math->cmp(50, 100000000));

$coinbase = new Transaction;
$coinbase->addInput($input);
$coinbase->addOutput($output);



//$header = new \Bitcoin\Block\BlockHeader();
//$header->setPrevBlock('0000000000000000000000000000000000000000000000000000000000000000');
//$header->setMerkleRoot('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b');
//$header->setTimestamp($math->hexDec('c7f5d74d'));
//$header->setBits(Buffer::hex('f2b9441a'));
*/
//$b = Bitcoin\Block\Block::fromHex($block);
/*
$header = new \Bitcoin\Block\BlockHeader();
$header->setVersion(1);
$header->setPrevBlock('00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81');
$header->setMerkleRoot('2b12fcf1b09288fcaff797d71e950e71ae42b91e8bdb2304758dfcffc2b620e3');
$header->setTimestamp($math->hexDec('c7f5d74d'));
$header->setBits(Buffer::hex('f2b9441a'));

$found = false;
$i = '1117865621';
while ($found == false) {
    $copy = $header;
    $nonceStr = str_pad(dechex($i), 8, '0', STR_PAD_LEFT);
//$nonceStr = '42a14695';
    $nonce = Buffer::hex($nonceStr);
    $copy->setNonce($nonce);

print_r($copy);
echo "SERIALIZE\n";
echo $copy->serialize('hex')."\n";
    $hash = Hash::sha256d($copy->serialize());

    if($nonceStr == '42a14695')
        break;
    $i++;
}

echo $hash."\n";
echo "done\n";
*/
/*
$coinbase = new Transaction;
$input = new TransactionInput;
$input->setTransactionId('0000000000000000000000000000000000000000000000000000000000000000');
$input->setVout(0);
$input->setScriptBuf(new Buffer('asdf123lol'));
$coinbase->addInput($input);

$pmt = array(
    array(
        'address' => 'RwUpzVNhxzPEin7uqUsaDaQ3wFtc2BxHGu',
        'amount' => '10100000'
    ),
    array(
        'address' => 'RhDYy4GLWh5s6Tx7Sea2mGmp2j559qNPKc',
        'amount' => '10100000'
    ),
    array(
        'address' => 'ReiayD6R4ZfnQN7rkkrqJimmP3CanygeLL',
        'amount' => '10100000'
    ),
    array(
        'address' => 'Rts7ewGF9cWQmvFLgLwiCA94Wzh52BwP6S',
        'amount' => '10100000'
    ),
    array(
        'address' => 'RfC1mM3bGrsfaAVYwneGQ8z8Hy2xcBZ8gD',
        'amount' => '10100000'
    ),
    array(
        'address' => 'RkhTwcChzH2QhnU6JhN71nFAufDn9p3xtG',
        'amount' => '10100000'
    ),
    array(
        'address' => 'RqDVB4Qvvig8oeUNGW1dyN1scUZNYWzqLn',
        'amount' => '10100000'
    ),
    array(
        'address' => 'Rern8oRnetrB7D43kuuZjam64KTwWypQk5',
        'amount' => '10100000'
    )
);

for($i = 0; $i < count($pmt); $i++)
{
    $address = Base58::decodeCheck($pmt[$i]['address']);
    $hash = substr($address, 0, 40);
    $output = new TransactionOutput;
    $output
        ->setValue($pmt[$i]['amount'])
        ->setScript(
        (new Script)
        ->op('OP_DUP')
        ->op('OP_HASH160')
        ->push($hash)
        ->op('OP_EQUALVERIFY')
        ->op('OP_CHECKSIG')
    );
    $coinbase->addOutput($output);
}
echo $coinbase->serialize('hex');

$merkle = (new Parser())
    ->writeBytes(32, $coinbase->getTransactionId())
    ->getBuffer()->serialize('hex');


$header = new \Bitcoin\Block\BlockHeader();
$header->setPrevBlock('00000000000008a3a41b85b8b29ad444def299fee21793cd8b9e567eab02cd81');
$header->setMerkleRoot($merkle);
$header->setTimestamp($math->hexDec(bin2hex(openssl_random_pseudo_bytes(8))));
$header->setBits(Buffer::hex('f2b9441a'));

echo "\n";
$found = false;
$i = '1';
$s = true;
$lastStamp = microtime($s);
while ($found == false) {
    $copy  = $header;
    $nonce = Buffer::hex(str_pad(dechex($i), 8, '0', STR_PAD_LEFT));
    $copy->setNonce($nonce);

    //print_r($copy);

    $hash = Hash::sha256d($copy->serialize(), true);
    $h = new Buffer($hash);
    //$h = (new Parser())
       // ->writeBytes(32, $hash)
      //  ->getBuffer();


    //echo "Block hash? ".$h."\n";
    if(substr($h->serialize('hex'), -4) === '0000')
    {
        echo ":OOO ".$h." - ".$h->serialize('hex')."\n";
        var_dump($h);
        break;
    }

    //if($nonceStr == '42a14695')
      //  break;
    if($i % 10000 == 0) {
        echo "last 10,000 hashes took ". 10000/(microtime($s) - $lastStamp) ."\n";

        $lastStamp = microtime($s);
    }
    $i++;
}

$block = new \Bitcoin\Block\Block();
$block->setHeader($header);
$block->setTransactions(array($coinbase));

echo $h."\n";
echo "Data was: ". $copy->serialize('hex')."\n";
echo "\n\n";
echo $block->serialize('hex')."\n";

echo "done\n";*/
/*
$b = new \Bitcoin\Block\Block();
$b->setTransactions(array($t));

$h = new \Bitcoin\Block\MerkleRoot($b);
echo $h->calculateHash()."\n";
*/
/**
 * 01000000 - version
0000000000000000000000000000000000000000000000000000000000000000 - prev block
3BA3EDFD7A7B12B27AC72C3E67768F617FC81BC3888A51323A9FB8AA4B1E5E4A - merkle root
29AB5F49 - timestamp
FFFF001D - bits
1DAC2B7C - nonce
01 - number of transactions
01000000 - version
01 - input
0000000000000000000000000000000000000000000000000000000000000000FFFFFFFF - prev output
4D - script length
04FFFF001D0104455468652054696D65732030332F4A616E2F32303039204368616E63656C6C6F72206F6E206272696E6B206F66207365636F6E64206261696C6F757420666F722062616E6B73 - scriptsig
FFFFFFFF - sequence
01 - outputs
00F2052A01000000 - 50 BTC
43 - pk_script length
4104678AFDB0FE5548271967F1A67130B7105CD6A828E03909A67962E0EA1F61DEB649F6BC3F4CEF38C4F35504E51EC112DE5C384DF7BA0B8D578A4C702B6BF11D5FAC - pk_script
00000000 - lock time

 */





/*






$pk = new PrivateKey('cca9fbcc1b41e5a95d369eaa6ddcff73b61a4efaa279cfc6567e8daa39cbaf50');

$m = \Normalizer::normalize('Satoshi Nakamoto', Normalizer::FORM_KD);
echo "normalized\n";
echo $m."\n";
//$m = Buffer::hex($m);
//$m = 'Satoshi Nakamoto';
$m = Hash::sha256("sample", true);
echo "$m\n --ed ndormalized\n";

$hmac = new Bitcoin\Util\HMAC_DRBG('sha256', $pk);
$hmac->update($pk->serialize() . $m);

$h = $hmac->bytes(32);
echo $h;
//print_r($hmac);
*/


/*
$m = "Satoshi Nakamoto";
$m = utf8_encode($m);
echo "m $m\n";
echo "h ".Bitcoin\Crypto\Hash::sha256($m)."\n";
echo "------------------------\n";
//$pk = new PrivateKey('8F8A276C19F4149656B280621E358CCE24F5F52542772691EE69063B74F15D15');
$pk = new PrivateKey('0000000000000000000000000000000000000000000000000000000000000001');


$b = Buffer::hex(Bitcoin\Crypto\Hash::sha256($m));

$k = new Bitcoin\Crypto\HMACDRBG('sha256', new Buffer($pk->serialize() . $b->serialize()));


$K = $k->bytes(32);
echo $K."\n";





*/






/*
if($tx == $t->serialize('hex'))
{
    echo "\nMatch!\n";
}
//$o = $t->getOutputs();
//echo $t->serialize('hex');
//print_r($t->toArray());
echo "\n\nID: ".$t->getTransactionId()."\n";
*/
