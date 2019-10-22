<?php

declare(strict_types=1);

namespace Unilend\Test\Unit\Service\Notification;

use DateTimeImmutable;
use Exception;
use Faker\Provider\Base;
use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, Companies, Embeddable\Money, Notification, Project, ProjectOffer, Tranche, TrancheOffer};
use Unilend\Repository\NotificationRepository;
use Unilend\Service\Notification\NotificationDisplayManager;

/**
 * @coversDefaultClass \Unilend\Service\Notification\NotificationDisplayManager
 *
 * @internal
 */
class NotificationDisplayManagerTest extends TestCase
{
    /** @var NotificationRepository */
    private $notificationRepository;
    /** @var TranslatorInterface */
    private $translator;
    /** @var RouterInterface */
    private $router;
    /** @var NumberFormatter */
    private $currencyFormatterNoDecimal;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->notificationRepository     = $this->prophesize(NotificationRepository::class);
        $this->translator                 = $this->prophesize(TranslatorInterface::class);
        $this->router                     = $this->prophesize(RouterInterface::class);
        $this->currencyFormatterNoDecimal = $this->prophesize(NumberFormatter::class);
    }

    /**
     * @covers ::getLastClientNotifications
     *
     * @dataProvider notificationDataProvider
     *
     * @small
     *
     * @param array|Notification[] $testedNotifications
     *
     * @throws Exception
     */
    public function testGetLastNotification(array $testedNotifications)
    {
        $this->translator->trans(Argument::type('string'), Argument::cetera())->willReturn(Base::lexify('????'));
        /** @var MethodProphecy $repositoryMethodProphecy */
        $repositoryMethodProphecy = $this->notificationRepository->findBy(
            Argument::that(static function ($argument) {
                return !empty($argument['client']) && $argument['client'] instanceof Clients;
            }),
            Argument::that(static function ($argument) {
                return !empty($argument['added']);
            }),
            Argument::cetera()
        );
        $repositoryMethodProphecy->willReturn($testedNotifications);

        $notificationDisplayManager = $this->createTestObject();

        $resultingNotifications = $notificationDisplayManager->getLastClientNotifications(new Clients());

        static::assertCount(count($testedNotifications), $resultingNotifications);

        foreach (array_map(null, $testedNotifications, $resultingNotifications) as [$testedNotification, $resultingNotification]) {
            $this->translator->trans(Argument::type('string'), Argument::cetera())->shouldHaveBeenCalled();

            $testedNotification->getProphecy()->getId()->shouldHaveBeenCalled();
            $testedNotification->getProphecy()->getProject()->shouldHaveBeenCalled();
            $testedNotification->getProphecy()->getAdded()->shouldHaveBeenCalled();
            $testedNotification->getProphecy()->getStatus()->shouldHaveBeenCalled();

            foreach (['id', 'projectId', 'type', 'title', 'datetime', 'iso-8601', 'content', 'image', 'status'] as $key) {
                static::assertArrayHasKey($key, $resultingNotification, 'assert ' . $key . ' exists');
            }

            static::assertSame($testedNotification->getId(), $resultingNotification['id']);
            static::assertSame($testedNotification->getAdded(), $resultingNotification['datetime']);
            static::assertNotEmpty($resultingNotification['type'], 'type');
            static::assertNotEmpty($resultingNotification['content'], 'content');
            static::assertNotEmpty($resultingNotification['title'], 'title');
            static::assertNotEmpty($resultingNotification['status'], 'title');
        }
    }

    /**
     * @throws Exception
     *
     * @return array
     */
    public function notificationDataProvider(): array
    {
        return [
            'zero'    => [[]],
            'one'     => [[$this->createNotification(Notification::TYPE_ACCOUNT_CREATED)]],
            'several' => [
                array_map(
                    [$this, 'createNotification'],
                    [
                        Notification::TYPE_ACCOUNT_CREATED,
                        Notification::TYPE_PROJECT_REQUEST,
                        Notification::TYPE_PROJECT_PUBLICATION,
                        Notification::TYPE_TRANCHE_OFFER_SUBMITTED_SUBMITTER,
                        Notification::TYPE_TRANCHE_OFFER_SUBMITTED_PARTICIPANTS,
                        Notification::TYPE_PROJECT_COMMENT_ADDED,
                    ]
                ),
            ],
        ];
    }

    /**
     * @param int $type
     * @param int $status
     *
     * @throws Exception
     *
     * @return Notification|object
     */
    private function createNotification(int $type, int $status = Notification::STATUS_READ): object
    {
        /** @var Companies|ObjectProphecy $borrowerCompany */
        $borrowerCompany = $this->prophesize(Companies::class);
        $borrowerCompany->getId()->willReturn(Base::randomDigitNotNull());
        $borrowerCompany->getName()->willReturn(Base::lexify('???????'));

        /** @var Companies|ObjectProphecy $submitterCompany */
        $submitterCompany = $this->prophesize(Companies::class);
        $submitterCompany->getId()->willReturn(Base::randomDigitNotNull());
        $submitterCompany->getName()->willReturn(Base::lexify('???????'));

        /** @var Companies|ObjectProphecy $lenderCompany */
        $lenderCompany = $this->prophesize(Companies::class);
        $lenderCompany->getId()->willReturn(Base::randomDigitNotNull());
        $lenderCompany->getName()->willReturn(Base::lexify('???????'));

        /** @var Project|ObjectProphecy $project */
        $project = $this->prophesize(Project::class);
        $project->getId()->willReturn(Base::randomDigitNotNull());
        $project->getHash()->willReturn(Base::lexify('???????'));
        $project->getTitle()->willReturn(Base::lexify('???????'));
        $project->getBorrowerCompany()->willReturn($borrowerCompany->reveal());
        $project->getSubmitterCompany()->willReturn($submitterCompany->reveal());

        $tranche = $this->prophesize(Tranche::class);
        $tranche->getProject()->willReturn($project);

        $projectOffer = new ProjectOffer($lenderCompany->reveal(), $project->reveal());

        /** @var TrancheOffer|ObjectProphecy $trancheOffer */
        $trancheOffer = $this->prophesize(TrancheOffer::class);
        $trancheOffer->getId()->willReturn(Base::randomDigitNotNull());
        $trancheOffer->getMoney()->willReturn(new Money());
        $trancheOffer->getProjectOffer()->willReturn($projectOffer);
        $trancheOffer->getTranche()->willReturn($tranche->reveal());

        /** @var Clients|ObjectProphecy $clients */
        $clients = $this->prophesize(Clients::class);
        $clients->getIdClient()->willReturn(Base::randomDigitNotNull());

        /** @var Notification|ObjectProphecy $notification */
        $notification = $this->prophesize(Notification::class);
        $notification->getId()->willReturn(Base::randomDigitNotNull());
        $notification->getAdded()->willReturn(new DateTimeImmutable());
        $notification->getClient()->willReturn($clients);
        $notification->getProject()->willReturn($project);
        $notification->getTrancheOffer()->willReturn($trancheOffer);
        $notification->getType()->willReturn($type);
        $notification->getStatus()->willReturn($status);

        return $notification->reveal();
    }

    /**
     * @return NotificationDisplayManager
     */
    private function createTestObject(): NotificationDisplayManager
    {
        return new NotificationDisplayManager(
            $this->notificationRepository->reveal(),
            $this->translator->reveal(),
            $this->router->reveal(),
            $this->currencyFormatterNoDecimal->reveal()
        );
    }
}
