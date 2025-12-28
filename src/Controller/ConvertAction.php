<?php

declare(strict_types = 1);

namespace App\Controller;

use App\Dto\ConvertRequest;
use App\Exception\ConversionException;
use App\Service\UnoconvertServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * Document to PDF Conversion Controller.
 *
 * Handles multipart/form-data requests containing a document file and optional
 * LibreOffice PDF export options. Streams the converted PDF back to the client
 * without buffering the entire file in memory.
 *
 * Supports documents (DOCX, DOC, ODT, RTF, TXT, HTML), spreadsheets (XLSX, XLS, ODS),
 * presentations (PPTX, PPT, ODP), images (PNG, JPG, TIFF), and PDF files up to 100MB.
 *
 * @see ConvertRequest For all available PDF export options
 */
#[Route('/convert', name: 'api_convert', methods: ['POST'])]
#[OA\Post(
    description: 'Converts a document to PDF using LibreOffice via unoserver. Supports all LibreOffice PDF export options as individual form fields.',
    summary: 'Convert a document to PDF',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(ref: '#/components/schemas/ConvertRequest'),
        ),
    ),
    tags: ['PDF Conversion'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Successfully converted document to PDF',
            headers: [
                new OA\Header(
                    header: 'Content-Disposition',
                    description: 'Attachment with converted filename',
                    schema: new OA\Schema(type: 'string'),
                ),
            ],
            content: new OA\MediaType(
                mediaType: 'application/pdf',
                schema: new OA\Schema(type: 'string', format: 'binary'),
            ),
        ),
        new OA\Response(
            response: 400,
            description: 'Invalid request (missing file, invalid options, etc.)',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'No file uploaded'),
                ],
            ),
        ),
        new OA\Response(
            response: 422,
            description: 'Conversion failed',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Document conversion failed'),
                    new OA\Property(property: 'details', type: 'string', nullable: true),
                ],
            ),
        ),
        new OA\Response(
            response: 504,
            description: 'Conversion timed out',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Document conversion timed out'),
                ],
            ),
        ),
    ],
)]
final class ConvertAction extends AbstractController
{
    private const array ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.presentation',
        'text/plain',
        'text/rtf',
        'text/html',
        'image/jpeg',
        'image/png',
        'image/tiff',
    ];

    public function __construct(
        private readonly UnoconvertServiceInterface $unoconvertService,
        private readonly ValidatorInterface $validator,
        private readonly string $uploadMaxSize = '100M',
    ) {
    }

    public function __invoke(#[MapUploadedFile] UploadedFile $file, #[MapRequestPayload] ?ConvertRequest $request = null): Response
    {
        $violations = $this->validator->validate($file, new Assert\File(
            maxSize: $this->uploadMaxSize,
            mimeTypes: self::ALLOWED_MIME_TYPES,
        ));

        if ($violations->count() > 0) {
            return $this->errorResponse((string)$violations->get(0)->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        try {
            $streamCallback = $this->unoconvertService->convert($file, $request);

            return $this->createStreamedResponse($streamCallback, $file->getClientOriginalName(), $request?->outputFile);
        } catch (ConversionException $e) {
            $statusCode = $e->getMessage() === 'Document conversion timed out'
                ? Response::HTTP_GATEWAY_TIMEOUT
                : Response::HTTP_UNPROCESSABLE_ENTITY;

            return $this->errorResponse($e->getMessage(), $statusCode, $e->errorOutput);
        } catch (Throwable $e) {
            return $this->errorResponse('Internal server error', Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    /**
     * Creates a StreamedResponse that outputs PDF content.
     *
     * Sets appropriate headers for PDF download and disables output buffering
     * to allow true streaming of large files without memory issues.
     *
     * @param callable $streamCallback Callback that outputs PDF content when invoked
     * @param string $originalName Original uploaded filename
     * @param string|null $outputFile Optional custom output filename (without extension)
     *
     * @return StreamedResponse Response with PDF stream and download headers
     */
    private function createStreamedResponse(callable $streamCallback, string $originalName, ?string $outputFile = null): StreamedResponse
    {
        $outputFilename = ($outputFile ?? pathinfo($originalName, PATHINFO_FILENAME)) . '.pdf';

        $response = new StreamedResponse($streamCallback);

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $outputFilename));
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    private function errorResponse(string $message, int $statusCode, ?string $details = null): JsonResponse
    {
        $data = ['error' => $message];

        if ($details !== null && $details !== '') {
            $data['details'] = $details;
        }

        return $this->json($data, $statusCode);
    }
}
