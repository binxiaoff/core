<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\SlackNotifier;

use KLS\Syndication\Arrangement\Entity\Project;
use Nexy\Slack\MessageInterface;

interface ProjectNotifierInterface
{
    public function notify(Project $project): void;

    public function createSlackMessage(Project $project): MessageInterface;
}
