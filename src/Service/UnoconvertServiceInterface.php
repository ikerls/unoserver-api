<?php

declare(strict_types = 1);

namespace App\Service;

use App\Dto\ConvertRequest;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

interface UnoconvertServiceInterface
{
    /**
     * Converts a document to PDF using unoserver/unoconvert.
     * Returns a callable that streams the output when invoked.
     *
     *@throws Throwable
     *
     * @return callable(): void A callback that outputs the converted PDF
     */
    public function convert(UploadedFile $file, ?ConvertRequest $request = null): callable;
}
