<?php

declare(strict_types=1);

namespace Unilend\DataTransformer;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Exception\{AccessDeniedException, RuntimeException};
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\DTO\MessageInput;
use Unilend\Entity\Clients;
use Unilend\Entity\Message;
use Unilend\Entity\MessageThread;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Repository\MessageThreadRepository;
use Unilend\Security\Voter\MessageVoter;

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

    /**
     * MessageInputDataTransformer constructor.
     *
     * @param ValidatorInterface      $validator
     * @param Security                $security
     * @param MessageThreadRepository $messageThreadRepository
     * @param IriConverterInterface   $iriConverter
     */
    public function __construct(ValidatorInterface $validator, Security $security, MessageThreadRepository $messageThreadRepository, IriConverterInterface $iriConverter)
    {
        $this->validator                = $validator;
        $this->security                 = $security;
        $this->messageThreadRepository  = $messageThreadRepository;
        $this->iriConverter             = $iriConverter;
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
        $client = $this->security->getUser();

        if (false === $client instanceof Clients) {
            throw new RuntimeException();
        }

        if (($entity instanceof ProjectParticipation) && false === $this->security->isGranted(MessageVoter::ATTRIBUTE_CREATE, $entity)) {
            throw new AccessDeniedException();
        }

        if (($entity instanceof Project) && false === $entity->isProjectOrganizer($this->security->getUser()->getCurrentStaff()->getCompany())) {
            throw new AccessDeniedException();
        }

        if ($entity instanceof ProjectParticipation) {
            $messageThread = $this->getMessageThreadFromProjectParticipation($entity);

            return new Message($client->getCurrentStaff(), $messageThread, $object->body);
        }

        $messageThread = $this->getActiveProjectParticipationMessageThreadFromProject($entity);

        // If entity is a project, the message must be broadcasted
        return new Message($client->getCurrentStaff(), $messageThread, $object->body, true);
    }

    /**
     * @param Project $project
     *
     * @return MessageThread|null
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function getActiveProjectParticipationMessageThreadFromProject(Project $project): ?MessageThread
    {
        foreach ($project->getProjectParticipations() as $projectParticipation) {
            if ($projectParticipation->isActive()) {
                return $this->getMessageThreadFromProjectParticipation($projectParticipation);
            }
        }

        return null;
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
