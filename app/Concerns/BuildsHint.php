<?php

namespace App\Concerns;

trait BuildsHint
{
    protected function buildHint(string $term, int $revealed): string
    {
        $letterIndex = 0;
        $result = '';
        foreach (mb_str_split($term) as $char) {
            if ($char === ' ') {
                $result .= '  ';
            } else {
                $letterIndex++;
                $result .= ($letterIndex <= $revealed ? $char : '_').' ';
            }
        }

        return rtrim($result);
    }
}
