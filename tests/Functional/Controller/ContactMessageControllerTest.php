<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\ContactMessage;

class ContactMessageControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->clearMessagesTable();
    }

    private function clearMessagesTable(): void
    {
        $conn = $this->em->getConnection();
        $conn->executeStatement('TRUNCATE TABLE contact_message');
    }

    public function testCreateMessageSuccess(): void
    {
        $payload = [
            'fullName' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'message' => 'Hello',
            'consent' => true,
        ];

        $this->client->request(
            method: 'POST',
            uri: '/api/messages',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $repo = $this->em->getRepository(ContactMessage::class);
        $saved = $repo->findOneBy(['email' => 'jan@example.com']);

        self::assertNotNull($saved);
        self::assertSame('Jan Kowalski', $saved->getFullName());
        self::assertSame('jan@example.com', $saved->getEmail());
        self::assertSame('Hello', $saved->getMessage());
        self::assertTrue($saved->isConsent());

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertIsArray($data);
        self::assertArrayHasKey('id', $data);
    }

    public function testCreateMessageValidationErrors(): void
    {
        $payload = [
            'email' => 'not-an-email',
            'message' => '',
            'consent' => false,
        ];

        $this->client->request(
            method: 'POST',
            uri: '/api/messages',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('fullName', $data['errors']);
        self::assertArrayHasKey('email', $data['errors']);
        self::assertArrayHasKey('message', $data['errors']);
        self::assertArrayHasKey('consent', $data['errors']);

        $repo = $this->em->getRepository(ContactMessage::class);
        self::assertSame(0, count($repo->findAll()));
    }

    public function testCreateMessageInvalidJsonReturns400(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/messages',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: '{'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testListMessagesReturnsItems(): void
    {
        $m1 = new ContactMessage('Ala', 'ala@example.com', 'Msg 1', true);
        $m2 = new ContactMessage('Ola', 'ola@example.com', 'Msg 2', true);

        $this->em->persist($m1);
        $this->em->persist($m2);
        $this->em->flush();

        $this->client->request(
            method: 'GET',
            uri: '/api/messages?limit=20&offset=0',
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertIsArray($data);
        self::assertGreaterThanOrEqual(2, count($data));

        $first = $data[0];
        self::assertArrayHasKey('id', $first);
        self::assertArrayHasKey('fullName', $first);
        self::assertArrayHasKey('email', $first);
        self::assertArrayHasKey('message', $first);
        self::assertArrayHasKey('consent', $first);
        self::assertArrayHasKey('createdAt', $first);
    }

    public function testListMessagesValidationError(): void
    {
        $this->client->request(
            method: 'GET',
            uri: '/api/messages?limit=101&offset=-1',
            server: [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        self::assertResponseStatusCodeSame(400);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode(
            $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('limit', $data['errors']);
        self::assertArrayHasKey('offset', $data['errors']);
    }
}
