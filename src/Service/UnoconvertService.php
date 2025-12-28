<?php

declare(strict_types = 1);

namespace App\Service;

use App\Dto\ConvertRequest;
use App\Exception\ConversionException;
use Generator;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Document to PDF Conversion Service using LibreOffice/unoserver.
 *
 * Provides two conversion strategies optimized for different file sizes:
 * - Stream mode (files ≤ threshold): Uses stdin/stdout, no filesystem writes
 * - Filesystem mode (files > threshold): Uses temp files with automatic cleanup
 *
 * Both modes return a callable that streams the PDF output in chunks,
 * preventing memory exhaustion for large documents.
 */
final readonly class UnoconvertService implements UnoconvertServiceInterface
{
    private const int DEFAULT_STREAM_THRESHOLD = 10 * 1024 * 1024;

    // Chunk size: 64KB
    private const int STREAM_CHUNK_SIZE = 65536;

    public function __construct(
        private string $unoconvertBinary = 'unoconvert',
        private string $unoserverHost = '127.0.0.1',
        private int $unoserverPort = 2003,
        private int $timeout = 120,
        private int $streamThreshold = self::DEFAULT_STREAM_THRESHOLD,
    ) {
    }

    /**
     * Converts a document to PDF using unoserver/unoconvert.
     *
     * @throws ConversionException When conversion fails or times out
     * @throws Throwable For unexpected errors during conversion setup
     *
     * @return callable(): void A callback that outputs the converted PDF to stdout in chunks
     */
    public function convert(UploadedFile $file, ?ConvertRequest $request = null): callable
    {
        $request ??= new ConvertRequest();
        $fileSize = $file->getSize();

        if ($fileSize !== false && $fileSize <= $this->streamThreshold) {
            return $this->convertViaStream($file, $request);
        }

        return $this->convertViaFilesystem($file, $request);
    }

    /**
     * Converts using stdin/stdout without filesystem writes.
     *
     * @throws ConversionException If file cannot be read or process fails to start
     *
     * @return callable(): void Callback that streams PDF output
     */
    private function convertViaStream(UploadedFile $file, ConvertRequest $request): callable
    {
        $command = $this->buildStreamCommand($request);

        $inputPath = $file->getPathname();
        $inputHandle = @fopen($inputPath, 'rb');
        if ($inputHandle === false) {
            throw new ConversionException('Failed to open input file for reading');
        }

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        $process->setInput($this->createInputStream($inputHandle));

        try {
            $process->start();
        } catch (\Exception $e) {
            @fclose($inputHandle);
            throw new ConversionException('Failed to start conversion process: ' . $e->getMessage());
        }

        return function () use ($process, $inputHandle): void {
            $errorOutput = '';

            try {
                while ($process->isRunning()) {
                    $output = $process->getIncrementalOutput();
                    if ($output !== '') {
                        echo $output;
                        flush();
                    }
                    $errorOutput .= $process->getIncrementalErrorOutput();
                    usleep(1000); // Small delay to prevent busy-waiting
                }

                $output = $process->getIncrementalOutput();
                if ($output !== '') {
                    echo $output;
                    flush();
                }
                $errorOutput .= $process->getIncrementalErrorOutput();

            } catch (ProcessTimedOutException) {
                throw ConversionException::processTimeout();
            } finally {
                @fclose($inputHandle);
            }

            if (!$process->isSuccessful()) {
                throw ConversionException::processFailed(
                    errorOutput: $errorOutput ?: $process->getOutput(),
                    exitCode: $process->getExitCode() ?? -1,
                );
            }
        };
    }

    /**
     * Creates an input stream generator from a file handle.
     *
     * @param resource $handle
     *
     * @return Generator<string>
     */
    private function createInputStream($handle): Generator
    {
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, self::STREAM_CHUNK_SIZE);
                if ($chunk !== false && $chunk !== '') {
                    yield $chunk;
                }
            }
        } finally {
            // Handle will be closed by the caller
        }
    }

    /**
     * Converts using filesystem with temp files.
     *
     * @throws ConversionException If conversion fails or times out
     * @throws Throwable For file system or process errors
     *
     * @return callable(): void Callback that streams PDF output and removes temp files
     */
    private function convertViaFilesystem(UploadedFile $file, ConvertRequest $request): callable
    {
        $inputPath = $this->createTempFile($file);
        $outputPath = $this->generateOutputPath($inputPath);

        try {
            $this->executeConversion($inputPath, $outputPath, $request);
        } catch (Throwable $e) {
            $this->cleanup($inputPath);
            $this->cleanup($outputPath);
            throw $e;
        }

        $this->cleanup($inputPath);

        return function () use ($outputPath): void {
            try {
                $this->streamFile($outputPath);
            } finally {
                $this->cleanup($outputPath);
            }
        };
    }

    /**
     * Streams a file to output in 64KB chunks.
     *
     * Reads and outputs the file incrementally.
     *
     * @throws ConversionException If file cannot be opened for reading
     */
    private function streamFile(string $path): void
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new ConversionException('Failed to read converted file');
        }

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, self::STREAM_CHUNK_SIZE);
                if ($chunk !== false && $chunk !== '') {
                    echo $chunk;
                    flush();
                }
            }
        } finally {
            @fclose($handle);
        }
    }

    /**
     * Creates a temporary file from the uploaded file.
     *
     * @return string Path to created temp file
     */
    private function createTempFile(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'tmp';
        $tempPath = sys_get_temp_dir() . '/' . uniqid('unoconvert_', true) . '.' . $extension;

        $file->move(dirname($tempPath), basename($tempPath));

        return $tempPath;
    }

    /**
     * Generates output path for converted PDF.
     *
     * @return string Path where converted PDF will be written
     */
    private function generateOutputPath(string $inputPath): string
    {
        $pathInfo = pathinfo($inputPath);

        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_output.pdf';
    }

    /**
     * Executes the conversion process using filesystem paths.
     *
     * Runs unoconvert synchronously and waits for completion.
     *
     * @param string $inputPath Path to input document
     * @param string $outputPath Path where PDF should be written
     * @param ConvertRequest $request PDF export options
     *
     * @throws ConversionException If conversion times out or fails
     */
    private function executeConversion(string $inputPath, string $outputPath, ConvertRequest $request): void
    {
        $command = $this->buildCommand($inputPath, $outputPath, $request);
        $process = new Process($command);
        $process->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw ConversionException::processTimeout();
        }

        if (!$process->isSuccessful()) {
            throw ConversionException::processFailed(
                errorOutput: $process->getErrorOutput() ?: $process->getOutput(),
                exitCode: $process->getExitCode() ?? -1,
            );
        }
    }

    /**
     * Builds command array for file-based conversion.
     *
     * Constructs unoconvert command with all arguments for filesystem mode.
     *
     * @return string[] Command array for Symfony Process component
     */
    private function buildCommand(string $inputPath, string $outputPath, ConvertRequest $request): array
    {
        $command = [
            $this->unoconvertBinary,
            '--host', $this->unoserverHost,
            '--port', (string)$this->unoserverPort,
            '--host-location', 'local',
            '--convert-to', 'pdf',
        ];

        if ($request->updateIndex === true) {
            $command[] = '--update-index';
        } else {
            $command[] = '--dont-update-index';
        }

        $this->applyRequestOptions($command, $request);

        $command[] = $inputPath;
        $command[] = $outputPath;

        return $command;
    }

    /**
     * Builds command array for stdin/stdout conversion.
     *
     * Constructs unoconvert command using "-" for stdin and stdout.
     *
     * @return string[] Command array for Symfony Process component
     */
    private function buildStreamCommand(ConvertRequest $request): array
    {
        $command = [
            $this->unoconvertBinary,
            '--host', $this->unoserverHost,
            '--port', (string)$this->unoserverPort,
            '--host-location', 'remote',
            '--convert-to', 'pdf',
        ];

        if ($request->updateIndex === true) {
            $command[] = '--update-index';
        } else {
            $command[] = '--dont-update-index';
        }

        $this->applyRequestOptions($command, $request);

        // Use stdin (-) for input and stdout (-) for output
        $command[] = '-';
        $command[] = '-';

        return $command;
    }

    private function cleanup(string $path): void
    {
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    private const array ACRONYM_MAP = [
        'PDF' => 'PDF',
        'UA' => 'UA',
        'OOO' => 'OOo',
        'TSA' => 'TSA',
    ];

    private const array SKIP_PROPERTIES = ['updateIndex', 'outputFile'];

    /**
     * Applies all PDF export options from the request to the command.
     *
     * Converts ConvertRequest properties to LibreOffice --filter-option arguments
     * using an explicit mapping for accurate option names.
     *
     * @param string[] $command Command array to modify (passed by reference)
     */
    private function applyRequestOptions(array &$command, ConvertRequest $request): void
    {
        foreach (get_object_vars($request) as $property => $value) {
            if ($value === null || in_array($property, self::SKIP_PROPERTIES, true)) {
                continue;
            }

            $optionName = $this->resolveOptionName($property);

            $command[] = '--filter-option';
            $command[] = $optionName . '=' . $this->formatOptionValue($value);
        }
    }

    /**
     * Resolves a DTO property name to a LibreOffice filter option name.
     *
     * Converts camelCase to PascalCase and applies specific acronym casing
     * (e.g., pdfUaCompliance -> PDFUACompliance).
     */
    private function resolveOptionName(string $property): string
    {
        $parts = preg_split('/(?=[A-Z])/', $property, -1, PREG_SPLIT_NO_EMPTY);
        $resolved = '';

        foreach ($parts as $part) {
            $upper = strtoupper($part);
            $resolved .= self::ACRONYM_MAP[$upper] ?? ucfirst($part);
        }

        return $resolved;
    }

    private function formatOptionValue(bool|int|string $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            default => (string)$value,
        };
    }
}
