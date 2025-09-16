import $ from 'jquery';

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
    $input.on('blur', function () {
        finishEdit($container, id, $(this).val().trim(), currentText);
    });

    // Обработчик нажатия Enter
    $input.on('keypress', function (e) {
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
            success: function (response) {
                if (response.success) {
                    $titleText.text(newText);
                } else {
                    alert('Ошибка при обновлении названия');
                    $titleText.text(currentText);
                }
            },
            error: function () {
                alert('Ошибка при обновлении названия');
                $titleText.text(currentText);
            }
        });
    }
}

// Функция для опроса прогресса загрузок
function pollDownloadProgress() {
    // Находим все строки с загрузками
    const downloadRows = $('.download-row');
    const processingRows = downloadRows.filter('[data-status="processing"]');

    console.log('Найдено загрузок в процессе:', processingRows.length);

    if (processingRows.length === 0) {
        // Скрываем спиннер кнопки, если нет активных загрузок
        $('#btn-spinner').addClass('d-none');
        $('#btn-text').text('Скачать в MP3');
        $('#download-btn').prop('disabled', false);
        return;
    }

    const ids = processingRows.map(function() {
        return $(this).data('id');
    }).get();

    console.log('Опрашиваем прогресс для ID:', ids);

    // Используем правильный URL с учетом базового пути приложения
    const progressUrl = "/downloads/progress";

    $.ajax({
        url: progressUrl,
        type: 'GET',
        data: {ids: ids},
        success: function(data) {
            console.log('Получены данные прогресса:', data);
            let hasActiveDownloads = false;
            let needsReload = false;

            // Обновляем прогресс для каждой загрузки
            for (const id in data) {
                if (data.hasOwnProperty(id)) {
                    const progress = data[id].progress;
                    const status = data[id].status;

                    // Обновляем прогресс-бар
                    const progressBar = $(`#progress-${id}`);
                    if (progressBar.length) {
                        progressBar.css('width', progress + '%');
                        progressBar.attr('aria-valuenow', progress);
                        progressBar.find('.progress-text').text(progress + '%');
                    }

                    // Обновляем статус в таблице
                    const statusBadge = $(`.download-row[data-id="${id}"] .badge`);
                    const row = $(`.download-row[data-id="${id}"]`);

                    if (statusBadge.length) {
                        if (status === 'processing') {
                            statusBadge.text(`В процессе (${progress}%)`);
                            hasActiveDownloads = true;
                            // Обновляем data-атрибут
                            row.attr('data-status', 'processing');
                        } else {
                            // Если статус изменился, помечаем для перезагрузки
                            needsReload = true;
                            statusBadge.text(status);
                            if (status === 'completed') {
                                statusBadge.removeClass('bg-warning text-dark').addClass('bg-success');
                            } else if (status === 'failed') {
                                statusBadge.removeClass('bg-warning text-dark').addClass('bg-danger');
                            } else if (status === 'cancelled') {
                                statusBadge.removeClass('bg-warning text-dark').addClass('bg-secondary');
                            }
                            // Обновляем data-атрибут
                            row.attr('data-status', status);
                        }
                    }
                }
            }

            // Показываем/скрываем спиннер кнопки
            if (hasActiveDownloads) {
                $('#btn-spinner').removeClass('d-none');
                $('#btn-text').text('Загрузка...');
                $('#download-btn').prop('disabled', true);
            } else {
                $('#btn-spinner').addClass('d-none');
                $('#btn-text').text('Скачать в MP3');
                $('#download-btn').prop('disabled', false);
            }

            // Если есть завершенные загрузки, перезагружаем страницу
            if (needsReload) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ошибка при опросе прогресса:', error, xhr.responseText);
        }
    });
}

function cancelDownload(downloadId) {
    if (!confirm('Вы уверены, что хотите остановить загрузку?')) {
        return;
    }

    const form = $(`.cancel-form[action*="${downloadId}"]`);
    const url = form.attr('action');

    $.ajax({
        url: url,
        type: 'POST',
        data: form.serialize(),
        success: function(response) {
            if (response.success) {
                alert('Загрузка отменена');
                // Обновляем статус и скрываем кнопку отмены
                $(`.download-row[data-id="${downloadId}"]`).attr('data-status', 'cancelled');
                $(`.download-row[data-id="${downloadId}"] .badge`)
                    .removeClass('bg-warning text-dark')
                    .addClass('bg-secondary')
                    .text('cancelled');
                form.remove();

                // Обновляем спиннер кнопки
                $('#btn-spinner').addClass('d-none');
                $('#btn-text').text('Скачать в MP3');
                $('#download-btn').prop('disabled', false);
            } else {
                alert('Ошибка при отмене загрузки: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('Ошибка при отмене загрузки');
            console.error('Cancel error:', xhr.responseText);
        }
    });
}

// Инициализация при загрузке страницы
$(document).ready(function () {
    // Обработчики для десктопов
    $('.title-container').on('click', function (e) {
        // Активируем редактирование только при клике на текст, не на иконку
        if ($(e.target).hasClass('title-text')) {
            activateEditMode(this);
        }
    });

    // Обработчик для иконки редактирования
    $('.edit-icon').on('click', function (e) {
        e.stopPropagation();
        activateEditMode($(this).parent());
    });

    // Обработчики для мобильных кнопок редактирования
    $('.mobile-edit-btn').on('click', function () {
        const id = $(this).data('id');
        activateEditMode($(`.title-container[data-id="${id}"]`));
    });

    // Добавляем подтверждение перед удалением
    $('form[action*="destroy"]').on('submit', function (e) {
        if (!confirm('Вы уверены, что хотите удалить этот файл?')) {
            e.preventDefault();
        }
    });

    // Обработчик для формы отмены
    $(document).on('submit', '.cancel-form', function(e) {
        e.preventDefault();
        const downloadId = $(this).closest('.download-row').data('id');
        cancelDownload(downloadId);
    });

    // Запускаем опрос прогресса сразу и затем каждые 3 секунды
    // pollDownloadProgress();
    setInterval(pollDownloadProgress, 3000);
});
