<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Serializer\ContextBuilder;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Staff;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Repository\ProgramRepository;
use Unilend\CreditGuaranty\Security\Voter\ProgramVoter;

class ProjectContextBuilder implements SerializerContextBuilderInterface
{
    public const IMPORT_QUERY_PARAMETER = 'import';

    private Security                          $security;
    private SerializerContextBuilderInterface $decorated;
    private ProgramRepository                 $programRepository;

    public function __construct(SerializerContextBuilderInterface $decorated, Security $security, ProgramRepository $programRepository)
    {
        $this->decorated         = $decorated;
        $this->security          = $security;
        $this->programRepository = $programRepository;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     */
    public function createFromRequest(Request $request, bool $normalization, array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $token   = $this->security->getToken();
        $staff   = $token && $token->hasAttribute('staff') ? $token->getAttribute('staff') : null;

        if (false === ($staff instanceof Staff)) {
            return $context;
        }

        if (
            Request::METHOD_POST !== $request->getMethod()
            || Program::class !== $request->attributes->get('_api_resource_class')
            || 'api_credit_guaranty_programs_post_collection' !== $request->attributes->get('_route')
        ) {
            return $context;
        }

        $existingProgramPublicId = $request->get(static::IMPORT_QUERY_PARAMETER);

        $existingProgram = $this->programRepository->findOneBy(['publicId' => $existingProgramPublicId]);

        if (null === $existingProgram) {
            return $context;
        }

        if (false === $this->security->isGranted(ProgramVoter::ATTRIBUTE_VIEW, $existingProgram)) {
            throw new AccessDeniedException();
        }

        if (false === $this->security->isGranted(ProgramVoter::ATTRIBUTE_CREATE, $existingProgram)) {
            throw new AccessDeniedException();
        }

        $duplicatedProgram = $existingProgram->duplicate($staff);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $duplicatedProgram;

        return $context;
    }
}
