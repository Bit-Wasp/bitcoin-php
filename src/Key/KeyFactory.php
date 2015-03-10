<?php

namespace Afk11\Bitcoin\Key;

use Afk11\Bitcoin\Bitcoin;
use Afk11\Bitcoin\Math\Math;
use Mdanter\Ecc\GeneratorPoint;

class KeyFactory
{
    public static function fromKeyAndOffset(KeyInterface $key, $offset, Math $math = null, GeneratorPoint $generator = null)
    {
        $math = $math ?: Bitcoin::getMath();
        $generator = $generator ?: Bitcoin::getGenerator();

        if ($key->isPrivate()) {
            return PrivateKeyFactory::fromInt(
                $math->mod(
                    $math->add(
                        $offset,
                        $key->getSecretMultiplier()
                    ),
                    $generator->getOrder()
                ),
                true,
                $math,
                $generator
            );
        } else {
            return PublicKeyFactory::fromPoint(
                $generator// Get the EC point for this offset
                ->mul(
                    $offset
                )
                // Add it to the public key
                    ->add(
                        $key->getPoint()
                    ),
                true,
                $math,
                $generator
            );
        }
    }
}
