<!doctype html>
<title>Login Test</title>
@if ($errors->any())
  <div>{{ $errors->first() }}</div>
@endif
<form method="POST" action="{{ route('login.test') }}">
  @csrf
  <input name="email" type="email" value="admin@example.com">
  <input name="password" type="password" value="password">
  <button>Login</button>
</form>
