<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Pokie'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            background: #ffffff;
            color: #111827;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }
        a { color: #2563eb; text-decoration: none; }
        a:hover { color: #1d4ed8; }
        h1, h2, h3, p { margin: 0; }

        /* Top bar / wordmark */
        .topbar { display: flex; align-items: center; gap: 12px; padding: 20px 24px 0; }
        .wordmark { font: 600 15px 'Instrument Sans', sans-serif; color: #111827; letter-spacing: -0.01em; }
        a.wordmark:hover { color: #111827; }
        .wordmark.dim { color: #a1a1aa; }
        a.wordmark.dim:hover { color: #71717a; }
        .crumb-sep { color: #e4e4e7; }
        @media (min-width: 640px) { .topbar { padding: 24px 40px 0; } }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 50px; padding: 0 20px; border: none; border-radius: 12px;
            font: 500 15px 'Instrument Sans', sans-serif; cursor: pointer; text-decoration: none;
            white-space: nowrap;
        }
        .btn-dark { background: #111827; color: #fff; }
        .btn-dark:hover { background: #27272a; color: #fff; }
        .btn-gray { background: #f4f4f5; color: #111827; }
        .btn-gray:hover { background: #e4e4e7; color: #111827; }
        .btn-blue-soft { background: #eff6ff; color: #2563eb; }
        .btn-blue-soft:hover { background: #dbeafe; color: #2563eb; }
        .btn-green { background: #16a34a; color: #fff; }
        .btn-green:hover { background: #15803d; color: #fff; }
        .btn-red { background: #dc2626; color: #fff; }
        .btn-red:hover { background: #b91c1c; color: #fff; }
        .btn-white { background: #ffffff; color: #111827; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
        .btn-white:hover { color: #111827; }
        .btn-sm { min-height: 44px; padding: 0 14px; border-radius: 10px; font-size: 14px; }
        .btn-xs { min-height: 40px; padding: 0 12px; border-radius: 10px; font-size: 13px; }
        .btn:disabled { opacity: 0.55; cursor: default; }
        .btn:disabled:hover { background: #111827; }
        .icon-btn { width: 44px; height: 44px; min-height: 44px; padding: 0; border-radius: 12px; font-size: 16px; flex-shrink: 0; }

        /* Forms */
        .field { display: flex; flex-direction: column; gap: 8px; }
        .label { font: 500 13px 'Instrument Sans', sans-serif; color: #111827; }
        .label.dim { color: #a1a1aa; }
        .input {
            height: 50px; border: none; border-radius: 12px; padding: 0 16px;
            font: 400 16px 'Instrument Sans', sans-serif; color: #111827; background: #f4f4f5;
            width: 100%; outline: none;
        }
        .input:focus { box-shadow: 0 0 0 2px #2563eb; }
        .input[aria-invalid="true"] { background: #fef2f2; box-shadow: 0 0 0 1.5px #dc2626; }
        .input:disabled { color: #a1a1aa; background: #fafafa; }
        select.input { padding: 0 12px; appearance: auto; }
        .amount-wrap { display: flex; align-items: center; background: #f4f4f5; border-radius: 12px; }
        .amount-wrap:focus-within { box-shadow: 0 0 0 2px #2563eb; }
        .amount-wrap.invalid { background: #fef2f2; box-shadow: 0 0 0 1.5px #dc2626; }
        .amount-wrap .currency { padding: 0 0 0 16px; font: 400 16px 'Instrument Sans', sans-serif; color: #a1a1aa; }
        .amount-wrap input {
            height: 50px; border: none; outline: none; padding: 0 16px 0 6px;
            font: 500 17px 'Instrument Sans', sans-serif; color: #111827; flex: 1; min-width: 0;
            font-variant-numeric: tabular-nums; background: transparent;
        }

        /* Text helpers */
        .eyebrow { font: 500 11px 'Instrument Sans', sans-serif; color: #a1a1aa; letter-spacing: 0.08em; text-transform: uppercase; }
        .hint { font: 400 13px 'Instrument Sans', sans-serif; color: #a1a1aa; }
        .error-text { font: 400 13px 'Instrument Sans', sans-serif; color: #dc2626; }
        .muted-sm { font: 400 13px 'Instrument Sans', sans-serif; color: #a1a1aa; }
        .tabular { font-variant-numeric: tabular-nums; }
        .pos { color: #16a34a; }
        .neg { color: #dc2626; }
        .zero { color: #a1a1aa; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* Surfaces */
        .card-subtle { background: #fafafa; border-radius: 16px; }
        .alert-error { background: #fef2f2; border-radius: 12px; padding: 12px 16px; font: 400 14px/1.45 'Instrument Sans', sans-serif; color: #b91c1c; }
        .banner-info { background: #eff6ff; border-radius: 16px; padding: 16px 18px; font: 400 14px/1.5 'Instrument Sans', sans-serif; color: #1e40af; }
        .banner-success { background: #f0fdf4; border-radius: 16px; padding: 32px 20px; display: flex; flex-direction: column; align-items: center; gap: 4px; text-align: center; }
        .pill { display: inline-flex; align-items: center; gap: 5px; background: #eff6ff; color: #2563eb; border-radius: 999px; padding: 3px 10px; font: 500 12px 'Instrument Sans', sans-serif; }
        .rowline { border-top: 1px solid #f4f4f5; }
        .hidden { display: none !important; }

        /* Segmented control */
        .seg { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px; background: #f4f4f5; border-radius: 12px; padding: 4px; }
        .seg button {
            min-height: 42px; border: none; background: transparent; color: #71717a;
            border-radius: 9px; font: 500 14px 'Instrument Sans', sans-serif; cursor: pointer;
        }
        .seg button:hover { color: #111827; }
        .seg button[aria-pressed="true"] { background: #ffffff; color: #111827; box-shadow: 0 1px 2px rgba(0,0,0,0.08); }

        /* Flash toast */
        .toast {
            position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 1100;
            max-width: min(92vw, 420px); padding: 12px 18px; border-radius: 12px;
            font: 500 14px 'Instrument Sans', sans-serif; box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transition: opacity 0.4s ease;
        }
        .toast-success { background: #f0fdf4; color: #15803d; }
        .toast-error { background: #fef2f2; color: #b91c1c; }
    </style>
</head>
<body>
    @if (session('success'))
        <div class="toast toast-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error') && ! trim($__env->yieldContent('inline-errors')))
        <div class="toast toast-error" role="alert">{{ session('error') }}</div>
    @endif
    @yield('content')
    <script>
        document.querySelectorAll('.toast').forEach(function (el) {
            setTimeout(function () {
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 450);
            }, 3500);
        });
    </script>
    @yield('scripts')
</body>
</html>
