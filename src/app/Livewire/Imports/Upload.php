<?php
namespace App\Livewire\Imports;
use Livewire\Component; use Livewire\WithFileUploads;
class Upload extends Component {
    use WithFileUploads;
    public $file; public array $mapping=[
        'observed_at'=>'Time','location'=>'Location',
        'inputs'=>['i_index'=>'I','j_index'=>'J','latitude'=>'Latitude','longitude'=>'Longitude'],
        'outputs'=>['target_depth_m'=>'Target','rf_depth_m'=>'RF','rbf_depth_m'=>'RBF','rnn_depth_m'=>'RNN','dt_depth_m'=>'DT','ann_depth_m'=>'ANN','lstm_depth_m'=>'LSTM','gru_depth_m'=>'GRU']
    ]; public string $name='Imported Run';
    public function render(){ return view('livewire.imports.upload'); }
}
