<?php

declare(strict_types=1);

namespace KLS\Core\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use KLS\Core\Model\Bitmask;

class BitmaskType extends Type
{
    public const BITMASK = 'bitmask';

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param int|null $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Bitmask
    {
        return null === $value ? null : new Bitmask((int) $value);
    }

    /**
     * @param Bitmask|null $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return null === $value ? null : $value->get();
    }

    public function getName(): string
    {
        return self::BITMASK;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
