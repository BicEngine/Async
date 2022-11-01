<?php

declare(strict_types=1);

if (!\function_exists('all')) {
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
    function all(iterable $tasks): array
    {
        return \Bic\Async\Task::all($tasks);
    }
}

if (!\function_exists('fiber_to_coroutine')) {
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
    function fiber_to_coroutine(\Fiber $fiber, mixed ...$args): \Generator
    {
        return \Bic\Async\Task::fiberToCoroutine($fiber, ...$args);
    }
}

if (!\function_exists('async')) {
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
    function async(callable $task, mixed ...$args): \Generator
    {
        return \Bic\Async\Task::async($task, ...$args);
    }
}
