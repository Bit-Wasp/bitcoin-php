<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter;

use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;

class EcSerializer
{
    const PATH_PHPECC = 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\\';
    const PATH_SECP256K1 = 'BitWasp\Bitcoin\Crypto\EcAdapter\Impl\Secp256k1\\';

    /**
     * @var string[]
     */
    private static $serializerInterface = [
        'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PrivateKeySerializerInterface',
        'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Key\PublicKeySerializerInterface',
        'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\CompactSignatureSerializerInterface',
        'BitWasp\Bitcoin\Crypto\EcAdapter\Serializer\Signature\DerSignatureSerializerInterface'
    ];

    /**
     * @var string[]
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
     * @var bool
     */
    private static $useCache = true;

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
        if (0 === count(self::$map)) {
            if (!in_array($interface, self::$serializerInterface, true)) {
                throw new \InvalidArgumentException('Interface not known');
            }

            $cInterface = count(self::$serializerInterface);
            if ($cInterface !== count(self::$serializerImpl)) {
                throw new \InvalidArgumentException('Invalid serializer interface map');
            }

            for ($i = 0; $i < $cInterface; $i++) {
                /** @var string $iface */
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
     * @param bool|true $useCache
     * @return mixed
     */
    public static function getSerializer(EcAdapterInterface $adapter, $interface, $useCache = true)
    {
        if (isset(self::$cache[$interface])) {
            return self::$cache[$interface];
        }

        $classPath = self::getAdapterImplPath($adapter) . self::getImplRelPath($interface);
        $class = new $classPath($adapter);

        if ($useCache && self::$useCache) {
            self::$cache[$interface] = $class;
        }

        return $class;
    }

    /**
     * Disables caching of serializers
     */
    public static function disableCache()
    {
        self::$useCache = false;
    }
}
