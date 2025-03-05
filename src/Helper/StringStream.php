<?php

namespace Unleash\Client\Helper;

use Override;
use Psr\Http\Message\StreamInterface;
use Unleash\Client\Exception\StreamException;

/**
 * @internal
 */
final class StringStream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @readonly
     */
    private int $size;

    public function __construct(
        string $content
    ) {
        $this->size = strlen($content);
        $stream = fopen('php://temp', 'w+');
        if (!is_resource($stream)) {
            // @codeCoverageIgnoreStart
            throw new StreamException('Cannot create temporary stream for storing data');
            // @codeCoverageIgnoreEnd
        }
        $this->stream = $stream;
        fwrite($this->stream, $content);
        rewind($this->stream);
    }

    #[Override]
    public function __toString(): string
    {
        $stream = $this->stream;
        if ($stream === null) {
            return '';
        }
        $content = stream_get_contents($stream, -1, 0);
        if (!is_string($content)) {
            // @codeCoverageIgnoreStart
            $content = '';
            // @codeCoverageIgnoreEnd
        }

        return $content;
    }

    #[Override]
    public function close(): void
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $type = get_resource_type($this->stream);
        if ($type !== 'stream') {
            throw new StreamException('The stream is already closed');
        }
        fclose($this->stream);
    }

    #[Override]
    public function detach()
    {
        $resource = $this->stream;
        $this->stream = null;

        return $resource;
    }

    #[Override]
    public function getSize(): int
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }

        return $this->size;
    }

    #[Override]
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $tell = ftell($this->stream);

        // this doesn't happen anymore in php 8.3, but is kept here for older versions
        // @codeCoverageIgnoreStart
        if ($tell === false) {
            throw new StreamException('Could not retrieve stream position. Is the stream after EOF?');
        }
        // @codeCoverageIgnoreEnd

        return $tell;
    }

    #[Override]
    public function eof(): bool
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }

        return feof($this->stream);
    }

    #[Override]
    public function isSeekable(): bool
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }

        return true;
    }

    #[Override]
    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        fseek($this->stream, $offset, $whence);
    }

    #[Override]
    public function rewind(): void
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        rewind($this->stream);
    }

    #[Override]
    public function isWritable(): bool
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }

        return true;
    }

    #[Override]
    public function write($string): int
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $result = fwrite($this->stream, $string);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new StreamException('Failed to write to the stream');
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    #[Override]
    public function isReadable(): bool
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }

        return true;
    }

    /**
     * @param int<1, max> $length
     */
    #[Override]
    public function read($length): string
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $result = fread($this->stream, $length);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new StreamException('Failed to read from stream');
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    #[Override]
    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $result = stream_get_contents($this->stream);
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new StreamException('Failed to read from stream');
            // @codeCoverageIgnoreEnd
        }

        return $result;
    }

    #[Override]
    public function getMetadata($key = null): mixed
    {
        if ($this->stream === null) {
            throw new StreamException('The stream is detached');
        }
        $metadata = stream_get_meta_data($this->stream);
        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
