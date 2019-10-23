<?php

declare(strict_types=1);

namespace Unilend\Service;

use InvalidArgumentException;

class FrontRouter
{
    private const TRANSFER_PROTOCOL = 'https';

    private const ROUTING = [
        'profil' => 'profile',
    ];

    /** @var string */
    private $frontUrl;

    /**
     * @param string $frontUrl
     */
    public function __construct(string $frontUrl)
    {
        $this->frontUrl = $frontUrl;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function generate(string $name): string
    {
        if (false === isset(self::ROUTING[$name])) {
            throw new InvalidArgumentException(sprintf('Unable to generate a URL for the named route "%s" as such route does not defined.', $name));
        }

        return self::TRANSFER_PROTOCOL . '://' . $this->frontUrl . self::ROUTING[$name];
    }
}
