<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Taproot;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\XOnlyPublicKeyInterface;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffertools;
use const BitWasp\Bitcoin\Script\Interpreter\TAPROOT_LEAF_MASK;

function hashTapLeaf(int $leafVersion, BufferInterface $scriptBytes): BufferInterface
{
    $ret = Hash::taggedSha256("TapLeaf", new Buffer(
        pack("C", $leafVersion&TAPROOT_LEAF_MASK) .
        Buffertools::numToVarIntBin($scriptBytes->getSize()) .
        $scriptBytes->getBinary()
    ));
    return $ret;
}

function hashTapBranch(BufferInterface $left, BufferInterface $right): BufferInterface
{
    $hash = Hash::taggedSha256("TapBranch", Buffertools::concat(...Buffertools::sort([$left, $right])));
    return $hash;
}

function taprootTreeHelper(array $scripts): array
{
    if (is_array($scripts) && count($scripts) == 1) {
        if (count($scripts[0]) == 2 && is_int($scripts[0][0]) && $scripts[0][1] instanceof \BitWasp\Bitcoin\Script\ScriptInterface) {
            list ($leafVersion, $script) = $scripts[0];
            if (!($script instanceof \BitWasp\Bitcoin\Script\ScriptInterface)) {
                throw new \RuntimeException("leaf[1] not a script");
            }

            $leafHash = hashTapLeaf($leafVersion, $script->getBuffer());
            return [
                [
                    [$leafVersion, $script, new Buffer() /*leafcontrol*/]
                ],
                $leafHash,
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

    $hash = hashTapBranch($left_hash, $right_hash);
    return [array_merge($left2, $right2), $hash];
}

function taprootConstruct(XOnlyPublicKeyInterface $internalKey, array $scripts): array
{
    $keyBytes = $internalKey->getBuffer();
    if (count($scripts) == 0) {
        return [ScriptFactory::scriptPubKey()->taproot($keyBytes), null, [], []];
    }

    list ($ret, $hash) = taprootTreeHelper($scripts);
    $tweak = Hash::taggedSha256("TapTweak", new Buffer($keyBytes->getBinary() . $hash->getBinary()));
    $outputKey = $internalKey->tweakAdd($tweak);
    $controlList = [];
    $scriptList = [];
    foreach ($ret as list ($version, $script, $control)) {
        $scriptList[] = $script;
        $controlList[] = chr(($version & TAPROOT_LEAF_MASK) + ($outputKey->hasSquareY() ? 0 : 1)) .
            $keyBytes->getBinary() .
            $control->getBinary();
    }
    return [ScriptFactory::scriptPubKey()->taproot($outputKey->getBuffer()), $tweak, $scriptList, $controlList];
}
