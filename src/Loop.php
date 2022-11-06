<?php

declare(strict_types=1);

namespace Bic\Async;

final class Loop implements LoopInterface
{
    /**
     * @var \SplObjectStorage<TaskInterface, null>
     */
    private readonly \SplObjectStorage $tasks;

    /**
     * @var bool
     */
    private bool $running = false;

    public function __construct()
    {
        $this->tasks = new \SplObjectStorage();
    }

    /**
     * {@inheritDoc}
     */
    public function attach(callable|\Generator|\Fiber|TaskInterface $task): TaskInterface
    {
        if (!$task instanceof TaskInterface) {
            $task = Task::new($task);
        }

        $this->tasks->attach($task);

        return $task;
    }

    /**
     * {@inheritDoc}
     */
    public function cancel(TaskInterface $task): void
    {
        $this->tasks->detach($task);
    }

    /**
     * {@inheritDoc}
     */
    public function start(): bool
    {
        if ($this->running === true) {
            return false;
        }

        $this->running = true;

        while ($this->running) {
            foreach ($this->tasks as $task) {
                if ($task->isCompleted()) {
                    $this->tasks->detach($task);
                    continue;
                }

                try {
                    $value = $task->current();

                    if (\Fiber::getCurrent()) {
                        $value = \Fiber::suspend($value);
                    }

                    $task->resume($value);
                } catch (\Throwable $e) {
                    $task->throw($e);
                }
            }

            \usleep(1);
        }

        return true;
    }

    /**
     * @return TaskInterface
     * @throws \Throwable
     */
    public function getTask(): TaskInterface
    {
        return Task::fromFiber($this->start(...));
    }

    /**
     * {@inheritDoc}
     */
    public function stop(): bool
    {
        if ($this->running === true) {
            $this->running = false;
            return true;
        }

        return false;
    }
}
