<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataImport;
use Illuminate\Support\Facades\Storage;

class DataImportController extends Controller
{
    public function download(DataImport $import)
    {
        return Storage::disk($import->disk)->download($import->path, $import->original_name);
    }
}
