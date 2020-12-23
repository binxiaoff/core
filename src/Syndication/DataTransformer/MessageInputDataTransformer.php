<?php

declare(strict_types=1);

namespace Unilend\Syndication\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\{AccessDeniedException, RuntimeException};
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\DTO\MessageInput;
use Unilend\Core\Entity\{Message, MessageThread, User};
use Unilend\Core\Repository\MessageThreadRepository;
use Unilend\Core\Security\Voter\MessageThreadVoter;
use Unilend\Syndication\Entity\{Project, ProjectParticipation};
use Unilend\Syndication\Service\Project\ProjectManager;

class MessageInputDataTransformer implements DataTransformerInterface
{
    /** @var ValidatorInterface */
    private ValidatorInterface $validator;

    /** @var Security */
    private Security $security;

    /** @var MessageThreadRepository */
    private MessageThreadRepository $messageThreadRepository;

    /** @var IriConverterInterface */
    private IriConverterInterface $iriConverter;

    /** @var ProjectManager */
    private ProjectManager $projectManager;

    /**
     * MessageInputDataTransformer constructor.
     *
     * @param ValidatorInterface      $validator
     * @param Security                $security
     * @param MessageThreadRepository $messageThreadRepository
     * @param IriConverterInterface   $iriConverter
     * @param ProjectManager          $projectManager
     */
    public function __construct(
        ValidatorInterface $validator,
        Security $security,
        MessageThreadRepository $messageThreadRepository,
        IriConverterInterface $iriConverter,
        ProjectManager $projectManager
    ) {
        $this->validator                = $validator;
        $this->security                 = $security;
        $this->messageThreadRepository  = $messageThreadRepository;
        $this->iriConverter             = $iriConverter;
        $this->projectManager           = $projectManager;
    }

    /**
     * @param array|object $data
     * @param string       $to
     * @param array        $context
     *
     * @return bool
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (Message::class === $to) && (MessageInput::class === $context['input']['class']);
    }

    /**
     * @param object $object
     * @param string $to
     * @param array  $context
     *
     * @return Message
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        $entity = $this->iriConverter->getItemFromIri($object->entity, [AbstractNormalizer::GROUPS => []]);
        $user = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new RuntimeException();
        }

        if (($entity instanceof ProjectParticipation) && false === $this->security->isGranted(MessageThreadVoter::ATTRIBUTE_VIEW, $entity)) {
            throw new AccessDeniedException();
        }

        if (($entity instanceof Project) && false === $this->projectManager->isArranger($entity, $this->security->getUser()->getCurrentStaff())) {
            throw new AccessDeniedException();
        }

        if ($entity instanceof ProjectParticipation) {
            $messageThread = $this->getMessageThreadFromProjectParticipation($entity);

            return new Message($user->getCurrentStaff(), $messageThread, $object->body);
        }

        $messageThread = $this->getActiveProjectParticipationMessageThreadFromProject($entity);

        // If entity is a project, the message must be broadcast
        return (new Message($user->getCurrentStaff(), $messageThread, $object->body))->setBroadcast();
    }

    /***
     * @param Project $project
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return MessageThread|null
     */
    private function getActiveProjectParticipationMessageThreadFromProject(Project $project): ?MessageThread
    {
        $messageThread = null;
        foreach ($project->getProjectParticipations() as $projectParticipation) {
            if ($projectParticipation->isActive()) {
                $messageThread = $this->getMessageThreadFromProjectParticipation($projectParticipation);
            }
        }

        return $messageThread;
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return MessageThread
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getMessageThreadFromProjectParticipation(ProjectParticipation $projectParticipation): MessageThread
    {
        if ($projectParticipation->getMessageThread() instanceof MessageThread) {
            return $projectParticipation->getMessageThread();
        }
        $messageThread = (new MessageThread())->setProjectParticipation($projectParticipation);
        $this->messageThreadRepository->save($messageThread);

        return $messageThread;
    }
}
