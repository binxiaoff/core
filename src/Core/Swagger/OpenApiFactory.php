<?php

declare(strict_types=1);

namespace KLS\Core\Swagger;

use ApiPlatform\Core\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\Core\OpenApi\Model\Operation;
use ApiPlatform\Core\OpenApi\Model\PathItem;
use ApiPlatform\Core\OpenApi\OpenApi;

class OpenApiFactory implements OpenApiFactoryInterface
{
    private OpenApiFactoryInterface $decorated;

    public function __construct(OpenApiFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $openApi = $openApi->withExtensionProperty('x-visibility', 'hide');

        $this->removeRoutesWithVisibilityHide($openApi);

        return $openApi;
    }

    private function removeRoutesWithVisibilityHide(OpenApi $openApi): void
    {
        /** @var PathItem $pathItem */
        foreach ($openApi->getPaths()->getPaths() as $pathName => $pathItem) {
            $getOperation = $pathItem->getGet();

            if (false === ($getOperation instanceof Operation)) {
                continue;
            }

            $extensionProperties = $getOperation->getExtensionProperties();

            if (\array_key_exists('x-visibility', $extensionProperties) && 'hide' === $extensionProperties['x-visibility']) {
                $openApi->getPaths()->addPath($pathName, $pathItem->withGet(null));
            }
        }
    }
}
