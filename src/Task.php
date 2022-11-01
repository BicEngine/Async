<?php

declare(strict_types=1);

namespace Bic\Async;

final class Task
{
    /**
     * @template TReturn of mixed
     *
     * @param ( array<\Generator<mixed, mixed, TReturn, mixed>>
     *        | array<\Fiber<mixed, mixed, TReturn, mixed>>
     *        | callable(mixed):TReturn
     *        ) $tasks
     *
     * @return array<TReturn>
     * @throws \Throwable
     */
    public static function all(iterable $tasks): array
    {
        $coroutines = $result = [];

        foreach ($tasks as $index => $task) {
            $result[$index] = null;
            $coroutines[$index] = match (true) {
                $task instanceof \Generator => $task,
                $task instanceof \Fiber => self::fiberToCoroutine($task),
                \is_callable($task) => self::async($task),
                default => throw new \InvalidArgumentException('Invalid task type ' . \get_debug_type($task)),
            };
        }

        if (\Fiber::getCurrent()) {
            while ($coroutines !== []) {
                foreach ($coroutines as $index => $coroutine) {
                    if (!$coroutine->valid()) {
                        $result[$index] = $coroutine->getReturn();
                        unset($coroutines[$index]);
                        continue;
                    }

                    $send = \Fiber::suspend($coroutine->current());

                    $coroutine->send($send);
                }
            }
        }

        while ($coroutines !== []) {
            foreach ($coroutines as $index => $coroutine) {
                if (!$coroutine->valid()) {
                    $result[$index] = $coroutine->getReturn();
                    unset($coroutines[$index]);
                    continue;
                }

                $coroutine->next();
            }
        }

        return $result;
    }

    /**
     * @template TStart
     * @template TResume
     * @template TReturn
     * @template TSuspend
     *
     * @param \Fiber<TStart, TResume, TReturn, TSuspend> $fiber
     * @param TStart ...$args
     * @return \Generator<array-key, TResume, TReturn, TSuspend>
     * @throws \Throwable
     */
    public static function fiberToCoroutine(\Fiber $fiber, mixed ...$args): \Generator
    {
        $index = -1; // Note: Pre-increment is faster than post-increment.
        $value = null;

        // Allow an already running fiber.
        if (!$fiber->isStarted()) {
            $value = $fiber->start(...$args);

            if (!$fiber->isTerminated()) {
                $value = yield ++$index => $value;
            }
        }

        // A Fiber without suspends should return the result immediately.
        if (!$fiber->isTerminated()) {
            while (true) {
                $value = $fiber->resume($value);

                // The last call to "resume()" moves the execution of the
                // Fiber to the "return" stmt.
                //
                // So the "yield" is not needed. Skip this step and return
                // the result.
                if ($fiber->isTerminated()) {
                    break;
                }

                $value = yield ++$index => $value;
            }
        }

        return $fiber->getReturn();
    }

    /**
     * @template TReturn of mixed
     * @template TArg of mixed
     *
     * @param callable(TArg):TReturn $task
     * @param TArg ...$args
     *
     * @return \Generator<mixed, mixed, TReturn, mixed>
     * @throws \Throwable
     */
    public static function async(callable $task, mixed ...$args): \Generator
    {
        return self::fiberToCoroutine(new \Fiber($task), ...$args);
    }
}
