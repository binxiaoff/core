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
use Unilend\Core\Repository\UserRepository;

class AuthenticationTest extends WebTestCase
{
    /** @var string  */
    private const LOGIN_ENDPOINT = '/core/authentication_token';

    /**
     * Test POST /authentication_tokens
     *
     * @throws JsonException
     */
    public function testSuccessfulLoginForStaffAccount(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => 'user:9']);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => '0000', 'captchaValue' => 'ignored in test'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseIsSuccessful();
        $response = $client->getResponse()->getContent();

        $response = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        static::assertArrayHasKey('refresh_token', $response);
        static::assertArrayHasKey('staffTokens', $response);
        static::assertArrayHasKey('userToken', $response);
        static::assertArrayNotHasKey('token', $response);
        static::assertCount(count($user->getStaff()), $response['staffTokens']);

        $decoder = static::$container->get(JWTEncoderInterface::class);

        $iriConverter = static::$container->get(IriConverterInterface::class);

        $userIri = $iriConverter->getIriFromItem($user);

        $staffIris = [];

        $decodedUserToken = $decoder->decode($response['userToken']);

        static::assertSame($userIri, $decodedUserToken['user'] ?? null);

        foreach ($user->getStaff() as $staff) {
            $staffIris[] = $iriConverter->getIriFromItem($staff);
        }

        foreach ($response['staffTokens'] as $token) {
            $decoded = $decoder->decode($token);

            static::assertNotNull($decoded['version'] ?? null);
            static::assertSame($userIri, $decoded['user'] ?? null);
            static::assertNotNull($decoded['staff'] ?? null);
            static::assertContains($decoded['staff'], $staffIris);
        }
    }

    /**
     * @throws JsonException
     */
    public function testFailedLoginUnknownUser()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => 'notknownuser@test.com', 'password' => '0000', 'captchaValue' => 'ignored in test'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    /**
     * @throws JsonException
     */
    public function testFailedLoginWrongPassword()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        /** @var User $user */
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => 'user:9']);

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
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => 'user:9']);

        $client->request(
            Request::METHOD_POST,
            static::LOGIN_ENDPOINT,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode(['username' => $user->getUsername(), 'password' => '0000'], JSON_THROW_ON_ERROR)
        );

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
