<?php

declare(strict_types = 1);

namespace App\Tests\Service;

use App\Dto\ConvertRequest;
use App\Exception\ConversionException;
use App\Service\UnoconvertService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UnoconvertServiceTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }
    }

    public function testSmallUploadStreamsConvertedContentAndOptions(): void
    {
        $service = new UnoconvertService($this->converter(), streamThreshold: 100);
        $callback = $service->convert(
            $this->upload('notes.txt', 'hello'),
            new ConvertRequest(quality: 75, pdfUaCompliance: true, updateIndex: false),
        );

        ob_start();
        $callback();
        $output = ob_get_clean();

        self::assertSame('PDF:hello|Quality=75|PDFUACompliance=true|--dont-update-index|remote', $output);
    }

    public function testLargeUploadConvertsThroughTemporaryFiles(): void
    {
        $service = new UnoconvertService($this->converter(), streamThreshold: 3);
        $callback = $service->convert($this->upload('notes.txt', 'hello'));

        ob_start();
        $callback();
        $output = ob_get_clean();

        self::assertSame('PDF:hello|none|none|--update-index|local', $output);
    }

    public function testFailedFilesystemConversionReportsError(): void
    {
        $service = new UnoconvertService($this->converter(), streamThreshold: 0);

        try {
            $service->convert($this->upload('notes.txt', 'FAIL'));
            self::fail('Expected conversion to fail.');
        } catch (ConversionException $e) {
            self::assertSame('Document conversion failed', $e->getMessage());
            self::assertSame('converter refused input', $e->errorOutput);
            self::assertSame(7, $e->exitCode);
        }
    }

    public function testFailedStreamConversionReportsErrorWhenContentIsRead(): void
    {
        $service = new UnoconvertService($this->converter(), streamThreshold: 100);
        $callback = $service->convert($this->upload('notes.txt', 'FAIL'));

        ob_start();
        try {
            $callback();
            self::fail('Expected conversion to fail.');
        } catch (ConversionException $e) {
            self::assertSame('Document conversion failed', $e->getMessage());
            self::assertSame('converter refused input', $e->errorOutput);
            self::assertSame(7, $e->exitCode);
        } finally {
            ob_end_clean();
        }
    }

    private function converter(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'fake_unoconvert_');
        self::assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        file_put_contents($path, <<<'PHP'
#!/usr/bin/env php
<?php
$args = array_slice($argv, 1);
$input = $args[count($args) - 2];
$output = $args[count($args) - 1];
$content = file_get_contents($input === '-' ? 'php://stdin' : $input);
if ($content === 'FAIL') {
    fwrite(STDERR, 'converter refused input');
    exit(7);
}
$option = static function (string $name) use ($args): string {
    foreach ($args as $arg) {
        if (str_starts_with($arg, $name . '=')) {
            return $arg;
        }
    }

    return 'none';
};
$payload = 'PDF:' . $content . '|' . $option('Quality') . '|' . $option('PDFUACompliance')
    . '|' . (in_array('--update-index', $args, true) ? '--update-index' : '--dont-update-index')
    . '|' . ($input === '-' ? 'remote' : 'local');
if ($output === '-') {
    echo $payload;
} else {
    file_put_contents($output, $payload);
}
PHP);
        chmod($path, 0700);

        return $path;
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload_test_');
        self::assertNotFalse($path);
        $this->temporaryFiles[] = $path;
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, test: true);
    }
}
