<?php

namespace App;

use App\Models\Term;

trait RandomTerm
{
    public function randomTerm(): string
    {
        $count = Term::query()->count();

        if ($count === 0) {
            throw new \RuntimeException('No terms in database. Run TermSeeder first.');
        }

        return Term::query()->skip(random_int(0, $count - 1))->first()->value;
    }
}
