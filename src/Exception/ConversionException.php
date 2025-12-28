<?php

declare(strict_types = 1);

namespace App\Exception;

use RuntimeException;

/**
 * Exception thrown when document conversion fails.
 *
 * Contains optional error output and exit code from the unoconvert process
 * to help diagnose conversion failures.
 */
final class ConversionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorOutput = null,
        public readonly ?int $exitCode = null,
    ) {
        parent::__construct($message);
    }

    public static function processFailed(string $errorOutput, int $exitCode): self
    {
        return new self(
            message: 'Document conversion failed',
            errorOutput: $errorOutput,
            exitCode: $exitCode,
        );
    }

    public static function processTimeout(): self
    {
        return new self(
            message: 'Document conversion timed out',
        );
    }

    public static function invalidFormat(string $format): self
    {
        return new self(
            message: sprintf('Invalid output format: %s', $format),
        );
    }
}
