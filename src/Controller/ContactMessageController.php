<?php

declare(strict_types=1);

namespace App\Controller;

use Throwable;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use App\Entity\ContactMessage;
use App\Dto\CreateContactMessageRequestDto;
use App\Dto\ListContactMessagesRequestDto;
use App\Repository\ContactMessageRepository;

class ContactMessageController extends AbstractController
{
    private ContactMessageRepository $contactMessageRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(
        ContactMessageRepository $contactMessageRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->contactMessageRepository = $contactMessageRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/api/messages', methods: ['POST'])]
    #[OA\Post(
        operationId: 'createContactMessage',
        tags: ['Messages'],
        summary: 'Create contact message',
        description: 'Accepts contact form payload, validates it and saves into PostgreSQL.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullName', 'email', 'message', 'consent'],
                properties: [
                    new OA\Property(property: 'fullName', type: 'string', maxLength: 255, example: 'John Smith'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 320, example: 'john@example.com'),
                    new OA\Property(property: 'message', type: 'string', example: 'Lorem ipsum'),
                    new OA\Property(property: 'consent', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 123),
                        new OA\Property(property: 'fullName', type: 'string', example: 'John Smith'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                        new OA\Property(property: 'message', type: 'string', example: 'Lorem ipsum'),
                        new OA\Property(property: 'consent', type: 'boolean', example: true),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-19T12:00:00+00:00'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation errors',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'email' => ['Email is not valid'],
                                'consent' => ['Consent must be accepted'],
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Something went wrong'),
                    ]
                )
            )
        ]
    )]
    public function create(
        #[MapRequestPayload] CreateContactMessageRequestDto $dto
    ): JsonResponse {
        try {
            $entity = new ContactMessage(
                $dto->fullName,
                $dto->email,
                $dto->message,
                $dto->consent
            );

            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return $this->json(
                ['error' => 'Something went wrong'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json($entity, Response::HTTP_CREATED);
    }

    #[Route('/api/messages', methods: ['GET'])]
    #[OA\Get(
        operationId: 'listContactMessages',
        tags: ['Messages'],
        summary: 'List contact messages',
        description: 'Returns latest contact messages with pagination using limit and offset.',
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                description: 'Max number of items to return.',
                schema: new OA\Schema(type: 'integer', default: 20, minimum: 1, maximum: 100),
                example: 20
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                required: false,
                description: 'Number of items to skip.',
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0),
                example: 0
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 12),
                            new OA\Property(property: 'fullName', type: 'string', example: 'Jan Kowalski'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jan@example.com'),
                            new OA\Property(property: 'message', type: 'string', example: 'Treść wiadomości'),
                            new OA\Property(property: 'consent', type: 'boolean', example: true),
                            new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-19T12:00:00+00:00'),
                        ]
                    )
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error (query params)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            example: [
                                'limit' => ['This value should be between 1 and 100.'],
                                'offset' => ['This value should be positive or zero.'],
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Server error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string', example: 'Something went wrong'),
                    ]
                )
            ),
        ]
    )]
    public function get(
        #[MapQueryString] ListContactMessagesRequestDto $dto,
    ): JsonResponse {
        try {

            $return = $this->contactMessageRepository->getLatest(
                $dto->limit,
                $dto->offset
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return $this->json(
                ['message' => 'Something went wrong'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json($return);
    }
}
