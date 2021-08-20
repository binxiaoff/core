<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Traits;

use Faker\Provider\Base;
use KLS\Core\DTO\FileInput;
use KLS\Core\Entity\Embeddable\Money;
use KLS\Core\Entity\Message;
use KLS\Core\Entity\MessageThread;
use KLS\Core\Entity\Staff;
use KLS\Syndication\Agency\Entity\Covenant;
use KLS\Syndication\Agency\Entity\Project as AgencyProject;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Arrangement\Entity\Project as ArrangementProject;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileInputEntitiesTrait
{
    use StaffTrait;
    use TokenTrait;

    private function createFileInput($targetEntity, ?string $type = null): FileInput
    {
        $filePath         = \sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uploadTestFile';
        $originalFileName = Base::asciify(\str_repeat('*', 20));
        \fopen($filePath, 'wb+');

        $uploadedFile = new UploadedFile($filePath, $originalFileName, null, null, true);

        return new FileInput($uploadedFile, $type ?? FileInput::ACCEPTED_MEDIA_TYPE[0], $targetEntity);
    }

    private function createMessage(Staff $staff): Message
    {
        return new Message($staff, new MessageThread(), 'message body');
    }

    private function createTerm(Staff $staff): Term
    {
        return new Term(
            new Covenant(
                $this->createAgencyProject($staff),
                'Covenant',
                Covenant::NATURE_CONTROL,
                new \DateTimeImmutable('- 2 years'),
                40,
                new \DateTimeImmutable('+ 3 years')
            ),
            new \DateTimeImmutable('- 1 years'),
            new \DateTimeImmutable('- 2 years')
        );
    }

    private function createAgencyProject(Staff $staff): AgencyProject
    {
        return new AgencyProject(
            $staff,
            'Agency Project',
            'risk1',
            new Money('EUR', '42'),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
        );
    }

    private function createArrangementProject(Staff $staff, ?int $status = null): ArrangementProject
    {
        $project = new ArrangementProject($staff, 'risk1', new Money('EUR', '42'));

        if (null !== $status) {
            $project->setCurrentStatus(new ProjectStatus($project, $status, $staff));
        }

        return $project;
    }

    private function createProjectParticipation(Staff $staff, ArrangementProject $arrangementProject): ProjectParticipation
    {
        return new ProjectParticipation($staff->getCompany(), $arrangementProject, $staff);
    }
}
