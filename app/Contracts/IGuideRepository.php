<?php

namespace App\Contracts;

use App\Models\Guide;
use Illuminate\Database\Eloquent\Model;

interface IGuideRepository
{
    /**
     * Returns the Guide Model instance matching the given $hashId, or null if none is found.
     * @param string $hashId
     * @return Model|Guide|null A Guide model instance or null
     */
    public function getByHashId(string $hashId): Model|Guide|null;
}
