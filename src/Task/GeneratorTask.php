<?php

declare(strict_types=1);

namespace Bic\Async\Task;

use Bic\Async\Exception\CompletedTaskException;
use Bic\Async\Exception\NonCompletedTaskException;
use Bic\Async\Task;
use Bic\Async\TaskInterface;

/**
 * @template TReturn of mixed
 * @template TSend of mixed
 * @template TValue of mixed
 *
 * @template-extends Task<TReturn, TSend, TValue>
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Bic\Async
 */
class GeneratorTask extends Task
{
    /**
     * @var TaskInterface|null
     */
    private ?TaskInterface $child = null;

    /**
     * @param \Generator<array-key, TSend, TReturn, TValue> $coroutine
     */
    public function __construct(
        private readonly \Generator $coroutine,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function throw(\Throwable $e): void
    {
        if ($this->child !== null && !$this->child->isCompleted()) {
            $this->child->throw($e);
            return;
        }

        $this->coroutine->throw($e);
    }

    /**
     * {@inheritDoc}
     */
    public function current(): mixed
    {
        $current = $this->coroutine->current();

        if ($current instanceof TaskInterface) {
            $this->child = $this->coroutine->current();
        }

        if ($this->child !== null) {
            if ($this->child->isCompleted()) {
                return $this->child->getResult();
            }

            return $this->child->current();
        }

        return $current;
    }

    /**
     * {@inheritDoc}
     *
     * @throws CompletedTaskException
     */
    public function resume(mixed $value = null): void
    {
        if (!$this->coroutine->valid()) {
            throw CompletedTaskException::fromResumedTask($this);
        }

        if ($this->coroutine->current() instanceof TaskInterface) {
            $this->child = $this->coroutine->current();
        }

        if ($this->child !== null) {
            if (!$this->child->isCompleted()) {
                $this->child->resume($value);
                return;
            }

            $this->coroutine->send($this->child->getResult());
            $this->child = null;
            return;
        }

        $this->coroutine->send($value);
    }

    /**
     * {@inheritDoc}
     */
    public function isCompleted(): bool
    {
        return !$this->coroutine->valid();
    }

    /**
     * {@inheritDoc}
     *
     * @throws NonCompletedTaskException
     */
    public function getResult(): mixed
    {
        if ($this->coroutine->valid()) {
            throw NonCompletedTaskException::fromFetchResult($this);
        }

        return $this->coroutine->getReturn();
    }
}
