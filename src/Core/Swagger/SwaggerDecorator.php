<?php

declare(strict_types=1);

namespace KLS\Core\Swagger;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SwaggerDecorator implements NormalizerInterface
{
    private NormalizerInterface $decorated;
    private RouterInterface $router;

    public function __construct(NormalizerInterface $decorated, RouterInterface $router)
    {
        $this->decorated = $decorated;
        $this->router    = $router;
    }

    /**
     * @param mixed $data
     */
    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * @param mixed $object
     *
     * @throws ExceptionInterface
     */
    public function normalize($object, ?string $format = null, array $context = []): array
    {
        /** @var array $docs */
        $docs = $this->decorated->normalize($object, $format, $context);

        // Paths
        $authenticationPaths = [
            $this->router->generate('authentication_token')       => $this->generateAuthenticationTokenPath(),
            $this->router->generate('gesdinet_jwt_refresh_token') => $this->generateRefreshTokenPath(),
        ];
        // put authentication paths at top of the list
        $docs['paths'] = \array_merge($authenticationPaths, $docs['paths']);

        // @todo to refactorize in CALS-4262
        // remove unused routes (ItemOperations are necessary for APIPlatform but we don't expose those endpoints)
        $removedGetRoutes = [
            '/acceptations_legal_docs/{id}',
            '/company_modules/{id}',
            '/files/{id}',
            '/file_versions/{id}',
            '/legal_documents/{id}',
            '/project_files/{id}',
            '/project_organizers/{id}',
            '/project_statuses/{id}',
            '/project_participation_collections/{id}',
            '/project_participation_members/{id}',
            '/project_participation_statuses/{id}',
            '/project_participation_tranches/{id}',
            '/staff_statuses/{id}',
        ];

        foreach ($removedGetRoutes as $route) {
            if (isset($docs['paths'][$route]['get'])) {
                unset($docs['paths'][$route]['get']);
            }
        }

        // Schemas
        $docs['components']['schemas']['Folder'] = [
            'properties' => [
                'name' => ['type' => 'string'],
            ],
        ];
        \ksort($docs['components']['schemas']);

        return $docs;
    }

    private function generateAuthenticationTokenPath(): array
    {
        return [
            'post' => [
                'tags'        => ['Authentication'],
                'operationId' => 'postAuthenticationToken',
                'summary'     => 'Retrieve authentication tokens',
                'requestBody' => [
                    'content' => [
                        'application/x-www-form-urlencoded' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'username' => [
                                        'description' => 'Email',
                                        'type'        => 'string',
                                    ],
                                    'password' => [
                                        'description' => 'Password',
                                        'type'        => 'string',
                                    ],
                                    'captchaValue' => [
                                        'description' => 'Captcha value',
                                        'type'        => 'string',
                                    ],
                                ],
                                'required' => ['username', 'password', 'captchaValue'],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Retrieving succeeded',
                        'content'     => [
                            'application/x-form-' => [
                                'schema' => [
                                    'type'       => 'object',
                                    'properties' => [
                                        'username' => [
                                            'description' => 'Email',
                                            'type'        => 'string',
                                        ],
                                        'password' => [
                                            'description' => 'Password',
                                            'type'        => 'string',
                                        ],
                                        'captchaValue' => [
                                            'description' => 'Captcha value',
                                            'type'        => 'string',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Invalid credentials',
                        'content'     => [
                            'application/json' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function generateRefreshTokenPath(): array
    {
        return [
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
                        'description' => 'Refresh succeeded',
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
                        'description' => 'Refresh token does not exist',
                        'content'     => [
                            'application/json' => [],
                        ],
                    ],
                ],
            ],
        ];
    }
}
