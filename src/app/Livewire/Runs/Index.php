<?php
namespace App\Livewire\Runs;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ModelRun;
class Index extends Component {
    use WithPagination;
    public function render(){ $runs=ModelRun::latest()->paginate(15); return view('livewire.runs.index', compact('runs')); }
}
