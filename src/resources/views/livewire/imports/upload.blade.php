<div>
  <label>Name <input type="text" name="name" value="{{ $name }}"></label>
  <label>File <input type="file" name="file"></label>
  <h3>Mapping JSON</h3>
  <textarea name="mapping" rows="10" style="width:100%">{{ json_encode($mapping, JSON_PRETTY_PRINT) }}</textarea>
  <p class="text-xs">Default mapping matches your model_results.xlsx columns.</p>
</div>