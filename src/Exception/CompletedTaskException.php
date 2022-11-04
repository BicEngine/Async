<?php

declare(strict_types=1);

namespace Bic\Async\Exception;

use Bic\Async\TaskInterface;

class CompletedTaskException extends TaskException
{
    public static function fromResumedTask(TaskInterface $task): static
    {
        $message = 'Cannot resume already completed Task@%d';

        return new static(\sprintf($message, \spl_object_id($task)));
    }
}
