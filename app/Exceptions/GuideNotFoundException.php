<?php

namespace App\Exceptions;
use RuntimeException;

class GuideNotFoundException extends RuntimeException
{
    public function __construct(string $hashId)
    {
        parent::__construct("Guide with hash_id #{$hashId} could not be found.");
    }
}
