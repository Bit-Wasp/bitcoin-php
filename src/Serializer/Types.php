<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Serializer;

use BitWasp\Buffertools\CachingTypeFactory;
use BitWasp\Buffertools\Types\ByteString;
use BitWasp\Buffertools\Types\Int128;
use BitWasp\Buffertools\Types\Int16;
use BitWasp\Buffertools\Types\Int256;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\Int64;
use BitWasp\Buffertools\Types\Int8;
use BitWasp\Buffertools\Types\Uint128;
use BitWasp\Buffertools\Types\Uint16;
use BitWasp\Buffertools\Types\Uint256;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\Uint64;
use BitWasp\Buffertools\Types\Uint8;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\VarString;

class Types
{
    /**
     * @return CachingTypeFactory
     */
    public static function factory()
    {
        static $factory;
        if (null === $factory) {
            $factory = new CachingTypeFactory();
        }

        return $factory;
    }

    /**
     * @param int $length
     * @return ByteString
     */
    public static function bytestring($length)
    {
        return static::factory()->{__FUNCTION__}($length);
    }

    /**
     * @param int $length
     * @return ByteString
     */
    public static function bytestringle($length)
    {
        return static::factory()->{__FUNCTION__}($length);
    }

    /**
     * @return Uint8
     */
    public static function uint8()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint8
     */
    public static function uint8le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint16
     */
    public static function uint16()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint16
     */
    public static function uint16le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint32
     */
    public static function uint32()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint32
     */
    public static function uint32le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint64
     */
    public static function uint64()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint64
     */
    public static function uint64le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint128
     */
    public static function uint128()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint128
     */
    public static function uint128le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint256
     */
    public static function uint256()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Uint256
     */
    public static function uint256le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int8
     */
    public static function int8()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int8
     */
    public static function int8le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int16
     */
    public static function int16()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int16
     */
    public static function int16le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int32
     */
    public static function int32()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int32
     */
    public static function int32le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int64
     */
    public static function int64()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int64
     */
    public static function int64le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int128
     */
    public static function int128()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int128
     */
    public static function int128le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int256
     */
    public static function int256()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return Int256
     */
    public static function int256le()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return VarInt
     */
    public static function varint()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @return VarString
     */
    public static function varstring()
    {
        return static::factory()->{__FUNCTION__}();
    }

    /**
     * @param callable $reader
     * @return \BitWasp\Buffertools\Types\Vector
     */
    public static function vector(callable $reader)
    {
        return static::factory()->vector($reader);
    }
}
