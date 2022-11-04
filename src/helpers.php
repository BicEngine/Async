<?php

declare(strict_types=1);

namespace Bic\Async;

/**
 * @template TArgReturn of mixed
 * @template TArgSend of mixed
 * @template TArgValue of mixed
 *
 * @param non-empty-list<TaskInterface<TArgReturn, TArgSend, TArgValue>> $tasks
 *
 * @return TaskInterface<array<TArgReturn>, TArgSend, TArgValue>
 */
function all(iterable $tasks): TaskInterface
{
    return Task::all($tasks);
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
function some(iterable $tasks, int $count = 1): TaskInterface
{
    return Task::some($tasks, $count);
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
function any(iterable $tasks): TaskInterface
{
    return Task::any($tasks);
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
function race(iterable $tasks): TaskInterface
{
    return Task::race($tasks);
}

/**
 * @template TArgReturn of mixed
 * @template TArgSend of mixed
 * @template TArgValue of mixed
 *
 * @param TaskInterface<TArgReturn, TArgSend, TArgValue> $task
 *
 * @return TArgReturn
 */
function await(TaskInterface $task): mixed
{
    return $task->wait();
}
