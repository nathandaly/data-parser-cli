<?php

namespace App\Contracts;

use App\DTO\ClassifiedTags;

interface TagClassifierInterface
{
    public function classify(string $tags): ClassifiedTags;
}
