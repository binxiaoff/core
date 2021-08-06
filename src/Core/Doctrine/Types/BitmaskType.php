<?php

declare(strict_types=1);

namespace Unilend\Core\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Unilend\Core\Model\Bitmask;

class BitmaskType extends Type
{
    public const BITMASK = 'bitmask';

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|null $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Bitmask
    {
        return null === $value ? null : new Bitmask((int) $value);
    }

    /**
     * {@inheritdoc}
     *
     * @param Bitmask|null $value
     *
     * @return int
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        return null === $value ? null : $value->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return self::BITMASK;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
