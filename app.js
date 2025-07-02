// Lógica JS para el sistema de calendario general autónomo

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar FullCalendar
    let calendar;
    let calendarEl = document.getElementById('calendar');

    function initCalendar(settings) {
        if (calendar) calendar.destroy();
        // Días hábiles: convertir a array de números (FullCalendar: 0=Dom, 1=Lun...)
        let workDays = (settings.work_days || '1,2,3,4,5').split(',').map(Number);
        // Ajustar a formato de FullCalendar (0=Domingo)
        workDays = workDays.map(d => d % 7);
        // Definir formato de hora para eventos y columna de horas
        let hourFormat = (settings.hour_format === '12') ? { hour: 'numeric', minute: '2-digit', hour12: true } : { hour: '2-digit', minute: '2-digit', hour12: false };
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'es',
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Lista'
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: 'appointments.php?action=list',
            editable: true,
            firstDay: parseInt(settings.week_start_day) || 1,
            hiddenDays: [0,1,2,3,4,5,6].filter(d => !workDays.includes(d)),
            slotMinTime: settings.work_start || '08:00:00',
            slotMaxTime: settings.work_end || '18:00:00',
            eventDrop: calendarEventDrop,
            eventResize: calendarEventResize,
            dateClick: calendarDateClick,
            eventClick: calendarEventClick,
            eventTimeFormat: hourFormat,
            slotLabelFormat: hourFormat,
            nowIndicator: true
        });
        calendar.render();
    }

    function calendarEventDrop(info) {
        // Cuando se arrastra y suelta un evento
        const event = info.event;
        function toLocalDateTimeString(date) {
            // Convierte a YYYY-MM-DD HH:MM:SS en zona local
            const pad = n => n < 10 ? '0' + n : n;
            return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
        }
        const data = {
            id: event.id,
            title: event.title,
            description: event.extendedProps.description || '',
            start_time: toLocalDateTimeString(event.start),
            end_time: event.end ? toLocalDateTimeString(event.end) : toLocalDateTimeString(event.start),
            all_day: event.allDay ? 1 : 0,
            color: event.backgroundColor || event.color || '#43a047'
        };
        // Mostrar indicador de carga
        event.setProp('title', event.title + ' (Guardando...)');
        fetch('appointments.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                event.setProp('title', data.title);
                loadUpcomingAppointments();
                showNotification('Cita actualizada correctamente', 'success');
            } else {
                info.revert();
                showNotification('Error al actualizar: ' + resp.message, 'error');
            }
        })
        .catch(error => {
            info.revert();
            showNotification('Error de conexión al actualizar la cita', 'error');
        });
    }

    function calendarEventResize(info) {
        // Cuando se redimensiona un evento (cambia la duración)
        const event = info.event;
        function toLocalDateTimeString(date) {
            const pad = n => n < 10 ? '0' + n : n;
            return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes()) + ':' + pad(date.getSeconds());
        }
        const data = {
            id: event.id,
            title: event.title,
            description: event.extendedProps.description || '',
            start_time: toLocalDateTimeString(event.start),
            end_time: toLocalDateTimeString(event.end),
            all_day: event.allDay ? 1 : 0,
            color: event.backgroundColor || event.color || '#43a047'
        };
        // Mostrar indicador de carga
        event.setProp('title', event.title + ' (Guardando...)');
        fetch('appointments.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                event.setProp('title', data.title);
                loadUpcomingAppointments();
                showNotification('Duración de cita actualizada', 'success');
            } else {
                info.revert();
                showNotification('Error al actualizar: ' + resp.message, 'error');
            }
        })
        .catch(error => {
            info.revert();
            showNotification('Error de conexión al actualizar la cita', 'error');
        });
    }

    function calendarDateClick(info) {
        // Obtener la fecha y hora seleccionada (local)
        const startDate = new Date(info.date);
        const endDate = new Date(startDate.getTime() + 30 * 60000); // +30 minutos
        function toInputFormatLocal(date) {
            const pad = n => n < 10 ? '0' + n : n;
            return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        }
        openAppointmentModal({
            start: toInputFormatLocal(startDate),
            end: toInputFormatLocal(endDate),
            allDay: info.allDay
        });
    }

    function calendarEventClick(info) {
        fetch('appointments.php?action=get&id=' + info.event.id)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    openAppointmentModal(data.appointment, true);
                }
            });
    }

    // Botón para nueva cita
    document.getElementById('createAppointment').onclick = function() {
        openAppointmentModal();
    };

    // Modal de cita
    function openAppointmentModal(appointment = {}, isEdit = false) {
        const modal = document.getElementById('appointmentModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Deshabilitar scroll fondo
        document.getElementById('modalTitleText').innerText = isEdit ? 'Editar cita' : 'Crear Cita';
        document.getElementById('appt_id').value = appointment.id || '';
        document.getElementById('appt_title').value = appointment.title || '';
        document.getElementById('appt_description').value = appointment.description || '';
        // Color: asegurar que sea un color hexadecimal válido
        function isValidHexColor(c) {
            return typeof c === 'string' && /^#[0-9A-Fa-f]{6}$/.test(c);
        }
        let color = appointment.color;
        if (!isValidHexColor(color)) color = '#43a047';
        document.getElementById('appt_color').value = color;
        document.getElementById('appt_all_day').checked = appointment.all_day == 1;
        // Fechas
        let start = appointment.start_time || appointment.start || '';
        let end = appointment.end_time || appointment.end || '';
        function toInputFormatLocal(date) {
            // Formato local para input datetime-local
            const pad = n => n < 10 ? '0' + n : n;
            return date.getFullYear() + '-' + pad(date.getMonth()+1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
        }
        if (start) {
            let startDate = typeof start === 'string' ? new Date(start.replace(' ', 'T')) : start;
            if (!isNaN(startDate.getTime())) {
                start = toInputFormatLocal(startDate);
            }
        }
        if (end) {
            let endDate = typeof end === 'string' ? new Date(end.replace(' ', 'T')) : end;
            if (!isNaN(endDate.getTime())) {
                end = toInputFormatLocal(endDate);
            }
        }
        // Si la hora final es antes de la de inicio, poner +30 minutos
        if (start && end && end < start) {
            let startDate = new Date(start);
            let endDate = new Date(startDate.getTime() + 30 * 60000);
            end = toInputFormatLocal(endDate);
        }
        document.getElementById('appt_start').value = start || '';
        document.getElementById('appt_end').value = end || '';
        // Mostrar botón eliminar solo si es edición
        document.getElementById('deleteAppointmentBtn').style.display = isEdit ? 'inline-block' : 'none';
        // Asignar función de eliminar
        document.getElementById('deleteAppointmentBtn').onclick = function() {
            if (confirm('¿Seguro que deseas eliminar esta cita?')) {
                fetch('appointments.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: appointment.id })
                })
                .then(res => res.json())
                .then(resp => {
                    if (resp.success) {
                        closeModal();
                        calendar.refetchEvents();
                        loadUpcomingAppointments();
                        showNotification('Cita eliminada correctamente', 'success');
                    } else {
                        showNotification('Error al eliminar: ' + resp.message, 'error');
                    }
                });
            }
        };
        // Cargar usuarios y asignar el usuario seleccionado
        const userSelect = document.getElementById('appt_user_id');
        userSelect.innerHTML = '<option value="">-- Selecciona un usuario --</option>';
        fetch('appointments.php?action=users')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    data.users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.email;
                        userSelect.appendChild(opt);
                    });
                    // Seleccionar el usuario asignado si existe
                    if (appointment.user_id) {
                        userSelect.value = appointment.user_id;
                        document.getElementById('appt_user_info').textContent = 'Usuario asignado: ' + (userSelect.selectedOptions[0]?.textContent || 'Sin usuario seleccionado');
                    } else {
                        userSelect.value = '';
                        document.getElementById('appt_user_info').textContent = 'Sin usuario seleccionado';
                    }
                }
            });
        userSelect.onchange = function() {
            document.getElementById('appt_user_info').textContent = userSelect.value ? 'Usuario asignado: ' + userSelect.selectedOptions[0].textContent : 'Sin usuario seleccionado';
        };
    }

    // Función para cerrar el modal
    function closeModal() {
        document.getElementById('appointmentModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Cerrar modal con botón
    document.getElementById('closeModalBtn').onclick = closeModal;

    // Cerrar modal con tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('appointmentModal');
            if (modal.style.display === 'flex') {
                closeModal();
            }
        }
    });

    // Cerrar modal haciendo clic fuera del contenido
    document.getElementById('appointmentModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });

    // Función para mostrar notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Agregar estilos básicos si no existen
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    border-radius: 4px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    animation: slideIn 0.3s ease-out;
                    max-width: 300px;
                }
                .notification-success { background-color: #28a745; }
                .notification-error { background-color: #dc3545; }
                .notification-info { background-color: #17a2b8; }
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    document.getElementById('appointmentForm').onsubmit = function(e) {
        e.preventDefault();
        const data = {
            id: document.getElementById('appt_id').value,
            title: document.getElementById('appt_title').value,
            description: document.getElementById('appt_description').value,
            start_time: document.getElementById('appt_start').value.replace('T', ' '),
            end_time: document.getElementById('appt_end').value.replace('T', ' '),
            all_day: document.getElementById('appt_all_day').checked ? 1 : 0,
            color: document.getElementById('appt_color').value || '#43a047',
            user_id: document.getElementById('appt_user_id').value || null
        };
        const method = data.id ? 'PUT' : 'POST';
        fetch('appointments.php', {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                closeModal();
                calendar.refetchEvents();
                loadUpcomingAppointments();
                showNotification(data.id ? 'Cita actualizada correctamente' : 'Cita creada correctamente', 'success');
            } else {
                showNotification('Error: ' + resp.message, 'error');
            }
        });
    };

    // Cargar próximas citas
    function loadUpcomingAppointments() {
        fetch('appointments.php?action=upcoming')
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById('upcomingAppointmentsList');
                list.innerHTML = '';
                if (data.success && data.appointments.length) {
                    data.appointments.forEach(appt => {
                        const start = formatDateTime(appt.start_time);
                        const end = formatDateTime(appt.end_time);
                        let html = `<div class='upcoming-appointment' style='border-left: 4px solid ${appt.color || '#43a047'}; cursor:pointer;' data-id='${appt.id}'>`;
                        html += `<div class='appt-title'>${escapeHtml(appt.title)}</div>`;
                        html += `<div class='appt-time'>${start} - ${end}</div>`;
                        if (appt.description) {
                            html += `<div class='appt-desc'>${escapeHtml(appt.description)}</div>`;
                        }
                        html += `</div>`;
                        list.innerHTML += html;
                    });
                    // Hacer clic en cita lateral
                    Array.from(document.querySelectorAll('.upcoming-appointment')).forEach(el => {
                        el.onclick = function() {
                            const id = this.getAttribute('data-id');
                            fetch('appointments.php?action=get&id=' + id)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        openAppointmentModal(data.appointment, true);
                                    }
                                });
                        };
                    });
                } else {
                    list.innerHTML = '<div class="upcoming-appointment">No hay próximas citas.</div>';
                }
            });
    }
    loadUpcomingAppointments();

    // Formatea fecha y hora en formato amigable
    const hourFormatSelect = document.getElementById('hour_format');
    function formatDateTime(dt) {
        if (!dt) return '';
        const d = new Date(dt.replace(' ', 'T'));
        const opts = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: (window._hourFormat === '12') };
        let str = d.toLocaleString('es-CO', opts).replace(',', '');
        if(window._hourFormat === '12') str = str.replace('a. m.', 'AM').replace('p. m.', 'PM');
        return str;
    }
    // Escapa HTML para evitar XSS
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c];
        });
    }

    // --- CONFIGURACIÓN ---
    const settingsModal = document.getElementById('settingsModal');
    const openSettingsBtn = document.getElementById('openSettingsBtn');
    const closeSettingsBtn = document.getElementById('closeSettingsBtn');
    const settingsForm = document.getElementById('settingsForm');
    const weekStartDay = document.getElementById('week_start_day');
    const workStart = document.getElementById('work_start');
    const workEnd = document.getElementById('work_end');
    const workDaysDiv = document.getElementById('work_days');

    openSettingsBtn.onclick = function() {
        settingsModal.style.display = 'flex';
        loadSettings();
    };
    closeSettingsBtn.onclick = function() {
        settingsModal.style.display = 'none';
    };
    settingsModal.addEventListener('click', function(e) {
        if (e.target === this) settingsModal.style.display = 'none';
    });

    function loadSettings() {
        fetch('appointments.php?action=settings')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const s = data.settings;
                    weekStartDay.value = s.week_start_day;
                    workStart.value = s.work_start.slice(0,5);
                    workEnd.value = s.work_end.slice(0,5);
                    hourFormatSelect.value = s.hour_format || '24';
                    window._hourFormat = s.hour_format || '24';
                    // Días hábiles
                    const days = (s.work_days || '1,2,3,4,5').split(',');
                    Array.from(workDaysDiv.querySelectorAll('input[type=checkbox]')).forEach(cb => {
                        cb.checked = days.includes(cb.value);
                    });
                }
            });
    }

    settingsForm.onsubmit = function(e) {
        e.preventDefault();
        const days = Array.from(workDaysDiv.querySelectorAll('input[type=checkbox]'))
            .filter(cb => cb.checked).map(cb => cb.value).join(',');
        function toTimeString(val) {
            if (!val) return '00:00:00';
            return val.length === 5 ? val + ':00' : val;
        }
        const data = {
            week_start_day: weekStartDay.value,
            work_days: days,
            work_start: toTimeString(workStart.value),
            work_end: toTimeString(workEnd.value),
            hour_format: hourFormatSelect.value
        };
        window._hourFormat = hourFormatSelect.value;
        fetch('appointments.php?action=settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                settingsModal.style.display = 'none';
                showNotification('Configuración guardada', 'success');
                initCalendar(data); // Recargar calendario con nueva config
            } else {
                showNotification('Error: ' + resp.message, 'error');
            }
        });
    };

    // Al cargar la página, obtener configuración y crear calendario
    fetch('appointments.php?action=settings')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window._hourFormat = data.settings.hour_format || '24';
                initCalendar(data.settings);
            }
        });

    // Por defecto, formato 12 horas
    window._hourFormat = window._hourFormat || '12';

    // Cambiar textos de botones y mensajes a español
    const btns = document.querySelectorAll('.btn, .btn-success, .btn-danger, .btn-settings');
    btns.forEach(btn => {
        if(btn.innerText.includes('New') || btn.innerText.includes('Nueva Cita')) btn.innerText = '+ Nueva Cita';
        if(btn.innerText.includes('Config')) btn.innerText = 'Configuración';
        if(btn.innerText.includes('Cancel')) btn.innerText = 'Cancelar';
        if(btn.innerText.includes('Save') || btn.innerText.includes('Guardar')) btn.innerText = 'Guardar';
        if(btn.innerText.includes('Delete') || btn.innerText.includes('Eliminar')) btn.innerText = 'Eliminar';
    });
});
