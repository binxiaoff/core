<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Traits;

use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\Project as ArrangementProject;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;

trait ArrangementProjectSetTrait
{
    private function createArrangementProject(Staff $staff, ?int $status = null): Project
    {
        $project = new Project($staff, 'risk1', new Money('EUR', '42'));
        $project->setPublicId();

        if (null !== $status) {
            $projectStatus = new ProjectStatus($project, $status, $staff);
            $project->setCurrentStatus($projectStatus);
            $project->getStatuses()->add($projectStatus);
        }

        return $project;
    }

    private function createProjectParticipation(Staff $staff, ArrangementProject $arrangementProject): ProjectParticipation
    {
        $projectParticipation = new ProjectParticipation($staff->getCompany(), $arrangementProject, $staff);
        $projectParticipation->setPublicId();

        return $projectParticipation;
    }
}
