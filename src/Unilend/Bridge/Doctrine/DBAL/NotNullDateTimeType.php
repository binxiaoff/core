<?php

namespace Unilend\Bridge\Doctrine\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class NotNullDateTimeType extends DateTimeType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value || $value instanceof \DateTime) {
            return $value;
        }

        if ('0000-00-00' === substr($value, 0, 10)) {
            return null;
        }

        return parent::convertToPHPValue($value, $platform);
    }
}
