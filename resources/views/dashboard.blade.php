<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 container mt-3">
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
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Видео</th>
                                <th>Статус</th>
                                {{--                                <th>Дата</th>--}}
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($downloads as $download)
                                <tr>
                                    <td>
                                        <span class="editable-title" data-id="{{ $download->id }}">
                                            {{ $download->title ?? $download->video_id }}
                                            <i class="fas fa-edit edit-icon"></i>
                                        </span>
                                        {{--                                        <button class="btn btn-sm btn-outline-primary mobile-edit-btn" data-id="{{ $download->id }}">--}}
                                        {{--                                            <i class="fas fa-edit"></i>--}}
                                        {{--                                        </button>--}}
                                    </td>
                                    <td>
                                <span class="badge
                                    @if($download->status === 'completed') bg-success
                                    @elseif($download->status === 'processing') bg-warning
                                    @elseif($download->status === 'failed') bg-danger
                                    @else bg-secondary @endif">
                                    {{ $download->status }}
                                </span>
                                    </td>
                                    {{--                                    <td>{{ $download->created_at->format('d.m.Y H:i') }}</td>--}}
                                    <td class="action-buttons d-flex justify-content-around">
                                        @if($download->status === 'completed')
                                            <div>
                                                <a href="{{ route('downloads.download', $download) }}"
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-download"></i>
                                                    <span class="d-none d-md-inline">Скачать</span>
                                                </a>
                                            </div>
                                        @endif

                                        <div>
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
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                <script>
                    // Добавляем подтверждение перед удалением
                    document.addEventListener('DOMContentLoaded', function () {
                        const deleteForms = document.querySelectorAll('form[action*="destroy"]');

                        deleteForms.forEach(form => {
                            form.addEventListener('submit', function (e) {
                                if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
                                    e.preventDefault();
                                }
                            });
                        });
                    });

                    // Функция активации редактирования
                    function activateEditMode(element) {
                        const $this = $(element);
                        const currentText = $this.text().trim();
                        const id = $this.data('id');

                        // Заменяем текст на input
                        $this.html(`<input type="text" class="edit-input" value="${currentText}">`);
                        const $input = $this.find('input');

                        // Фокусируемся на input
                        $input.focus();

                        // Обработчик потери фокуса
                        $input.on('blur', function () {
                            finishEdit($this, id, $(this).val().trim(), currentText);
                        });

                        // Обработчик нажатия Enter
                        $input.on('keypress', function (e) {
                            if (e.which === 13) { // Enter
                                $(this).blur();
                            }
                        });
                    }

                    // Функция завершения редактирования
                    function finishEdit($element, id, newText, currentText) {
                        if (newText && newText !== currentText) {
                            // Отправляем AJAX-запрос для обновления
                            $.ajax({
                                url: `/downloads/${id}`,
                                type: 'PATCH',
                                data: {
                                    title: newText,
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $element.html(newText + '<i class="fas fa-edit edit-icon"></i>');
                                        setupEditHandlers($element);
                                    } else {
                                        alert('Ошибка при обновлении названия');
                                        $element.html(currentText + '<i class="fas fa-edit edit-icon"></i>');
                                        setupEditHandlers($element);
                                    }
                                },
                                error: function () {
                                    alert('Ошибка при обновлении названия');
                                    $element.html(currentText + '<i class="fas fa-edit edit-icon"></i>');
                                    setupEditHandlers($element);
                                }
                            });
                        } else {
                            $element.html(currentText + '<i class="fas fa-edit edit-icon"></i>');
                            setupEditHandlers($element);
                        }
                    }

                    // Настройка обработчиков редактирования
                    function setupEditHandlers(element) {
                        // Для десктопов - клик по тексту
                        $(element).on('click', function (e) {
                            if (!$(e.target).hasClass('edit-icon')) {
                                activateEditMode(this);
                            }
                        });

                        // Для иконки редактирования
                        $(element).find('.edit-icon').on('click', function (e) {
                            e.stopPropagation();
                            activateEditMode($(this).parent());
                        });
                    }

                    // Инициализация при загрузке страницы
                    $(document).ready(function () {
                        // Настройка обработчиков для всех заголовков
                        $('.editable-title').each(function () {
                            setupEditHandlers(this);
                        });

                        // Обработчики для мобильных кнопок редактирования
                        $('.mobile-edit-btn').on('click', function () {
                            const id = $(this).data('id');
                            activateEditMode($(`.editable-title[data-id="${id}"]`));
                        });

                        // Добавляем подтверждение перед удалением
                        $('form[action*="destroy"]').on('submit', function (e) {
                            if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
                                e.preventDefault();
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</x-app-layout>
