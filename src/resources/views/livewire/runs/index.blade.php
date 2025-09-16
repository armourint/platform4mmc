<div>
  <h2>Model Runs</h2>
  <table border="1" cellpadding="6">
    <tr><th>ID</th><th>Name</th><th>Uploaded</th></tr>
    @foreach($runs as $r)
      <tr><td>{{ $r->id }}</td><td>{{ $r->name }}</td><td>{{ $r->created_at }}</td></tr>
    @endforeach
  </table>
  {{ $runs->links() }}
</div>