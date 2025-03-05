<?php

namespace Unleash\Client\Bootstrap;

use InvalidArgumentException;
use JsonException;
use Override;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Unleash\Client\Exception\InvalidValueException;

final class FileBootstrapProvider implements BootstrapProvider
{
    /**
     * @readonly
     * @var string|\SplFileInfo
     */
    private $file;
    /**
     * @param string|\SplFileInfo $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }
    /**
     * @throws Throwable
     * @throws JsonException
     *
     * @return array<mixed>
     */
    public function getBootstrap(): array
    {
        $filePath = $this->getFilePath($this->file);
        if ($exception = $this->getExceptionForInvalidPath($filePath)) {
            throw $exception;
        }
        $content = @file_get_contents($filePath);
        if ($content === false) {
            $error = error_get_last();
            throw new RuntimeException(sprintf("Failed to read the contents of file '%s': %s", $filePath, $error['message'] ?? 'Unknown error'));
        }
        $result = @json_decode($content, true);
        if (json_last_error()) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }
        if (!is_array($result)) {
            throw new InvalidValueException(sprintf("The file '%s' must contain a valid json object, '%s' given.", $filePath, gettype($result)));
        }
        return $result;
    }

    /**
     * @param string|\SplFileInfo $file
     */
    private function getFilePath($file): string
    {
        if ($file instanceof SplFileInfo) {
            if ($path = $file->getRealPath()) {
                return $path;
            }
            throw new InvalidArgumentException("The file '{$file}' does not exist.");
        }

        return $file;
    }

    private function getExceptionForInvalidPath(string $path): ?Throwable
    {
        if (!fnmatch('*://*', $path)) {
            $path = "file://{$path}";
        }
        if (strncmp($path, 'file://', strlen('file://')) !== 0) {
            return null;
        }

        if (!is_file($path)) {
            return new InvalidArgumentException("The file '{$path}' does not exist.");
        }
        if (!is_readable($path)) {
            return new RuntimeException("The file '{$path}' is not readable.");
        }

        return null;
    }
}
