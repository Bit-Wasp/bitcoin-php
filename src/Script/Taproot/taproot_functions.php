<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Taproot;

use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_LEAF_MASK;

function taprootTreeHelper(array $scripts): array
{
    if (is_array($scripts) && count($scripts) == 1) {
        if (count($scripts[0]) == 2 && is_int($scripts[0][0]) && $scripts[0][1] instanceof \BitWasp\Bitcoin\Script\ScriptInterface) {
            list ($leafVersion, $script) = $scripts[0];
            if (!($script instanceof \BitWasp\Bitcoin\Script\ScriptInterface)) {
                throw new \RuntimeException("leaf[1] not a script");
            }
            $scriptBytes = $script->getBuffer();
            $preimg = new Buffer(pack("C", $leafVersion&TAPROOT_LEAF_MASK) . Buffertools::numToVarIntBin($scriptBytes->getSize()) . $scriptBytes->getBinary());
            return [
                [
                    [$leafVersion, $script, new Buffer() /*leafcontrol*/]
                ],
                Hash::taggedSha256("TapLeaf", $preimg),
            ];
        } else {
            return taprootTreeHelper($scripts[0]);
        }
    }

    $split = intdiv(count($scripts), 2);
    $listLeft = array_slice($scripts, 0, $split);
    $listRight = array_slice($scripts, $split);

    list ($left, $left_hash) = taprootTreeHelper($listLeft);
    list ($right, $right_hash) = taprootTreeHelper($listRight);
    /** @var BufferInterface $left_hash */
    /** @var BufferInterface $right_hash */
    $left2 = [];
    foreach ($left as list($version, $script, $control)) {
        $left2[] = [$version, $script, Buffertools::concat($control, $right_hash)];
    }
    $right2 = [];
    foreach ($right as list($version, $script, $control)) {
        $right2[] = [$version, $script, Buffertools::concat($control, $left_hash)];
    }
    $hash = Hash::taggedSha256("TapBranch", Buffertools::concat(...Buffertools::sort([$left_hash, $right_hash])));

    return [array_merge($left2, $right2), $hash];
}

function taprootConstruct(\BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface $xonlyPubKey, array $scripts): array
{
    $xonlyKeyBytes = $xonlyPubKey->getBuffer();
    if (count($scripts) == 0) {
        return [ScriptFactory::scriptPubKey()->taproot($xonlyKeyBytes), [], []];
    }
    list ($ret, $hash) = taprootTreeHelper($scripts);

    $tweak = Hash::taggedSha256("TapTweak", new Buffer($xonlyKeyBytes->getBinary() . $hash->getBinary()));
    $tweaked = $xonlyPubKey->tweakAdd($tweak);
    $controlList = [];
    $scriptList = [];
    foreach ($ret as list ($version, $script, $control)) {
        $scriptList[] = $script;
        $controlList[] = pack("C", ($version & 0xfe) + ($tweaked->hasSquareY() ? 0 : 1)) .
            $xonlyKeyBytes->getBinary() .
            $control->getBinary();
    }
    return [ScriptFactory::scriptPubKey()->taproot($tweaked->getBuffer()), $tweak, $scriptList, $controlList];
}

