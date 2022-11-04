<?php

declare(strict_types=1);

namespace Bic\Async;

interface LoopInterface extends TaskProviderInterface
{
    /**
     * @param TaskInterface|\Fiber|\Generator|callable $task
     *
     * @return TaskInterface
     */
    public function attach(TaskInterface|\Fiber|\Generator|callable $task): TaskInterface;

    /**
     * @param TaskInterface $task
     *
     * @return void
     */
    public function cancel(TaskInterface $task): void;

    /**
     * @return void
     */
    public function stop(): bool;

    /**
     * @return void
     */
    public function start(): bool;
}
