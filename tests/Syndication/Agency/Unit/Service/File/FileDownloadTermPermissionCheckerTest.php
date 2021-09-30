<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Service\File;

use KLS\Core\Entity\File;
use KLS\Core\Entity\FileDownload;
use KLS\Core\Entity\FileVersion;
use KLS\Core\Entity\Message;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Repository\TermRepository;
use KLS\Syndication\Agency\Security\Voter\TermVoter;
use KLS\Syndication\Agency\Service\File\FileDownloadTermPermissionChecker;
use KLS\Syndication\Arrangement\Entity\Project;
use KLS\Syndication\Arrangement\Entity\ProjectFile;
use KLS\Syndication\Arrangement\Entity\ProjectParticipation;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use KLS\Test\Syndication\Agency\Unit\Traits\TermTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @coversDefaultClass \KLS\Syndication\Agency\Service\File\FileDownloadTermPermissionChecker
 *
 * @internal
 */
class FileDownloadTermPermissionCheckerTest extends TestCase
{
    use PropertyValueTrait;
    use UserStaffTrait;
    use TermTrait;
    use ProphecyTrait;

    /** @var AuthorizationCheckerInterface|ObjectProphecy */
    private $authorizationChecker;

    /** @var TermRepository|ObjectProphecy */
    private $termRepository;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->prophesize(AuthorizationCheckerInterface::class);
        $this->termRepository       = $this->prophesize(TermRepository::class);
    }

    protected function tearDown(): void
    {
        $this->authorizationChecker = null;
        $this->termRepository       = null;
    }

    /**
     * @covers ::check
     */
    public function testCheck(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Term::FILE_TYPE_BORROWER_DOCUMENT);
        $term         = $this->createTerm($staff);

        $this->forcePropertyValue($file, 'id', 1);

        $this->termRepository->findOneBy(['borrowerDocument' => $file])->shouldBeCalledOnce()->willReturn($term);
        $this->authorizationChecker->isGranted(TermVoter::ATTRIBUTE_VIEW, $term)->shouldBeCalledOnce()->willReturn(true);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertTrue($result);
    }

    /**
     * @covers ::check
     *
     * @dataProvider notSupportsProvider
     */
    public function testCheckNotSupports(string $type): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, $type);

        $this->forcePropertyValue($file, 'id', 1);

        $this->termRepository->findOneBy(Argument::any())->shouldNotBeCalled();
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    public function notSupportsProvider(): iterable
    {
        yield Message::FILE_TYPE_MESSAGE_ATTACHMENT => [Message::FILE_TYPE_MESSAGE_ATTACHMENT];
        yield ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA => [ProjectParticipation::PROJECT_PARTICIPATION_FILE_TYPE_NDA];

        foreach (ProjectFile::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
        foreach (Project::getProjectFileTypes() as $type) {
            yield $type => [$type];
        }
    }

    /**
     * @covers ::check
     */
    public function testCheckWithTermNotFound(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Term::FILE_TYPE_BORROWER_DOCUMENT);

        $this->forcePropertyValue($file, 'id', 1);

        $this->termRepository->findOneBy(['borrowerDocument' => $file])->shouldBeCalledOnce()->willReturn(null);
        $this->authorizationChecker->isGranted(Argument::cetera())->shouldNotBeCalled();

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    /**
     * @covers ::check
     */
    public function testCheckWithTermNotGranted(): void
    {
        $staff        = $this->createStaff();
        $user         = $staff->getUser();
        $file         = new File();
        $fileVersion  = new FileVersion('', $user, $file, '');
        $fileDownload = new FileDownload($fileVersion, $user, Term::FILE_TYPE_BORROWER_DOCUMENT);
        $term         = $this->createTerm($staff);

        $this->forcePropertyValue($file, 'id', 1);

        $this->termRepository->findOneBy(['borrowerDocument' => $file])->shouldBeCalledOnce()->willReturn($term);
        $this->authorizationChecker->isGranted(TermVoter::ATTRIBUTE_VIEW, $term)->shouldBeCalledOnce()->willReturn(false);

        $result = $this->createTestObject()->check($fileDownload, $user);
        static::assertFalse($result);
    }

    private function createTestObject(): FileDownloadTermPermissionChecker
    {
        return new FileDownloadTermPermissionChecker(
            $this->authorizationChecker->reveal(),
            $this->termRepository->reveal()
        );
    }
}
