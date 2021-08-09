<?php

declare(strict_types=1);

namespace KLS\Core\MessageHandler\File;

use InvalidArgumentException;
use KLS\Core\Message\File\FileUploaded;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use KLS\Syndication\Arrangement\Service\Project\ProjectNotifier;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class FileUploadedHandler implements MessageHandlerInterface
{
    /** @var ProjectNotifier */
    private $projectNotifier;
    /** @var ProjectRepository */
    private $projectRepository;

    public function __construct(ProjectRepository $projectRepository, ProjectNotifier $projectNotifier)
    {
        $this->projectRepository = $projectRepository;
        $this->projectNotifier   = $projectNotifier;
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
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

        $this->projectNotifier->notifyUploaded($project);
    }
}
