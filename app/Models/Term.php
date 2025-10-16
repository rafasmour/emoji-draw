<?php

namespace App\Models;

use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Term extends Model
{
    protected $connection = 'mongodb';
    protected $fillable = [
        'value',
        'difficulty',
        'category',
        'language',
    ];
    protected $casts = [
        'difficulty' => 'string',
        'category' => 'string',
        'language' => 'string',
    ];
    /** @use HasFactory<TermFactory> */
    use HasFactory;
}
