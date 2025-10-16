<?php

namespace App\Http\Controllers;

use App\Models\Term;

class TermController extends Controller
{
    public function __construct(
        private Term $term,
    )
    {
    }

    public function index()
    {

    }
}
