<?php

declare(strict_types=1);

namespace Unilend\Controller\Attachment;

use ApiPlatform\Core\Api\IriConverterInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\Attachment;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Security\Voter\ProjectVoter;
use Unilend\Service\Attachment\AttachmentManager;

class Upload
{
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $converter;

    /**
     * @param AttachmentManager     $attachmentManager
     * @param Security              $security
     * @param IriConverterInterface $converter
     */
    public function __construct(AttachmentManager $attachmentManager, Security $security, IriConverterInterface $converter)
    {
        $this->attachmentManager = $attachmentManager;
        $this->security          = $security;
        $this->converter         = $converter;
    }

    /**
     * @param Request $request
     *
     * @throws Exception
     *
     * @return Attachment
     */
    public function __invoke(Request $request): Attachment
    {
        /** @var Clients $user */
        $user = $this->security->getUser();

        if ($userIri = $request->request->get('user')) {
            if (false === $this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException();
            }
            $user = $this->converter->getItemFromIri($userIri);
        }

        $type = $request->request->get('type');

        $project = $request->request->get('project');
        $project = $project ? $this->converter->getItemFromIri($project, [AbstractNormalizer::GROUPS => ['project:read']]) : null;
        /** @var Project $project */

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedHttpException('You cannot upload file for the project');
        }

        return $this->attachmentManager->upload(
            $request->files->get('file'),
            $user,
            $type,
            $project
        );
    }
}
