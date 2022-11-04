<?php

declare(strict_types=1);

namespace Bic\Async;

use Bic\Async\Task\FiberTask;
use Bic\Async\Task\GeneratorTask;

/**
 * @template TReturn of mixed
 * @template TSend of mixed
 * @template TValue of mixed
 *
 * @template-implements TaskInterface<TReturn, TSend, TValue>
 * @template-implements \IteratorAggregate<array-key, TValue>
 */
abstract class Task implements TaskInterface, \IteratorAggregate
{
    /**
     * @param \Generator|\Fiber|callable $task
     *
     * @return static
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function new(\Generator|\Fiber|callable $task): static
    {
        return match (true) {
            \is_callable($task) => (new \ReflectionFunction($task(...)))->isGenerator()
                ? static::fromGenerator($task)
                : static::fromFiber($task),
            $task instanceof \Generator => self::fromGenerator($task),
            $task instanceof \Fiber => self::fromFiber($task),
        };
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @psalm-type TArgGenerator = \Generator<array-key, TArgSend, TArgReturn, TArgValue>
     *
     * @psalm-param TArgGenerator|callable():TArgGenerator $coroutine
     *
     * @return static<TArgReturn, TArgSend, TArgValue>
     */
    public static function fromGenerator(\Generator|callable $coroutine): static
    {
        if (!$coroutine instanceof \Generator) {
            $coroutine = $coroutine();

            if (!$coroutine instanceof \Generator) {
                $message = 'Argument #1 ($coroutine) must be of type '
                         . 'callable():Generator, callable():%s given';

                throw new \InvalidArgumentException(\sprintf($message, \get_debug_type($coroutine)));
            }
        }

        return new GeneratorTask($coroutine);
    }

    /**
     * @param \Fiber|callable $fiber
     *
     * @return static
     * @throws \Throwable
     */
    public static function fromFiber(\Fiber|callable $fiber): static
    {
        if (!$fiber instanceof \Fiber) {
            $fiber = new \Fiber($fiber);
        }

        return new FiberTask($fiber);
    }

    /**
     * @return \Generator<array-key, TSend, TReturn, TValue>
     */
    public function getGenerator(): \Generator
    {
        while (!$this->isCompleted()) {
            $send = yield $this->current();

            $this->resume($send);
        }

        return $this->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function getFiber(): \Fiber
    {
        return new \Fiber(function (): mixed {
            while (!$this->isCompleted()) {
                $next = \Fiber::suspend($this->current());

                $this->resume($next);
            }

            return $this->getResult();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->getGenerator();
    }

    /**
     * {@inheritDoc}
     */
    public function wait(): mixed
    {
        if (\Fiber::getCurrent()) {
            while (!$this->isCompleted()) {
                $value = \Fiber::suspend($this->current());

                $this->resume($value);
            }
        } else {
            while (!$this->isCompleted()) {
                $this->resume();
            }
        }

        return $this->getResult();
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return array<TArgReturn>
     */
    public static function waitAll(iterable $tasks): array
    {
        return self::all($tasks)->wait();
    }

    /**
     * The {@see all()} method takes an iterable of {@see TaskInterface} as
     * input and returns a single {@see TaskInterface}.
     *
     * This returned {@see TaskInterface} returns result when all of the input's
     * {@see TaskInterface} (including when an empty iterable is passed), with
     * an array of the returned values. It throws an error when any of the
     * input's {@see TaskInterface} fails, with this first {@see \Throwable}.
     *
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return TaskInterface<array<TArgReturn>, TArgSend, TArgValue>
     */
    public static function all(iterable $tasks): TaskInterface
    {
        $tasks = [...$tasks];

        return Task::fromGenerator(function () use ($tasks) {
            $result = [];

            while ($tasks !== []) {
                foreach ($tasks as $index => $task) {
                    if ($task->isCompleted()) {
                        $result[$index] = $task->getResult();
                        unset($tasks[$index]);
                        continue;
                    }

                    $task->resume(yield $task->current());
                }
            }

            \ksort($result);

            return $result;
        });
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     * @param positive-int $count
     *
     * @return array<TArgReturn>
     */
    public static function waitSome(iterable $tasks, int $count = 1): mixed
    {
        return self::some($tasks, $count)->wait();
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     * @param positive-int $count
     *
     * @return TaskInterface<array<TArgReturn>, TArgSend, TArgValue>
     */
    public static function some(iterable $tasks, int $count = 1): TaskInterface
    {
        $tasks = [...$tasks];

        return Task::fromGenerator(function () use ($count, $tasks) {
            $result = [];

            while ($tasks !== [] && $count > 0) {
                foreach ($tasks as $index => $task) {
                    if ($task->isCompleted()) {
                        $result[$index] = $task->getResult();
                        unset($tasks[$index]);
                        continue;
                    }

                    $task->resume(yield $task->current());
                }
            }

            \ksort($result);

            return $result;
        });
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return TArgReturn
     */
    public static function waitAny(iterable $tasks): mixed
    {
        return self::any($tasks)->wait();
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return TaskInterface<TArgReturn, TArgSend, TArgValue>
     */
    public static function any(iterable $tasks): TaskInterface
    {
        $tasks = [...$tasks];

        return Task::fromGenerator(function () use ($tasks) {
            while (true) {
                foreach ($tasks as $task) {
                    if ($task->isCompleted()) {
                        return $task->getResult();
                    }

                    $task->resume(yield $task->current());
                }
            }
        });
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return TArgReturn
     */
    public static function waitRace(iterable $tasks): mixed
    {
        return self::race($tasks)->wait();
    }

    /**
     * @template TArgReturn of mixed
     * @template TArgSend of mixed
     * @template TArgValue of mixed
     *
     * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
     *
     * @return TaskInterface<TArgReturn, TArgSend, TArgValue>
     */
    public static function race(iterable $tasks): TaskInterface
    {
        $tasks = [...$tasks];

        return Task::fromGenerator(function () use ($tasks) {
            [$completed, $result] = [false, null];

            while ($tasks !== []) {
                foreach ($tasks as $index => $task) {
                    if ($task->isCompleted()) {
                        if ($completed === false) {
                            $result = $task->getResult();
                            $completed = true;
                        }

                        unset($tasks[$index]);
                        continue;
                    }

                    $task->resume(yield $task->current());
                }
            }

            return $result;
        });
    }
}
