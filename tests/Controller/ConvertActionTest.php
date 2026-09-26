<?php

declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Controller\ConvertAction;
use App\Dto\ConvertRequest;
use App\Exception\ConversionException;
use App\Service\UnoconvertServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class ConvertActionTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            @unlink($path);
        }
    }

    public function testReturnsPdfWithRequestedFilename(): void
    {
        $service = $this->createStub(UnoconvertServiceInterface::class);
        $service->method('convert')->willReturn(static function (): void {
            echo '%PDF-1.7 sample';
        });

        $response = $this->action($service)($this->upload('notes.txt', 'hello'), new ConvertRequest(outputFile: 'report'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/pdf', $response->headers->get('Content-Type'));
        self::assertSame('attachment; filename="report.pdf"', $response->headers->get('Content-Disposition'));
        ob_start();
        $response->sendContent();
        self::assertSame('%PDF-1.7 sample', ob_get_clean());
    }

    public function testRejectsUnsupportedFile(): void
    {
        $service = $this->createStub(UnoconvertServiceInterface::class);

        $response = $this->action($service)($this->upload('script.php', '<?php echo 1;'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testRejectsFileOverConfiguredLimit(): void
    {
        $service = $this->createStub(UnoconvertServiceInterface::class);

        $response = $this->action($service, '1K')($this->upload('notes.txt', str_repeat('a', 1025)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testReturnsConversionFailureDetails(): void
    {
        $service = $this->createStub(UnoconvertServiceInterface::class);
        $service->method('convert')->willThrowException(ConversionException::processFailed('converter refused input', 2));

        $response = $this->action($service)($this->upload('notes.txt', 'hello'));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Document conversion failed', 'details' => 'converter refused input'],
            json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testReturnsGatewayTimeoutForConversionTimeout(): void
    {
        $service = $this->createStub(UnoconvertServiceInterface::class);
        $service->method('convert')->willThrowException(ConversionException::processTimeout());

        $response = $this->action($service)($this->upload('notes.txt', 'hello'));

        self::assertSame(Response::HTTP_GATEWAY_TIMEOUT, $response->getStatusCode());
        self::assertSame(
            ['error' => 'Document conversion timed out'],
            json_decode((string)$response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    private function action(UnoconvertServiceInterface $service, string $maxSize = '100M'): ConvertAction
    {
        $action = new ConvertAction($service, Validation::createValidator(), $maxSize);
        $action->setContainer(new Container());

        return $action;
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
