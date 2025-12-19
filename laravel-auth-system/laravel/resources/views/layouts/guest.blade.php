<!DOCTYPE html>
@php
    $settings = auth()->user()?->settings ?? [];
    $theme = $settings['theme'] ?? 'light';
    $fontSize = $settings['font_size'] ?? 'medium';

    $themeClasses = match ($theme) {
        'dark' => 'bg-gray-900 text-white',
        'blue' => 'bg-blue-50 text-blue-900',
        'contrast' => 'bg-white text-black',
        default => 'bg-gray-100 text-gray-900',
    };

    $fontClasses = match ($fontSize) {
        'small' => 'text-sm',
        'large' => 'text-lg',
        default => 'text-base',
    };
@endphp
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // FingerprintJS removed as per user request
    </script>
    <style>
        /* High Contrast Overrides */
        .theme-contrast .bg-white {
            background-color: #ffffff !important;
            border: 2px solid #000 !important;
        }

        .theme-contrast .text-gray-900 {
            color: #000000 !important;
        }

        .theme-contrast a {
            text-decoration: underline !important;
            font-weight: bold !important;
        }

        /* Dark Mode Overrides for standard components */
        .theme-dark .bg-white {
            background-color: #1f2937 !important;
            color: #f3f4f6 !important;
        }

        .theme-dark input {
            background-color: #374151 !important;
            color: white !important;
            border-color: #4b5563 !important;
        }

        .theme-dark .text-gray-900 {
            color: #f3f4f6 !important;
        }

        .theme-dark .text-gray-600 {
            color: #d1d5db !important;
        }
    </style>
</head>

<body class="font-sans antialiased {{ $themeClasses }} {{ $fontClasses }} theme-{{ $theme }}">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>

        <!-- Settings Link (Visible only if logged in) -->
        @auth
            <div class="mt-4">
                <a href="{{ route('settings.edit') }}" class="text-sm underline opacity-70 hover:opacity-100">
                    表示設定を変更
                </a>
            </div>
        @endauth
    </div>
    <!-- XREA Ad Code -->
    <script type="text/javascript" src="https://cache1.value-domain.com/xa.j?site=hgyujhgj.s325.xrea.com"></script>
    <script type="text/javascript" src="https://cache1.value-domain.com/xa.j?site=hgyujhgj.s325.xrea.com"></script>
    <script type="text/javascript" src="https://cache1.value-domain.com/xa.j?site=hgyujhgj.s325.xrea.com"></script>
</body>

</html>