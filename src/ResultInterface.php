<?php

declare(strict_types=1);

namespace Bic\Async;

/**
 * @template TReturn of mixed
 */
interface ResultInterface
{
    /**
     * Gets a value that indicates whether the asynchronous
     * operation has completed.
     *
     * @return bool
     */
    public function isCompleted(): bool;

    /**
     * Returns asynchronous operation result after task
     * completion: {@see isCompleted()}.
     *
     * @return TReturn
     */
    public function getResult(): mixed;
}
