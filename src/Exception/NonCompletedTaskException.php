<?php

declare(strict_types=1);

namespace Bic\Async\Exception;

use Bic\Async\TaskInterface;

class NonCompletedTaskException extends TaskException
{
    public static function fromFetchResult(TaskInterface $task): static
    {
        $message = 'Cannot get result value of a Task@%d that has not completed';

        return new static(\sprintf($message, \spl_object_id($task)));
    }
}
