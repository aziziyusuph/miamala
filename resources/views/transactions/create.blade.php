<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Create Transaction</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 2rem; background: #f7f7f7; color: #222; }
            .container { max-width: 980px; margin: 0 auto; }
            .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
            h1 { margin-top: 0; }
            label { display: block; font-weight: 600; margin-bottom: 0.35rem; }
            input, select, textarea, button { width: 100%; padding: 0.65rem 0.75rem; font-size: 1rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
            button { width: auto; background: #0f172a; color: #fff; border: none; cursor: pointer; }
            .actions { display: flex; gap: 0.75rem; margin-top: 1rem; }
            a { color: #0f172a; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Create Transaction</h1>
            <div class="card">
                <form method="POST" action="{{ route('transactions.store') }}">
                    @csrf
                    @include('transactions._form', ['transaction' => new \App\Models\Transaction()])
                    <div class="actions">
                        <button type="submit">Create transaction</button>
                        <a href="{{ route('transactions.index') }}">Back to list</a>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
