<?php

declare(strict_types=1);

namespace Unilend\Swagger;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SwaggerDecorator implements NormalizerInterface
{
    private $decorated;

    /**
     * @param NormalizerInterface $decorated
     */
    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param mixed $object
     * @param null  $format
     * @param array $context
     *
     * @throws ExceptionInterface
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        /** @var array $docs */
        $docs = $this->decorated->normalize($object, $format, $context);

        $docs['info']['title']   = 'KLS';
        $docs['info']['version'] = '1.0.0';

        $docs['paths']['/authentication_token'] = [
            'post' => [
                'tags'        => ['Authentication'],
                'operationId' => 'postAuthenticationToken',
                'summary'     => 'Log in and get an authentication token',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'username' => [
                                        'description' => 'Username of the user',
                                        'type'        => 'string',
                                        'format'      => 'email',
                                    ],
                                    'password' => [
                                        'description' => 'Password of the user',
                                        'type'        => 'string',
                                    ],
                                ],
                                'required' => ['username', 'password'],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Login succeded',
                        'content'     => [
                            'application/json' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'token' => [
                                            'description' => 'JWT Token',
                                            'type'        => 'string',
                                        ],
                                        'refresh_token' => [
                                            'description' => 'Refresh Token',
                                            'type'        => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Bad credentials',
                        'content'     => [
                            'application/json' => [],
                        ],
                    ],
                ],
            ],
        ];

        $docs['paths']['/token/refresh'] = [
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
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
