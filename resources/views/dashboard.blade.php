<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-3">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-3 text-gray-900 dark:text-gray-100 container mt-2">
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
                        <button type="submit" class="btn btn-primary">
                            <span id="btn-text">Скачать в MP3</span>
                            <div id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </button>
                    </form>

                    <h2 class="mt-4 mb-2">История загрузок</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Видео</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($downloads as $download)
                                <tr class="download-row" data-id="{{ $download->id }}"
                                    data-status="{{ $download->status }}">
                                    <td>
                                        <div class="title-container" data-id="{{ $download->id }}">
                                            <span
                                                class="title-text">{{ $download->title ?? $download->video_id }}</span>
                                            <i class="fas fa-edit edit-icon"></i>
                                            <button class="btn btn-sm btn-outline-primary mobile-edit-btn"
                                                    data-id="{{ $download->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>

                                        @if($download->status !== 'completed')
                                            <div class="progress mt-2">
                                                <div id="progress-{{ $download->id }}"
                                                     class="progress-bar progress-bar-striped progress-bar-animated"
                                                     role="progressbar"
                                                     style="width: {{ $download->progress }}%; height: 20px;"
                                                     aria-valuenow="{{ $download->progress }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                    <span class="progress-text">{{ $download->progress }}%</span>
                                                </div>
                                            </div>
                                            <div class="progress-status mt-1 small text-muted">
                                                Загрузка и конвертация...
                                            </div>
                                        @endif

                                        <div class="footer d-flex justify-content-between">
                                            <div class="self-start">
                                                   <span class="badge
                                                        @if($download->status === 'completed') bg-success
                                                        @elseif($download->status === 'processing') bg-warning text-dark
                                                        @elseif($download->status === 'failed') bg-danger
                                                        @else bg-secondary @endif">
                                                        @if($download->status === 'processing')
                                                           В процессе ({{ $download->progress }}%)
                                                       @else
                                                           {{ $download->status }}
                                                       @endif
                                                   </span>
                                            </div>
                                            <div class="d-flex">
                                                <div class="mx-3">
                                                    @if($download->status === 'completed')
                                                        <a href="{{ route('downloads.download', $download) }}"
                                                           class="btn btn-sm btn-success">
                                                            <i class="fas fa-download"></i>
                                                            <span class="d-none d-md-inline">Скачать</span>
                                                        </a>
                                                    @endif
                                                </div>
                                                @if($download->status === 'processing')
                                                    <div class="mx-3">
                                                        <form action="{{ route('downloads.cancel', $download) }}" method="POST" class="d-inline cancel-form">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-stop"></i> Остановить загрузку
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                                <form action="{{ route('downloads.destroy', $download) }}" method="POST"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                            onclick="return confirm('Вы уверены, что хотите удалить этот файл?')">
                                                        <i class="fas fa-trash"></i>
                                                        <span class="d-none d-md-inline">Удалить</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
