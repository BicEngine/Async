<?php

declare(strict_types=1);

namespace Bic\Async\Task;

use Bic\Async\Task;

/**
 * @template TReturn of mixed
 * @template TSend of mixed
 * @template TValue of mixed
 *
 * @template-extends GeneratorTask<TReturn, TSend, TValue>
 *
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Bic\Async
 */
class FiberTask extends GeneratorTask
{
    /**
     * @param \Fiber<null, TSend, TReturn, TValue> $fiber
     * @throws \Throwable
     *
     * @psalm-suppress TooManyTemplateParams
     */
    public function __construct(
        \Fiber $fiber,
    ) {
        parent::__construct(self::toGenerator($fiber));
    }

    /**
     * @template TArgStart
     * @template TArgResume
     * @template TArgReturn
     * @template TArgSuspend
     *
     * @param \Fiber<TArgStart, TArgResume, TArgReturn, TArgSuspend> $fiber
     * @param TArgStart ...$args
     * @return \Generator<array-key, TArgResume, TArgReturn, TArgSuspend>
     * @throws \Throwable
     *
     * @psalm-suppress TooManyTemplateParams
     * @psalm-suppress MixedAssignment
     */
    private static function toGenerator(\Fiber $fiber, mixed ...$args): \Generator
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
}
