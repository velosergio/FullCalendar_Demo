<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth.php';

// Verificar que el usuario esté logueado
requireLogin();

// Obtener información del usuario actual
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario General</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="header-bar">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div>
                <i class="bi bi-calendar-check"></i> Calendario
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 0.9em; opacity: 0.9;">
                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($currentUser['email']); ?>
                </span>
                <a href="logout.php" style="color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; background: rgba(255,255,255,0.2); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
    <div class="main-layout">
        <div class="calendar-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
                <button id="createAppointment" class="btn btn-success">
                    <i class="bi bi-plus-lg"></i> Nueva Cita
                </button>
                <button id="openSettingsBtn" class="btn btn-settings" type="button">
                    <i class="bi bi-gear"></i> Configuración
                </button>
            </div>
            <div id="calendar"></div>
        </div>
        <aside class="sidebar-panel">
            <div class="sidebar-title">
                <span>Próximas Citas</span>
            </div>
            <div id="upcomingAppointmentsList"></div>
        </aside>
    </div>

    <!-- Modal para crear/editar cita -->
    <div id="appointmentModal">
        <div class="modal-content appointment-modal-modern">
            <form id="appointmentForm">
                <div class="modal-header-modern">
                    <i class="bi bi-calendar-plus" style="color:#43a047;font-size:2em;"></i>
                    <div>
                        <h2 id="modalTitleText" style="color:#43a047;display:inline;font-weight:700;font-size:1.6em;margin-left:8px;">Crear Cita</h2>
                    </div>
                    <button type="button" class="close-modal-btn" id="closeModalBtn" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body-modern">
                    <input type="hidden" id="appt_id" name="id">
                    <label for="appt_title"><i class="bi bi-type"></i> Título:</label>
                    <input type="text" id="appt_title" name="title" required placeholder="Nombre de la cita">
                    <label for="appt_description"><i class="bi bi-text-left"></i> Descripción:</label>
                    <textarea id="appt_description" name="description" placeholder="Detalles adicionales"></textarea>
                    <label for="appt_user_id"><i class="bi bi-person"></i> Usuario Asignado:</label>
                    <select id="appt_user_id" name="user_id">
                        <option value="">-- Selecciona un usuario --</option>
                    </select>
                    <div id="appt_user_info" style="font-size:0.97em;color:#888;margin-bottom:10px;"></div>
                    <div class="row-color-allday">
                        <div class="color-col">
                            <label for="appt_color"><i class="bi bi-palette"></i> Color de la cita:</label>
                            <input type="color" id="appt_color" name="color" value="#43a047">
                        </div>
                        <div class="allday-col">
                            <input type="checkbox" id="appt_all_day" name="all_day">
                            <label for="appt_all_day"><i class="bi bi-calendar3"></i> Todo el día</label>
                        </div>
                    </div>
                    <div class="row-time-modern">
                        <div class="time-col">
                            <label for="appt_start"><i class="bi bi-clock"></i> Hora de Inicio:</label>
                            <input type="datetime-local" id="appt_start" name="start_time" required>
                        </div>
                        <div class="time-col">
                            <label for="appt_end"><i class="bi bi-clock"></i> Hora de Fin:</label>
                            <input type="datetime-local" id="appt_end" name="end_time" required>
                        </div>
                    </div>
                </div>
                <div class="modal-actions-modern">
                    <button type="button" class="btn btn-danger" id="deleteAppointmentBtn" style="display:none"><i class="bi bi-trash"></i> Eliminar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de configuración -->
    <div id="settingsModal">
        <div class="modal-content">
            <form id="settingsForm">
                <h3><i class="bi bi-gear"></i> Configuración del Calendario</h3>
                <label for="week_start_day">Día de inicio de la semana:</label>
                <select id="week_start_day" name="week_start_day">
                    <option value="0">Domingo</option>
                    <option value="1">Lunes</option>
                    <option value="2">Martes</option>
                    <option value="3">Miércoles</option>
                    <option value="4">Jueves</option>
                    <option value="5">Viernes</option>
                    <option value="6">Sábado</option>
                </select>
                <label for="work_days">Días hábiles:</label>
                <div id="work_days" style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                    <label><input type="checkbox" value="1"> Lunes</label>
                    <label><input type="checkbox" value="2"> Martes</label>
                    <label><input type="checkbox" value="3"> Miércoles</label>
                    <label><input type="checkbox" value="4"> Jueves</label>
                    <label><input type="checkbox" value="5"> Viernes</label>
                    <label><input type="checkbox" value="6"> Sábado</label>
                    <label><input type="checkbox" value="7"> Domingo</label>
                </div>
                <label for="hour_format">Formato de hora:</label>
                <select id="hour_format" name="hour_format">
                    <option value="24">24 horas</option>
                    <option value="12">12 horas (AM/PM)</option>
                </select>
                <div class="row-time">
                    <div style="flex:1;">
                        <label for="work_start">Hora de inicio de atención:</label>
                        <input type="time" id="work_start" name="work_start" required>
                    </div>
                    <div style="flex:1;">
                        <label for="work_end">Hora de fin de atención:</label>
                        <input type="time" id="work_end" name="work_end" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn" id="closeSettingsBtn">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check2"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
    <script src="app.js"></script>
</body>
</html>
