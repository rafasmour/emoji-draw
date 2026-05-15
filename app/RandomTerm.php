<?php

namespace App;

use App\Models\Term;

trait RandomTerm
{
    public function randomTerm(): string
    {
        return Term::raw(fn ($collection) => $collection->aggregate([['$sample' => ['size' => 1]]]))->first()->value;
    }
}
