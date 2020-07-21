<?php

declare(strict_types=1);

namespace Unilend\Swagger;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SwaggerDecorator implements NormalizerInterface
{
    private NormalizerInterface $decorated;

    private RouterInterface $router;

    /**
     * @param NormalizerInterface $decorated
     * @param RouterInterface     $router
     */
    public function __construct(NormalizerInterface $decorated, RouterInterface $router)
    {
        $this->decorated = $decorated;
        $this->router    = $router;
    }

    /**
     * @param mixed       $object
     * @param string|null $format
     * @param array       $context
     *
     * @throws ExceptionInterface
     *
     * @return array
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        /** @var array $docs */
        $docs = $this->decorated->normalize($object, $format, $context);

        $docs['paths'][$this->router->generate('gesdinet_jwt_refresh_token')] = [
            'post' => [
                'tags'        => ['Authentication'],
                'operationId' => 'postRefreshToken',
                'summary'     => 'Refresh valid token',
                'requestBody' => [
                    'content' => [
                        'application/x-www-form-urlencoded' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'refresh_token' => [
                                        'description' => 'JWT Refresh Token',
                                        'type'        => 'string',
                                    ],
                                ],
                                'required' => ['refresh_token'],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Refresh succeded',
                        'content'     => [
                            'application/x-form-' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'refresh_token' => [
                                            'description' => 'JWT Refresh Token',
                                            'type'        => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Refresh token don\'t exist',
                        'content'     => [
                            'application/json' => [],
                        ],
                    ],
                ],
            ],
        ];

        return $docs;
    }

    /**
     * @param mixed $data
     * @param null  $format
     *
     * @return bool
     */
    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
