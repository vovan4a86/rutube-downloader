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
                                <th>Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($downloads as $download)
                                <tr>
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
                    // Функция активации редактирования
                    function activateEditMode(element) {
                        const $container = $(element);
                        const $titleText = $container.find('.title-text');
                        const currentText = $titleText.text().trim();
                        const id = $container.data('id');

                        // Создаем input для редактирования
                        const $input = $(`<input type="text" class="edit-input" value="${currentText}">`);

                        // Заменяем текст на input
                        $titleText.replaceWith($input);

                        // Фокусируемся на input
                        $input.focus();

                        // Обработчик потери фокуса
                        $input.on('blur', function() {
                            finishEdit($container, id, $(this).val().trim(), currentText);
                        });

                        // Обработчик нажатия Enter
                        $input.on('keypress', function(e) {
                            if (e.which === 13) { // Enter
                                $(this).blur();
                            }
                        });
                    }

                    // Функция завершения редактирования
                    function finishEdit($container, id, newText, currentText) {
                        // Восстанавливаем текстовый элемент
                        const $titleText = $(`<span class="title-text">${currentText}</span>`);
                        $container.find('.edit-input').replaceWith($titleText);

                        if (newText && newText !== currentText) {
                            // Отправляем AJAX-запрос для обновления
                            $.ajax({
                                url: `/downloads/${id}`,
                                type: 'PATCH',
                                data: {
                                    title: newText,
                                    _token: '{{ csrf_token() }}'
                                },
                                success: function(response) {
                                    if (response.success) {
                                        $titleText.text(newText);
                                    } else {
                                        alert('Ошибка при обновлении названия');
                                        $titleText.text(currentText);
                                    }
                                },
                                error: function() {
                                    alert('Ошибка при обновлении названия');
                                    $titleText.text(currentText);
                                }
                            });
                        }
                    }

                    // Инициализация при загрузке страницы
                    $(document).ready(function() {
                        // Обработчики для десктопов
                        $('.title-container').on('click', function(e) {
                            // Активируем редактирование только при клике на текст, не на иконку
                            if ($(e.target).hasClass('title-text')) {
                                activateEditMode(this);
                            }
                        });

                        // Обработчик для иконки редактирования
                        $('.edit-icon').on('click', function(e) {
                            e.stopPropagation();
                            activateEditMode($(this).parent());
                        });

                        // Обработчики для мобильных кнопок редактирования
                        $('.mobile-edit-btn').on('click', function() {
                            const id = $(this).data('id');
                            activateEditMode($(`.title-container[data-id="${id}"]`));
                        });

                        // Добавляем подтверждение перед удалением
                        $('form[action*="destroy"]').on('submit', function(e) {
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
