<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ModelRun;
use App\Imports\GenericSheetImport;
use Maatwebsite\Excel\Facades\Excel;

class ModelRunController extends Controller {
    public function store(Request $request){
        $data=$request->validate([
            'name'=>['required','string','max:255'],
            'file'=>['required','file','mimes:csv,txt,xlsx,xls'],
            'mapping'=>['required'],
        ]);
        $path=$request->file('file')->store('model-data');
        $mapping = is_array($data['mapping']) ? $data['mapping'] : json_decode($data['mapping'], true, 512, JSON_THROW_ON_ERROR);
        $run=ModelRun::create([ 'name'=>$data['name'], 'source_file'=>$path, 'meta'=>['mapping'=>$mapping,'uploader_id'=>$request->user()->id ?? null] ]);
        Excel::import(new GenericSheetImport($run, $mapping), storage_path('app/'.$path));
        return redirect()->route('runs.index')->with('ok','Imported run: '.$run->name);
    }
}
