<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Extensions — laranail/package-management</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 2rem; color: #1f2937; }
        h1 { font-size: 1.25rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { text-align: left; padding: .5rem .75rem; border-bottom: 1px solid #e5e7eb; font-size: .9rem; }
        th { color: #6b7280; font-weight: 600; }
        .badge { padding: .1rem .5rem; border-radius: .5rem; font-size: .75rem; }
        .on { background: #dcfce7; color: #166534; }
        .off { background: #f3f4f6; color: #6b7280; }
        form { display: inline; }
        button { cursor: pointer; border: 1px solid #d1d5db; background: #fff; border-radius: .375rem; padding: .25rem .6rem; font-size: .8rem; }
        .status { background: #eff6ff; color: #1e40af; padding: .5rem .75rem; border-radius: .375rem; margin-top: 1rem; }
        .empty { color: #6b7280; margin-top: 1rem; }
    </style>
</head>
<body>
    <h1>Extensions</h1>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if (count($extensions) === 0)
        <p class="empty">No extensions discovered under the configured platform paths.</p>
    @else
        <table>
            <thead>
                <tr><th>ID</th><th>Name</th><th>Role</th><th>Version</th><th>State</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach ($extensions as $extension)
                    <tr>
                        <td><code>{{ $extension->id }}</code></td>
                        <td>{{ $extension->name }}</td>
                        <td>{{ $extension->role }}</td>
                        <td>{{ $extension->version }}</td>
                        <td>
                            <span class="badge {{ $extension->enabled ? 'on' : 'off' }}">
                                {{ $extension->enabled ? 'enabled' : 'disabled' }}
                            </span>
                        </td>
                        <td>
                            @php($action = $extension->enabled ? 'disable' : 'enable')
                            <form method="POST" action="{{ route('laranail.extensions.' . $action) }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $extension->id }}">
                                <button type="submit">{{ ucfirst($action) }}</button>
                            </form>
                            <form method="POST" action="{{ route('laranail.extensions.install') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $extension->id }}">
                                <button type="submit">Install</button>
                            </form>
                            <form method="POST" action="{{ route('laranail.extensions.remove') }}">
                                @csrf
                                <input type="hidden" name="id" value="{{ $extension->id }}">
                                <button type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
