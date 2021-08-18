<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\File;

use InvalidArgumentException;
use KLS\Core\Message\File\FileUploaded;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\MailNotifier\ProjectUploadNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class FileUploadedHandler implements MessageHandlerInterface
{
    private ProjectUploadNotifier $projectUploadNotifier;
    private ProjectRepository $projectRepository;

    public function __construct(ProjectRepository $projectRepository, ProjectUploadNotifier $projectUploadNotifier)
    {
        $this->projectRepository     = $projectRepository;
        $this->projectUploadNotifier = $projectUploadNotifier;
    }

    public function __invoke(FileUploaded $fileUploaded)
    {
        $context = $fileUploaded->getContext();

        if (empty($context['projectId'])) {
            return;
        }

        $project = $this->projectRepository->find($context['projectId']);
        if (null === $project) {
            throw new InvalidArgumentException(\sprintf('The project with id %d does not exist', $context['projectId']));
        }

        $this->projectUploadNotifier->notify($project);
    }
}
