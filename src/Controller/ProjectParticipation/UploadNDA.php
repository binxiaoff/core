<?php

declare(strict_types=1);

namespace Unilend\Controller\ProjectParticipation;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Entity\ProjectParticipation;
use Unilend\Repository\ProjectParticipationRepository;
use Unilend\Security\Voter\ProjectVoter;
use Unilend\Service\Attachment\AttachmentManager;

class UploadNDA
{
    /** @var AttachmentManager */
    private $attachmentManager;
    /** @var Security */
    private $security;
    /** @var IriConverterInterface */
    private $converter;
    /** @var ProjectParticipationRepository */
    private $projectParticipationRepository;

    /**
     * @param AttachmentManager              $attachmentManager
     * @param Security                       $security
     * @param IriConverterInterface          $converter
     * @param ProjectParticipationRepository $projectParticipationRepository
     */
    public function __construct(
        AttachmentManager $attachmentManager,
        Security $security, IriConverterInterface $converter,
        ProjectParticipationRepository $projectParticipationRepository
    ) {
        $this->attachmentManager = $attachmentManager;
        $this->security = $security;
        $this->converter = $converter;
        $this->projectParticipationRepository = $projectParticipationRepository;
    }

    public function __invoke(ProjectParticipation $data, Request $request)
    {
        $type = $request->request->get('type');
        $file = $request->files->get('file');
        $project = $this->converter->getItemFromIri($request->request->get('project'), [AbstractNormalizer::GROUPS => ['project:read']]);
        $user = $this->security->getUser();

        if (false === $this->security->isGranted(ProjectVoter::ATTRIBUTE_EDIT, $project)) {
            throw new AccessDeniedHttpException('You cannot upload NDA for this project');
        }

        if ($data->getCompany() === $user->getStaff()->getCompany()) {
            // which exception?
            throw new AccessDeniedHttpException('You cannot upload NDA for your own entity');
        }

        if (null === $type || null === $file) {
            throw new \InvalidArgumentException('Invalid parameters.');
        }

        $attachment = $this->attachmentManager->upload($file, $user, $type, $project);
        $data->setConfidentialityDisclaimerDocument($attachment);
        $this->projectParticipationRepository->save($data);

        return $data;
    }
}
