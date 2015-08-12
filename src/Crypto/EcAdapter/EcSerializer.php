<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;

class EcSerializer
{
    const PATH_PHPECC = 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\\';
    const PATH_SECP256K1 = 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\\';

    /**
     * @var array
     */
    private static $serializerInterface = [
        Serializer\Key\PrivateKeySerializerInterface::class,
        Serializer\Key\PublicKeySerializerInterface::class,
        Serializer\Signature\CompactSignatureSerializerInterface::class,
        Serializer\Signature\DerSignatureSerializerInterface::class
    ];

    /**
     * @var array
     */
    private static $serializerImpl = [
        'Serializer\Key\PrivateKeySerializer',
        'Serializer\Key\PublicKeySerializer',
        'Serializer\Signature\CompactSignatureSerializer',
        'Serializer\Signature\DerSignatureSerializer'
    ];

    /**
     * @var array
     */
    private static $map = [];

    /**
     * @var array
     */
    private static $cache = [];

    /**
     * @param $interface
     * @return mixed
     */
    public static function getImplRelPath($interface)
    {
        if (empty(self::$map)) {
            if (!in_array($interface, self::$serializerInterface)) {
                throw new \InvalidArgumentException('Invalid interface');
            }

            $cInterface = count(self::$serializerInterface);
            if ($cInterface !== count(self::$serializerImpl)) {
                throw new \InvalidArgumentException('Invalid serializer interface map');
            }

            for ($i = 0; $i < $cInterface; $i++) {
                $iface = self::$serializerInterface[$i];
                $ipath = self::$serializerImpl[$i];
                self::$map[$iface] = $ipath;
            }
        }

        return self::$map[$interface];
    }

    /**
     * @return array
     */
    public static function getImplPaths()
    {
        return [
            'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter' => 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\\',
            'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\Adapter\EcAdapter' => 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\\'
        ];
    }

    /**
     * @param EcAdapterInterface $adapter
     * @return mixed
     */
    public static function getAdapterImplPath(EcAdapterInterface $adapter)
    {
        $paths = static::getImplPaths();
        $class = get_class($adapter);
        if (!isset($paths[$class])) {
            throw new \RuntimeException('Unknown EcAdapter');
        }

        return $paths[$class];
    }

    /**
     * @param EcAdapterInterface $adapter
     * @param $interface
     */
    public static function getSerializer(EcAdapterInterface $adapter, $interface)
    {
        if (!isset(self::$cache[$interface])) {
            $classPath = self::getAdapterImplPath($adapter) . self::getImplRelPath($interface);
            $class = new $classPath($adapter);
            self::$cache[$interface] = $class;
        }

        return self::$cache[$interface];
    }
}
