<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <style>
            .editable-title {
                cursor: pointer;
                position: relative;
                padding-right: 25px;
            }
            .edit-icon {
                position: absolute;
                right: 0;
                top: 50%;
                transform: translateY(-50%);
                opacity: 0.5;
                transition: opacity 0.2s;
            }
            .editable-title:hover .edit-icon {
                opacity: 1;
            }
            .edit-input {
                width: 100%;
                padding: 0.25rem;
                border: 1px solid #007bff;
                border-radius: 0.25rem;
            }
            .action-buttons {
                white-space: nowrap;
            }
            .mobile-edit-btn {
                display: none;
            }

            /* Адаптивность для мобильных устройств */
            @media (max-width: 768px) {
                .edit-icon {
                    display: none;
                }
                .mobile-edit-btn {
                    display: inline-block;
                    margin-left: 5px;
                }
                .action-buttons {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                }
                .action-buttons .btn {
                    flex: 1;
                    min-width: 80px;
                    font-size: 0.8rem;
                    padding: 0.25rem 0.5rem;
                }
            }
        </style>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="dark:bg-gray-800 shadow header">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
