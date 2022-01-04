<?php

declare(strict_types=1);

namespace KLS\Core\Swagger;

use KLS\CreditGuaranty\FEI\Entity\Constant\ReportingFilter;
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
        $this->hydrateReportingRoutesParameters($docs['paths']);
        \ksort($docs['paths']);
        // put authentication paths at top of the list
        $authenticationPaths = [
            $this->router->generate('authentication_token')       => $this->generateAuthenticationTokenPath(),
            $this->router->generate('gesdinet_jwt_refresh_token') => $this->generateRefreshTokenPath(),
        ];
        $docs['paths'] = \array_merge($authenticationPaths, $docs['paths']);

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
                                ],
                                'required' => ['username', 'password'],
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

    private function hydrateReportingRoutesParameters(array &$routes): void
    {
        $reportingRoutes = [
            [
                'route'  => '/credit_guaranty/reporting_templates/{publicId}/reporting',
                'method' => 'get',
            ],
            [
                'route'  => '/credit_guaranty/programs/{publicId}/financing_objects',
                'method' => 'patch',
            ],
            [
                'route'  => '/credit_guaranty/reporting_templates/{publicId}/export',
                'method' => 'get',
            ],
        ];

        foreach ($reportingRoutes as $reportingRoute) {
            foreach (ReportingFilter::FIELD_ALIAS_FILTER_KEYS as $fieldAliasFilter) {
                $routes[$reportingRoute['route']][$reportingRoute['method']]['parameters'][] = [
                    'name'            => $fieldAliasFilter,
                    'in'              => 'query',
                    'description'     => '',
                    'required'        => false,
                    'deprecated'      => false,
                    'allowEmptyValue' => false,
                    'schema'          => [],
                    'style'           => 'form',
                    'explode'         => false,
                    'allowReserved'   => false,
                ];
                $routes[$reportingRoute['route']][$reportingRoute['method']]['parameters'][] = [
                    'name'            => $fieldAliasFilter . '[]',
                    'in'              => 'query',
                    'description'     => '',
                    'required'        => false,
                    'deprecated'      => false,
                    'allowEmptyValue' => false,
                    'schema'          => ['type' => 'object'],
                    'style'           => 'deepObject',
                    'explode'         => true,
                    'allowReserved'   => false,
                ];
            }
        }
    }
}
