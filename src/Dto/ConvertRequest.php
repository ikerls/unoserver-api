<?php

declare(strict_types = 1);

namespace App\Dto;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Document to PDF conversion request DTO.
 *
 * Converts documents to PDF using LibreOffice/unoserver with optional PDF export options.
 *
 * @see https://help.libreoffice.org/latest/en-US/text/shared/guide/pdf_params.html
 */
#[OA\Schema(
    schema: 'ConvertRequest',
    description: 'Document to PDF conversion request. Supports all LibreOffice PDF export options.',
)]
final readonly class ConvertRequest
{
    public function __construct(
        // ==================== GENERAL PROPERTIES ====================
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Range of pages to export (e.g., "2-" to skip first page, "1-5", "1,3,5-7"). Empty exports all pages.',
            type: 'string',
            example: '1-5',
            nullable: true,
        )]
        public ?string $pageRange = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Use lossless compression (PNG) instead of JPEG for images.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $useLosslessCompression = null,
        #[Assert\Type('int')]
        #[Assert\Range(min: 1, max: 100)]
        #[OA\Property(
            description: 'JPEG quality (1-100). Higher = better quality, larger file.',
            type: 'integer',
            example: 90,
            nullable: true,
        )]
        public ?int $quality = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Reduce image resolution to MaxImageResolution value.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $reduceImageResolution = null,
        #[Assert\Type('int')]
        #[Assert\Choice(choices: [75, 150, 300, 600, 1200])]
        #[OA\Property(
            description: 'Target DPI when reduceImageResolution is true. Values: 75, 150, 300, 600, 1200.',
            type: 'integer',
            enum: [75, 150, 300, 600, 1200],
            example: 300,
            nullable: true,
        )]
        public ?int $maxImageResolution = null,
        #[Assert\Type('int')]
        #[Assert\Choice(choices: [0, 1, 2, 3, 15, 16, 17])]
        #[OA\Property(
            description: 'PDF version: 0=PDF 1.7, 1=PDF/A-1b, 2=PDF/A-2b, 3=PDF/A-3b, 15=PDF 1.5, 16=PDF 1.6, 17=PDF 1.7.',
            type: 'integer',
            enum: [0, 1, 2, 3, 15, 16, 17],
            example: 0,
            nullable: true,
        )]
        public ?int $selectPdfVersion = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Create accessible PDF following PDF/UA (ISO 14289) specification.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $pdfUaCompliance = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Create tagged PDF for accessibility.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $useTaggedPdf = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export form fields as widgets.',
            type: 'boolean',
            example: true,
            nullable: true,
        )]
        public ?bool $exportFormFields = null,
        #[Assert\Type('int')]
        #[Assert\Choice(choices: [0, 1, 2, 3])]
        #[OA\Property(
            description: 'Form submit format: 0=FDF, 1=PDF, 2=HTML, 3=XML.',
            type: 'integer',
            enum: [0, 1, 2, 3],
            example: 0,
            nullable: true,
        )]
        public ?int $formsType = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Allow multiple form fields with same name.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $allowDuplicateFieldNames = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export bookmarks to PDF.',
            type: 'boolean',
            example: true,
            nullable: true,
        )]
        public ?bool $exportBookmarks = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export placeholder field visual markings.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportPlaceholders = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export notes/comments to PDF.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportNotes = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export notes pages (Impress only).',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportNotesPages = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export only notes pages when exportNotesPages is true.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportOnlyNotesPages = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export notes in margin.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportNotesInMargin = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export slides not in slide show (Impress only).',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportHiddenSlides = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Suppress auto-inserted empty pages (Writer only).',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $isSkipEmptyPages = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Embed the 14 standard PDF fonts.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $embedStandardFonts = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Insert stream with original document for archiving.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $isAddStream = null,
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Watermark text drawn on every page.',
            type: 'string',
            example: 'CONFIDENTIAL',
            nullable: true,
        )]
        public ?string $watermark = null,
        #[Assert\Type('int')]
        #[OA\Property(
            description: 'Watermark text color as integer (default: 8388223 light green).',
            type: 'integer',
            example: 8388223,
            nullable: true,
        )]
        public ?int $watermarkColor = null,
        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        #[OA\Property(
            description: 'Watermark font height.',
            type: 'integer',
            nullable: true,
        )]
        public ?int $watermarkFontHeight = null,
        #[Assert\Type('int')]
        #[OA\Property(
            description: 'Watermark text rotation angle.',
            type: 'integer',
            nullable: true,
        )]
        public ?int $watermarkRotateAngle = null,
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Watermark font name.',
            type: 'string',
            example: 'Helvetica',
            nullable: true,
        )]
        public ?string $watermarkFontName = null,
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Tiled watermark text.',
            type: 'string',
            example: 'DRAFT',
            nullable: true,
        )]
        public ?string $tiledWatermark = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Use reference XObject markup for vector images.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $useReferenceXObject = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Enable redaction mode.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $isRedactMode = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Put every sheet on exactly one page, ignoring paper size and print ranges.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $singlePageSheets = null,

        // ==================== LINKS ====================
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export bookmarks as Named Destinations.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportBookmarksToPdfDestination = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Convert .od* extensions to .pdf in links.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $convertOooTargetToPdfTarget = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Export file:// links relative to source document.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $exportLinksRelativeFsys = null,

        // ==================== SECURITY ====================
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Encrypt PDF with password.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $encryptFile = null,
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Password to open encrypted PDF.',
            type: 'string',
            example: 'secret',
            nullable: true,
        )]
        public ?string $documentOpenPassword = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Restrict some permissions with password.',
            type: 'boolean',
            example: false,
            nullable: true,
        )]
        public ?bool $restrictPermissions = null,
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Password to change restricted permissions.',
            type: 'string',
            nullable: true,
        )]
        public ?string $permissionPassword = null,
        #[Assert\Type('int')]
        #[Assert\Choice(choices: [0, 1, 2])]
        #[OA\Property(
            description: 'Printing allowed: 0=none, 1=low resolution, 2=max resolution.',
            type: 'integer',
            enum: [0, 1, 2],
            example: 2,
            nullable: true,
        )]
        public ?int $printing = null,
        #[Assert\Type('int')]
        #[Assert\Choice(choices: [0, 1, 2, 3, 4])]
        #[OA\Property(
            description: 'Changes allowed: 0=none, 1=insert/delete/rotate pages, 2=fill forms, 3=forms+comments, 4=all except extraction.',
            type: 'integer',
            enum: [0, 1, 2, 3, 4],
            example: 4,
            nullable: true,
        )]
        public ?int $changes = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Allow copying pages and content.',
            type: 'boolean',
            example: true,
            nullable: true,
        )]
        public ?bool $enableCopyingOfContent = null,
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Allow content extraction for accessibility.',
            type: 'boolean',
            example: true,
            nullable: true,
        )]
        public ?bool $enableTextAccessForAccessibilityTools = null,

        // ==================== OTHER ====================
        #[Assert\Type('bool')]
        #[OA\Property(
            description: 'Update document indexes before conversion (table of contents, etc.).',
            type: 'boolean',
            example: true,
            nullable: true,
        )]
        public ?bool $updateIndex = true,

        // ==================== OUTPUT ====================
        #[Assert\Type('string')]
        #[OA\Property(
            description: 'Custom output filename (without extension). If not provided, uses the original filename.',
            type: 'string',
            example: 'converted-document',
            nullable: true,
        )]
        public ?string $outputFile = null,
    ) {
    }
}
