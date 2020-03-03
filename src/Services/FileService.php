<?php

namespace SpazzMarticus\Tus\Services;

use SpazzMarticus\Tus\Exceptions\RuntimeException;
use SpazzMarticus\Tus\Exceptions\ConflictException;
use SplFileInfo;
use SplFileObject;
use Psr\Http\Message\StreamInterface;

final class FileService
{
    public function instance(string $path): SplFileInfo
    {
        return new SplFileInfo($path);
    }

    public function create(SplFileInfo $file): void
    {
        if ($this->exists($file)) {
            throw new RuntimeException('File ' . $file->getPathname() . ' already exists.');
        }

        //Psst! (fopen won't stop yapping without the magic @ duct-tape)
        if (@fopen($file->getPathname(), 'w') === false) {
            throw new RuntimeException('File ' . $file->getPathname() . ' could not be created');
        }
    }

    public function exists(SplFileInfo $file): bool
    {
        $pathname = $file->getPathname();
        /**
         * Affected by status cache
         * @see https://www.php.net/manual/en/function.clearstatcache.php
         */
        clearstatcache(false, $pathname);
        return file_exists($pathname);
    }

    public function size(SplFileInfo $file): int
    {
        $pathname = $file->getPathname();
        /**
         * Affected by status cache
         * @see https://www.php.net/manual/en/function.clearstatcache.php
         */
        clearstatcache(false, $pathname);
        return filesize($pathname) ?: 0;
    }

    public function delete(SplFileInfo $file): void
    {
        if ($this->exists($file)) {
            if (!unlink($file->getPathname())) {
                if ($this->exists($file)) {
                    /**
                     * @todo Not handled in TusServer
                     */
                    throw new RuntimeException("Could not delete file");
                }
            }
        }
    }

    /**
     * @return SplFileObject
     */
    public function open(SplFileInfo $file): SplFileObject
    {
        return new SplFileObject($file->getPathname(), 'rb+');
    }

    public function point(SplFileObject $handle, int $offset): void
    {
        if ($handle->fseek($offset) !== 0) {
            throw new RuntimeException('Can not set pointer in file');
        }
    }

    public function copyFromStream(SplFileObject $handle, StreamInterface $stream, int $chunkSize, ?int $sizeLimit = null): int
    {
        $bytesTransfered = 0;

        /**
         * Writing Input to Chunk
         * This in-between step is necessary for checking checksums
         * Reading input in chunks helps to support large files
         */
        while (!$stream->eof()) {
            $chunk = $stream->read($chunkSize);
            $bytes = $handle->fwrite($chunk);
            $handle->fflush();

            $bytesTransfered += $bytes;

            if ($sizeLimit && $bytesTransfered > $sizeLimit) {
                throw new ConflictException("Upload exceeds max allowed size");
            }
        }
        return $bytesTransfered;
    }
}
