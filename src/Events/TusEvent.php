<?php

declare(strict_types=1);

namespace SpazzMarticus\Tus\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Ramsey\Uuid\UuidInterface;
use SplFileInfo;

abstract class TusEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(protected UuidInterface $uuid, protected SplFileInfo $file, protected array $metadata) {}

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getFile(): SplFileInfo
    {
        return $this->file;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
