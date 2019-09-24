<?php

declare(strict_types=1);

namespace Unilend\Test\unitTest\Service\ServiceTerms;

use Faker\Provider\Base;
use PHPUnit\Framework\TestCase;
use Prophecy\{Argument, Prophecy\MethodProphecy, Prophecy\ObjectProphecy};
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Messenger\{Envelope, MessageBusInterface};
use Symfony\Component\Security\Core\Authentication\Token\{Storage\TokenStorageInterface, TokenInterface};
use Unilend\Entity\{AcceptationsLegalDocs, Clients, LegalDocument, Settings};
use Unilend\Message\ServiceTerms\ServiceTermsAccepted;
use Unilend\Repository\{AcceptationLegalDocsRepository, LegalDocumentRepository, SettingsRepository};
use Unilend\Service\ServiceTerms\ServiceTermsManager;

/**
 * @internal
 *
 * @coversDefaultClass \Unilend\Service\ServiceTerms\ServiceTermsManager
 */
class ServiceTermsManagerTest extends TestCase
{
    /** @var ObjectProphecy */
    private $acceptationLegalDocsRepository;
    /** @var ObjectProphecy */
    private $legalDocumentRepository;
    /** @var ObjectProphecy */
    private $settingsRepository;
    /** @var ObjectProphecy */
    private $tokenStorage;
    /** @var ObjectProphecy */
    private $session;
    /** @var ObjectProphecy */
    private $messageBus;
    /** @var MethodProphecy */
    private $currentServiceTermsFinder;
    /** @var LegalDocument */
    private $currentServiceTerms;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->acceptationLegalDocsRepository = $this->prophesize(AcceptationLegalDocsRepository::class);
        $this->legalDocumentRepository        = $this->prophesize(LegalDocumentRepository::class);
        $this->settingsRepository             = $this->prophesize(SettingsRepository::class);
        $this->tokenStorage                   = $this->prophesize(TokenStorageInterface::class);
        $this->session                        = $this->prophesize(SessionInterface::class);
        $this->messageBus                     = $this->prophesize(MessageBusInterface::class);
        $this->initCurrentServiceTermsContext();
    }

    /**
     * @covers ::getCurrentVersion
     */
    public function testGetCurrentVersion(): void
    {
        $this->createTestObject()->getCurrentVersion();

        $this->currentServiceTermsFinder->shouldHaveBeenCalled();
    }

    /**
     * @covers ::acceptCurrentVersion
     */
    public function testAcceptCurrentVersion(): void
    {
        $client        = new Clients();
        $acceptationId = Base::randomNumber(3);
        $this->acceptationLegalDocsRepository->save(Argument::any())->will(function ($args) use ($acceptationId) {
            $acceptation = $args[0];
            $reflectionClass = new ReflectionClass(AcceptationsLegalDocs::class);
            $reflectionProperty = $reflectionClass->getProperty('idAcceptation');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($acceptation, $acceptationId);
        });
        $this->messageBus->dispatch(Argument::any())->willReturn(new Envelope(new \stdClass()));

        $serviceTermsAcceptation = $this->createTestObject()->acceptCurrentVersion($client);

        static::assertSame($client, $serviceTermsAcceptation->getClient());
        static::assertSame($this->currentServiceTerms, $serviceTermsAcceptation->getLegalDoc());
        $this->acceptationLegalDocsRepository->save(Argument::exact($serviceTermsAcceptation))->shouldHaveBeenCalled();
        $this->session->remove(Argument::exact(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED))->shouldHaveBeenCalled();
        $this->messageBus->dispatch(Argument::exact(new ServiceTermsAccepted($acceptationId)))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::checkCurrentVersionAccepted
     */
    public function testCheckCurrentVersionAcceptedAlreadyCheckedAndNotAccepted(): void
    {
        $this->session->has(Argument::exact(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED))->willReturn(true);

        $this->createTestObject()->checkCurrentVersionAccepted();

        $this->tokenStorage->getToken()->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::checkCurrentVersionAccepted
     */
    public function testCheckCurrentVersionAcceptedAccepted(): void
    {
        $client = new Clients();
        $this->initTokenStorage($client);

        $this->session
            ->has(Argument::exact(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED))
            ->willReturn(false)
        ;

        $this->acceptationLegalDocsRepository
            ->findOneBy(Argument::exact(['client' => $client, 'legalDoc' => $this->currentServiceTerms]))
            ->willReturn(new AcceptationsLegalDocs())
        ;

        $this->createTestObject()->checkCurrentVersionAccepted();

        $this->session->set(Argument::any(), Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::checkCurrentVersionAccepted
     */
    public function testCheckCurrentVersionAcceptedNotCheckedNotAccepted(): void
    {
        $client = new Clients();
        $this->initTokenStorage($client);

        $this->session
            ->has(Argument::exact(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED))
            ->willReturn(false)
        ;
        $setter = $this->session
            ->set(Argument::exact(ServiceTermsManager::SESSION_KEY_SERVICE_TERMS_ACCEPTED), Argument::exact(false))->willReturn();

        $this->acceptationLegalDocsRepository
            ->findOneBy(Argument::exact(['client' => $client, 'legalDoc' => $this->currentServiceTerms]))
            ->willReturn(null)
        ;

        $this->createTestObject()->checkCurrentVersionAccepted();

        $setter->shouldHaveBeenCalled();
    }

    /**
     * @return ServiceTermsManager
     */
    private function createTestObject(): ServiceTermsManager
    {
        return new ServiceTermsManager(
            $this->acceptationLegalDocsRepository->reveal(),
            $this->legalDocumentRepository->reveal(),
            $this->settingsRepository->reveal(),
            $this->tokenStorage->reveal(),
            $this->session->reveal(),
            $this->messageBus->reveal()
        );
    }

    /**
     * Initialise a context for getting the current service terms.
     */
    private function initCurrentServiceTermsContext(): void
    {
        $currentServiceTermsId     = Base::randomNumber(3);
        $this->currentServiceTerms = new LegalDocument();
        $reflectionClass           = new ReflectionClass(LegalDocument::class);
        $reflectionProperty        = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->currentServiceTerms, $currentServiceTermsId);
        $currentServiceTermsSettings = (new Settings())->setValue($currentServiceTermsId);
        $this->settingsRepository
            ->findOneBy(Argument::exact(['type' => Settings::TYPE_SERVICE_TERMS_PAGE_ID]))
            ->willReturn($currentServiceTermsSettings)
        ;

        $this->currentServiceTermsFinder = $this->legalDocumentRepository->find(Argument::exact($currentServiceTermsId))->willReturn($this->currentServiceTerms);
    }

    /**
     * @param Clients $client
     */
    private function initTokenStorage(Clients $client): void
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($client);
        $this->tokenStorage->getToken()->willReturn($token);
    }
}
