<?php

use Bitcoin\Util\Base58;
use Bitcoin\Util\Math;
use Bitcoin\ScriptInterpreter;

use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Network;
use Bitcoin\Transaction;

use Bitcoin\HierarchicalKey;

use Bitcoin\PrivateKey;
use Bitcoin\Script;

require_once "vendor/autoload.php";

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
$t = Transaction::fromHex('010000000462442ea8de9ee6cc2dd7d76dfc4523910eb2e3bd4b202d376910de700f63bf4b000000008b48304502207db5ea602fe2e9f8e70bfc68b7f468d68910d2ff4ac50294fc80109e254f317f022100a68a66f23406fdfd93025c28ffef4e79260283335ce39a4e8d0b52c5ee41913b014104f8de51f3b278225c0fe74a856ea2481e9ad4c9385fc10cefadaa4357ecd2c4d29904902d10e376546500c127f65d0de35b6215d49dd1ef6c67e6cdd5e781ef22ffffffff10aa2a8f9211ab71ffc9df03d52450d89a9de648fdfd75c0d20e4dcb1be29cfd020000008b483045022100d088af937bd457903391023c468bdbb9dc46681c3c83ab7b101c26a41524a0e20220369597fa4737aa4408469fec831b5ce53caee8e9fec81282376c6f592be354fb01410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff067b1e03bd8edc0496b41af958fead9d57489fa12d23f4b341ded9b78d8cb114000000008b483045022009538bca3258eb4175faa7121dca68b51d95f2ed7d24278f03e2d88077d92815022100b8706672c585e8607e18d235e69548cd28736adfa9ce4f8f5f3baffc5aad091b01410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff7f6d4bbb8f0d9b8bcad2e431c270aac63aa9caaa880dbd1688e39b6ac0d45ff4020000008b48304502203da091fed8fc71b3c859ee1dfe9c3d0e64915502af057357effa1ae4d1e0dbbf02210090fd964dfe7286b1ab0af3e8d6686c7826039eb0b46bac9803af367f080f38e401410445e476b3ea4559019c9f44dc41c103090473ce448f421f0000f2d630a62bb96af64f0fde21c84e4c5a679c43cb7b74e520dad662abfbedc86cc27cc03036c2b0ffffffff0200b79ba7000000001976a914b5ac94f60f833b1e2dab9bc5f7895687bd750e8688acb0720200000000001976a9141b16cf7372a97b42533605e14616b6338caba8e888ac00000000');
print_r($t);
$o = $t->getOutputs();
echo $o[1]->getValue()->serialize('int');
//echo $t->serialize('hex');