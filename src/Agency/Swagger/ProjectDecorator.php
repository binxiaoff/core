<?php

declare(strict_types=1);

namespace Unilend\Agency\Swagger;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProjectDecorator implements NormalizerInterface
{
    private NormalizerInterface $decorated;

    /**
     * @param NormalizerInterface $decorated
     */
    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
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

        $swagger = [
            'get' => [
                'tags'        => ['agency_project'],
                'summary'     => 'View dataroom',
                'produces'  => [
                    'application/ld+json',
                    'application/json',
                ],
                'parameters' => [
                    [
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'type' => 'string',
                    ],
                    [
                        'name' => 'path',
                        'in' => 'path',
                        'required' => false,
                        'type' => 'string',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Folder resource',
                        'content'     => [],
                        'schema'      => ['$ref' => '#/definitions/Folder'],
                    ],
                    '404' => [
                        'description' => 'Folder does not exist',
                    ],
                ],
            ],
        ];

        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentBorrower/{path}'] = $swagger;
        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentBorrower/{path}']['operationId'] = 'project_dataroom_shared_agentBorrower';
        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentPrincipalParticipant/{path}'] = $swagger;
        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentPrincipalParticipant/{path}']['operationId'] = 'project_dataroom_shared_agentPrincipalParticipant';
        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentSecondaryParticipant/{path}'] = $swagger;
        $docs['paths']['/agency/projects/{id}/dataroom/shared/agentSecondaryParticipant/{path}']['operationId'] = 'project_dataroom_shared_agentSecondaryParticipant';
        $docs['paths']['/agency/projects/{id}/dataroom/confidential/{path}'] = $swagger;
        $docs['paths']['/agency/projects/{id}/dataroom/confidential/{path}']['operationId'] = 'project_dataroom_confidential';

        return $docs;
    }

    /**
     * @param mixed       $data
     * @param string|null $format
     *
     * @return bool
     */
    public function supportsNormalization($data, ?string $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }
}
