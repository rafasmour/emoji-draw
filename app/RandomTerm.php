<?php

namespace App;

use App\Models\Term;

trait RandomTerm
{
    public function randomTerm()
    {
        return Term::all()->random(1)->first()->value;
    }
}
