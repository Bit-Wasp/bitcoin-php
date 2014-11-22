<?php

use Bitcoin\Base58;
use Bitcoin\Math;
use Bitcoin\ScriptInterpreter;
use Bitcoin\Buffer;
use Bitcoin\Network;
use Bitcoin\Transaction;
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

$rs = new Script();
$rs
    ->op('2')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->push('03da14f7693c61ea1413172c1e06fd187906f8d92380b6e68deeec9fcea23bd010')
    ->op('3')
    ->op('CHECKMULTISIG');



$hex = $rs->serialize('hex');
echo $hex."\n";


print_r($rs->getAsm());

//echo "----------------------------------------\n";

//print_r($p1);
$i = new ScriptInterpreter($rs);
//echo $p1."\n";
//print_r($p1->getAsm());echo "\n";
echo "\n";

$i->run();

$j = new ScriptInterpreter(Script::payToScriptHash($rs));
$j ->run();
print_r($j);