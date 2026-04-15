<?php
$title = 'Calendario - Consejo Comunal';
$active_page = 'calendar';
?>

<div class="page-header fade-in calendar-page">
    <h1><i class="bi bi-calendar-event"></i> Calendario de Actividades</h1>
    <div class="page-actions">
        <button id="btnAddCalendarEvent" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Evento
        </button>
    </div>
</div>

<div class="calendar-page">
    <div class="calendar-header d-flex justify-content-between align-items-center mb-4">
        <button class="btn btn-outline-secondary btn-sm" id="prevMonth">&lsaquo; Anterior</button>
        <div>
            <span id="currentMonthName" class="fs-5 fw-semibold"></span>
            <span id="currentYear" class="fs-5 fw-semibold"></span>
        </div>
        <button class="btn btn-outline-secondary btn-sm" id="nextMonth">Siguiente &rsaquo;</button>
    </div>

    <div id="calendarGrid" class="calendar-grid mb-4"></div>

    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">Eventos para <span id="selectedDateLabel"></span></h5>
                <small class="text-muted">Haz clic en un día para ver o agregar actividades.</small>
            </div>
            <button id="btnAddEventForDay" class="btn btn-outline-primary btn-sm">Agregar evento</button>
        </div>
        <div id="eventList"></div>
    </div>
</div>

<!-- Modal de evento -->
<div class="modal fade" id="calendarEventModal" tabindex="-1" aria-labelledby="calendarEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="calendarEventModalLabel">Nuevo Evento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="calendarEventForm">
                <input type="hidden" id="event_id" name="id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="event_title" class="form-label">Título</label>
                        <input type="text" class="form-control" id="event_title" name="title" required>
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label for="event_date" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="event_date" name="event_date" required>
                        </div>
                        <div class="col-4 mb-3">
                            <label for="event_time" class="form-label">Hora</label>
                            <input type="time" class="form-control" id="event_time" name="event_time">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="event_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="event_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var calendarEvents = [];
var currentDate = new Date();
var selectedDate = new Date();
var calendarModal;

function formatSpanishMonth(year, month) {
    var meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return meses[month] + ' ' + year;
}

function formatDateKey(date) {
    return date.toISOString().split('T')[0];
}

function getEventsByDate(dateKey) {
    return calendarEvents.filter(function(evento) {
        return evento.event_date === dateKey;
    });
}

function renderCalendar() {
    var year = currentDate.getFullYear();
    var month = currentDate.getMonth();
    var firstOfMonth = new Date(year, month, 1);
    var lastOfMonth = new Date(year, month + 1, 0);
    var startDay = firstOfMonth.getDay();
    var daysInMonth = lastOfMonth.getDate();
    var todayKey = formatDateKey(new Date());

    $('#currentMonthName').text(formatSpanishMonth(year, month));
    $('#currentYear').text(year);

    var html = '<table class="table mb-0">';
    html += '<thead><tr>';
    var dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    dias.forEach(function(d) {
        html += '<th>' + d + '</th>';
    });
    html += '</tr></thead><tbody>';

    var cell = 0;
    var started = false;

    while (cell < 42) {
        if (cell % 7 === 0) html += '<tr>';

        if (cell >= startDay && cell < startDay + daysInMonth) {
            started = true;
            var day = cell - startDay + 1;
            var dayDate = new Date(year, month, day);
            var dateKey = formatDateKey(dayDate);
            var events = getEventsByDate(dateKey);
            var selectedClass = formatDateKey(selectedDate) === dateKey ? ' selected' : '';
            var todayClass = todayKey === dateKey ? ' border border-primary' : '';
            html += '<td class="calendar-day' + selectedClass + todayClass + '" data-date="' + dateKey + '">';
            html += '<div class="day-number">' + day + '</div>';
            if (events.length) {
                html += '<div class="event-count">' + events.length + ' evento' + (events.length > 1 ? 's' : '') + '</div>';
            }
            html += '</td>';
        } else {
            html += '<td></td>';
        }

        if (cell % 7 === 6) html += '</tr>';
        cell++;
    }
    html += '</tbody></table>';

    $('#calendarGrid').html(html);
    $('#calendarGrid .calendar-day').on('click', function() {
        selectedDate = new Date($(this).data('date'));
        renderCalendar();
        renderEventList();
    });

    renderEventList();
}

function renderEventList() {
    var dateKey = formatDateKey(selectedDate);
    $('#selectedDateLabel').text(dateKey);
    var events = getEventsByDate(dateKey);
    var list = '';

    if (!events.length) {
        list = '<div class="alert alert-info">No hay eventos programados para este día.</div>';
    } else {
        events.forEach(function(evento) {
            list += '<div class="event-card">';
            list += '<div class="d-flex justify-content-between align-items-start">';
            list += '<div><h6 class="mb-1">' + $('<div>').text(evento.title).html() + '</h6>';
            list += '<div class="event-time">' + (evento.event_time ? evento.event_time + ' · ' : '') + evento.event_date + '</div></div>';
            list += '<div><button class="btn btn-sm btn-outline-secondary me-1" onclick="editCalendarEvent(' + evento.id + ')"><i class="bi bi-pencil"></i></button>';
            list += '<button class="btn btn-sm btn-outline-danger" onclick="deleteCalendarEvent(' + evento.id + ')"><i class="bi bi-trash"></i></button></div>';
            list += '</div>';
            if (evento.description) {
                list += '<p class="mt-2 mb-0 text-muted">' + $('<div>').text(evento.description).html() + '</p>';
            }
            list += '</div>';
        });
    }

    $('#eventList').html(list);
}

function loadCalendarEvents() {
    return $.getJSON(baseUrl + '/src/controllers/calendar.php?action=list').done(function(response) {
        if (response.status === 'ok') {
            calendarEvents = response.data;
            renderCalendar();
        } else {
            alert(response.message || 'Error al cargar eventos');
        }
    }).fail(function() {
        alert('Error al cargar eventos del calendario');
    });
}

function openCalendarModal(dateKey) {
    $('#calendarEventModalLabel').text('Nuevo Evento');
    $('#event_id').val('');
    $('#event_title').val('');
    $('#event_date').val(dateKey);
    $('#event_time').val('');
    $('#event_description').val('');
    calendarModal.show();
}

function editCalendarEvent(id) {
    $.getJSON(baseUrl + '/src/controllers/calendar.php?action=get&id=' + id).done(function(response) {
        if (response.status !== 'ok') {
            alert(response.message || 'Evento no encontrado');
            return;
        }
        var evento = response.data;
        $('#calendarEventModalLabel').text('Editar Evento');
        $('#event_id').val(evento.id);
        $('#event_title').val(evento.title);
        $('#event_date').val(evento.event_date);
        $('#event_time').val(evento.event_time);
        $('#event_description').val(evento.description);
        calendarModal.show();
    }).fail(function() {
        alert('Error al cargar evento');
    });
}

function deleteCalendarEvent(id) {
    if (!confirm('¿Eliminar este evento?')) return;
    $.post(baseUrl + '/src/controllers/calendar.php?action=delete', {
        id: id,
        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
    }, function(response) {
        if (response.status === 'ok') {
            loadCalendarEvents();
        } else {
            alert(response.message || 'No se pudo eliminar el evento');
        }
    }, 'json');
}

$(document).ready(function() {
    calendarModal = new bootstrap.Modal(document.getElementById('calendarEventModal'));
    selectedDate = new Date();
    loadCalendarEvents();

    $('#prevMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    $('#nextMonth').on('click', function() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    $('#btnAddCalendarEvent').on('click', function() {
        openCalendarModal(formatDateKey(selectedDate));
    });

    $('#btnAddEventForDay').on('click', function() {
        openCalendarModal(formatDateKey(selectedDate));
    });

    $('#calendarEventForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var action = $('#event_id').val() ? 'update' : 'create';
        $.post(baseUrl + '/src/controllers/calendar.php?action=' + action, formData, function(response) {
            if (response.status === 'ok') {
                calendarModal.hide();
                loadCalendarEvents();
            } else {
                alert(response.message || 'Error al guardar evento');
            }
        }, 'json').fail(function() {
            alert('Error al guardar evento');
        });
    });
});
</script>
