<?php

declare(strict_types=1);

namespace Bic\Async;

/**
 * @template TReturn of mixed
 * @template TSend of mixed
 * @template TValue of mixed
 *
 * @template-extends ResultInterface<TReturn>
 * @template-extends \Traversable<array-key, TValue>
 */
interface TaskInterface extends ResultInterface, \Traversable
{
    /**
     * @param \Throwable $e
     *
     * @return void
     */
    public function throw(\Throwable $e): void;

    /**
     * Returns current task state value.
     *
     * @return TValue
     */
    public function current(): mixed;

    /**
     * Resumes suspended (and not completed) task with passed value.
     *
     * @param TSend|null $value
     */
    public function resume(mixed $value = null): void;

    /**
     * Awaits (blocks) task execution and returns task result.
     *
     * @return TReturn
     */
    public function wait(): mixed;

    /**
     * Returns current {@see TaskInterface} state as {@see \Generator}.
     *
     * @return \Generator<array-key, TSend, TReturn, TValue>
     */
    public function getGenerator(): \Generator;

    /**
     * Returns current {@see TaskInterface} state as {@see \Fiber}.
     *
     * @return \Fiber<mixed, TSend, TReturn, TValue>
     */
    public function getFiber(): \Fiber;
}
