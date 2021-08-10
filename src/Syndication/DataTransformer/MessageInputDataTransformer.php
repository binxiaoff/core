<?php

declare(strict_types=1);

namespace KLS\Syndication\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\DTO\MessageInput;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\User;
use KLS\Core\Repository\MessageThreadRepository;
use KLS\Syndication\Entity\Project;
use KLS\Syndication\Entity\ProjectParticipation;
use KLS\Syndication\Security\Voter\ProjectParticipationVoter;
use KLS\Syndication\Service\Project\ProjectManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class MessageInputDataTransformer implements DataTransformerInterface
{
    private ValidatorInterface $validator;
    private Security $security;
    private MessageThreadRepository $messageThreadRepository;
    private IriConverterInterface $iriConverter;
    private ProjectManager $projectManager;

    public function __construct(
        ValidatorInterface $validator,
        Security $security,
        MessageThreadRepository $messageThreadRepository,
        IriConverterInterface $iriConverter,
        ProjectManager $projectManager
    ) {
        $this->validator               = $validator;
        $this->security                = $security;
        $this->messageThreadRepository = $messageThreadRepository;
        $this->iriConverter            = $iriConverter;
        $this->projectManager          = $projectManager;
    }

    /**
     * @param array|object $data
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return (Message::class === $to) && (MessageInput::class === $context['input']['class']);
    }

    /**
     * @param object $object
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Message
     */
    public function transform($object, string $to, array $context = [])
    {
        $this->validator->validate($object);

        $entity = $this->iriConverter->getItemFromIri($object->entity, [AbstractNormalizer::GROUPS => []]);
        $user   = $this->security->getUser();

        if (false === $user instanceof User) {
            throw new AccessDeniedException();
        }

        if (($entity instanceof ProjectParticipation) && false === $this->security->isGranted(ProjectParticipationVoter::ATTRIBUTE_VIEW, $entity)) {
            throw new AccessDeniedException();
        }

        if (
            ($entity instanceof Project) // You wish to send a broadcast message
            && (
                $entity->getArranger() !== $user->getCompany() // You are not connected as arranger
                || null === $user->getCurrentStaff() // You have no staff
                || $this->projectManager->isActiveParticipationMember($entity, $user->getCurrentStaff()) // You have no active member in project
            )
        ) {
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function getMessageThreadFromProjectParticipation(ProjectParticipation $projectParticipation): MessageThread
    {
        $messageThread = $this->messageThreadRepository->findOneBy(['projectParticipation' => $projectParticipation]);
        if ($messageThread instanceof MessageThread) {
            return $messageThread;
        }

        $messageThread = (new MessageThread())->setProjectParticipation($projectParticipation);
        $this->messageThreadRepository->save($messageThread);

        return $messageThread;
    }
}
