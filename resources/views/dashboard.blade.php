<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-4">Скачать видео с Rutube в MP3</h1>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('downloads.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="url" class="form-label">URL видео с Rutube</label>
                            <input type="url" class="form-control" id="url" name="url" required
                                   placeholder="https://rutube.ru/video/...">
                        </div>
                        <button type="submit" class="btn btn-primary">Скачать в MP3</button>
                    </form>

                    <h2 class="mt-5">История загрузок</h2>
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Видео</th>
                            <th>Статус</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($downloads as $download)
                            <tr>
                                <td>{{ $download->title ?? $download->video_id }}</td>
                                <td>
                            <span class="badge
                                @if($download->status === 'completed') bg-success
                                @elseif($download->status === 'processing') bg-warning
                                @elseif($download->status === 'failed') bg-danger
                                @else bg-secondary @endif">
                                {{ $download->status }}
                            </span>
                                </td>
                                <td>{{ $download->created_at->format('d.m.Y H:i') }}</td>
                                <td>
                                    @if($download->status === 'completed')
                                        <a href="{{ route('downloads.download', $download) }}"
                                           class="btn btn-sm btn-success">Скачать</a>
                                    @endif

                                    <form action="{{ route('downloads.destroy', $download) }}" method="POST"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Вы уверены, что хотите удалить этот файл?')">
                                            Удалить
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <script>
                    // Добавляем подтверждение перед удалением
                    document.addEventListener('DOMContentLoaded', function() {
                        const deleteForms = document.querySelectorAll('form[action*="destroy"]');

                        deleteForms.forEach(form => {
                            form.addEventListener('submit', function(e) {
                                if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
                                    e.preventDefault();
                                }
                            });
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
