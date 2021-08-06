<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Monolog\Processor;

use Monolog\Processor\ProcessorInterface;

/**
 * Add current environment in record and in extra.
 */
class EnvironmentProcessor implements ProcessorInterface
{
    private $environment;

    public function __construct(string $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @return array The processed records
     */
    public function __invoke(array $record): array
    {
        $record['environment']          = $this->environment;
        $record['extra']['environment'] = $this->environment;

        return $record;
    }
}
