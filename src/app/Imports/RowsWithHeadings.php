<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RowsWithHeadings implements WithHeadingRow
{
    // Empty on purpose. Implementing WithHeadingRow tells the reader
    // to return associative rows keyed by the header names.
}
