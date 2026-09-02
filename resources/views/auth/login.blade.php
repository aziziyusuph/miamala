<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Log in</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f7f7f7; color: #222; }
            .container { max-width: 440px; margin: 4rem auto; }
            .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
            h1 { margin-top: 0; }
            .field { margin-bottom: 1rem; }
            label { display: block; font-weight: 600; margin-bottom: 0.35rem; }
            input { width: 100%; padding: 0.65rem 0.75rem; font-size: 1rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
            button { padding: 0.65rem 0.9rem; background: #0f172a; color: #fff; border: 0; border-radius: 6px; cursor: pointer; }
            .error { margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fee2e2; color: #991b1b; border-radius: 6px; }
            .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
            .remember input { width: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card">
                <h1>Log in</h1>

                @if ($errors->any())
                    <div class="error">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>

                    <label class="remember" for="remember">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        Remember me
                    </label>

                    <button type="submit">Log in</button>
                </form>
            </div>
        </div>
    </body>
</html>
