<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Functional\Api;

use ApiPlatform\Core\Api\IriConverterInterface;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Unilend\Core\Entity\User;
use Unilend\Core\EventSubscriber\Jwt\VersionSubscriber;
use Unilend\Core\Repository\UserRepository;
use Unilend\Core\Service\Jwt\StaffPayloadManager;
use Unilend\Test\Core\DataFixtures\UserFixtures;

/**
 * @coversNothing
 *
 * @internal
 */
class AuthenticationTest extends WebTestCase
{
    /** @var string */
    private const LOGIN_ENDPOINT = '/core/authentication_token';

    public function providerSuccessfulLoginForStaffAccount(): array
    {
        return [
            'User with staff'                                     => ['user-9'],
            'User without staff (typically a borrower in agency)' => ['user-21'],
        ];
    }

    /**
     * Test POST /authentication_tokens.
     *
     * @throws JsonException
     *
     * @dataProvider providerSuccessfulLoginForStaffAccount
     */
    public function testSuccessfulLoginForStaffAccount(string $userPublicId): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => $userPublicId]);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => UserFixtures::DEFAULT_PASSWORD, 'captchaValue' => 'ignored in test'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseIsSuccessful();
        $response = $client->getResponse()->getContent();

        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('refresh_token', $response);
        static::assertArrayHasKey('tokens', $response);
        static::assertArrayNotHasKey('token', $response);
        static::assertNotEmpty($response['tokens']);

        $decoder = static::$container->get(JWTEncoderInterface::class);

        $iriConverter = static::$container->get(IriConverterInterface::class);

        $userIri = $iriConverter->getIriFromItem($user);

        $decodedTokens = array_map([$decoder, 'decode'], $response['tokens']);

        foreach ($decodedTokens as $decoded) {
            static::assertSame(VersionSubscriber::JWT_VERSION, $decoded['version']);
            static::assertSame($userIri, $decoded['user'] ?? null);
        }

        $userTokens = array_filter($decodedTokens, static fn ($decoded) => false === isset($decoded['@scope']));
        static::assertCount(1, $userTokens);

        // TODO This part might be better off in another function
        $staffTokens = array_filter($decodedTokens, static fn ($decoded) => StaffPayloadManager::getScope() === ($decoded['@scope'] ?? null));

        $staffIris = [];

        foreach ($user->getStaff() as $staff) {
            if ($staff->isGrantedLogin()) {
                $staffIris[] = $iriConverter->getIriFromItem($staff);
            }
        }

        static::assertEqualsCanonicalizing($staffIris, array_column($staffTokens, 'staff'));
    }

    public function providerFailedLoginWrongPassword(): array
    {
        return [
            'User with staff'                                     => ['user-9'],
            'User without staff (typically a borrower in agency)' => ['user-21'],
        ];
    }

    /**
     * @throws JsonException
     *
     * @dataProvider providerFailedLoginWrongPassword
     */
    public function testFailedLoginWrongPassword(string $publicId)
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => $publicId]);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => 'wrong password', 'captchaValue' => 'ignored in test'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @throws JsonException
     */
    public function testMissingCaptchaValue()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => 'user-9']);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => UserFixtures::DEFAULT_PASSWORD], JSON_THROW_ON_ERROR)
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @throws JsonException
     */
    public function testFailedLoginUninitializedUser()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => 'user-uninitialized']);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => UserFixtures::DEFAULT_PASSWORD, 'captchaValue' => 'ignored in test'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
