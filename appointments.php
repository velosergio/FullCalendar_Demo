<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];

function response($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'appointment' => $data,
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// --- 1. PRIMERO: Manejar configuración global ---
if ($method === 'POST' && (isset($_GET['action']) && $_GET['action'] === 'settings')) {
    $data = json_decode(file_get_contents('php://input'), true);
    $week_start_day = isset($data['week_start_day']) ? intval($data['week_start_day']) : 1;
    $work_days = isset($data['work_days']) ? $data['work_days'] : '1,2,3,4,5';
    $work_start = isset($data['work_start']) ? $data['work_start'] : '08:00:00';
    $work_end = isset($data['work_end']) ? $data['work_end'] : '18:00:00';
    $hour_format = isset($data['hour_format']) && in_array($data['hour_format'], ['12','24']) ? $data['hour_format'] : '24';
    // Si ya existe, actualiza; si no, inserta
    $sql = "SELECT id FROM settings ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $id = $row['id'];
        $sql = "UPDATE settings SET week_start_day=?, work_days=?, work_start=?, work_end=?, hour_format=?, updated_at=NOW() WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issssi', $week_start_day, $work_days, $work_start, $work_end, $hour_format, $id);
        $ok = mysqli_stmt_execute($stmt);
    } else {
        $sql = "INSERT INTO settings (week_start_day, work_days, work_start, work_end, hour_format) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'issss', $week_start_day, $work_days, $work_start, $work_end, $hour_format);
        $ok = mysqli_stmt_execute($stmt);
    }
    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar configuración']);
    }
    exit;
}

// --- 2. Luego: Resto de la lógica (citas) ---

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'users') {
        $sql = "SELECT id, email FROM users ORDER BY email ASC";
        $result = mysqli_query($conn, $sql);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    } elseif ($action === 'list') {
        // Listar todos los eventos para FullCalendar (sin importar usuario)
        $sql = "SELECT * FROM appointments ORDER BY start_time ASC";
        $result = mysqli_query($conn, $sql);
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'start' => $row['start_time'],
                'end' => $row['end_time'],
                'allDay' => $row['all_day'] == 1,
                'description' => $row['description'],
                'color' => $row['color'] ?? '#43a047',
                'user_id' => $row['user_id']
            ];
        }
        echo json_encode($events);
        exit;
    } elseif ($action === 'upcoming') {
        // Listar próximas citas (solo del usuario actual)
        $sql = "SELECT * FROM appointments WHERE user_id = ? AND start_time >= NOW() ORDER BY start_time ASC LIMIT 10";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $appts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $appts[] = $row;
        }
        echo json_encode(['success' => true, 'appointments' => $appts]);
        exit;
    } elseif ($action === 'settings') {
        // Obtener configuración global
        $sql = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $settings = mysqli_fetch_assoc($result);
        if ($settings) {
            echo json_encode(['success' => true, 'settings' => $settings]);
        } else {
            // Valores por defecto si no hay configuración
            echo json_encode(['success' => true, 'settings' => [
                'week_start_day' => 1,
                'work_days' => '1,2,3,4,5',
                'work_start' => '08:00:00',
                'work_end' => '18:00:00',
                'hour_format' => '12',
            ]]);
        }
        exit;
    } elseif (isset($_GET['id'])) {
        // Obtener una cita específica (solo del usuario actual)
        $id = intval($_GET['id']);
        $sql = "SELECT * FROM appointments WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $appt = mysqli_fetch_assoc($result);
        if ($appt) {
            response(true, 'Cita encontrada', $appt);
        } else {
            response(false, 'Cita no encontrada', null, 404);
        }
    } else {
        // Listar todas las citas (solo del usuario actual)
        $sql = "SELECT * FROM appointments WHERE user_id = ? ORDER BY start_time ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $appts = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $appts[] = $row;
        }
        echo json_encode(['success' => true, 'appointments' => $appts]);
        exit;
    }
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
        response(false, 'Faltan datos obligatorios', null, 400);
    }
    $color = isset($data['color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color']) ? $data['color'] : '#43a047';
    $assigned_user_id = isset($data['user_id']) && intval($data['user_id']) > 0 ? intval($data['user_id']) : $user_id;
    $sql = "INSERT INTO appointments (user_id, title, description, start_time, end_time, all_day, color) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $desc = $data['description'] ?? '';
    $all_day = !empty($data['all_day']) ? 1 : 0;
    mysqli_stmt_bind_param($stmt, 'issssss', $assigned_user_id, $data['title'], $desc, $data['start_time'], $data['end_time'], $all_day, $color);
    if (mysqli_stmt_execute($stmt)) {
        $id = mysqli_insert_id($conn);
        $sql = "SELECT * FROM appointments WHERE id = ?";
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, 'i', $id);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);
        $appt = mysqli_fetch_assoc($result);
        response(true, 'Cita creada', $appt, 201);
    } else {
        response(false, 'Error al crear cita', null, 500);
    }
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['id']) || empty($data['title']) || empty($data['start_time']) || empty($data['end_time'])) {
        response(false, 'Faltan datos obligatorios', null, 400);
    }
    $color = isset($data['color']) && preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color']) ? $data['color'] : '#43a047';
    $assigned_user_id = isset($data['user_id']) && intval($data['user_id']) > 0 ? intval($data['user_id']) : $user_id;
    $sql = "UPDATE appointments SET user_id=?, title=?, description=?, start_time=?, end_time=?, all_day=?, color=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    $desc = $data['description'] ?? '';
    $all_day = !empty($data['all_day']) ? 1 : 0;
    mysqli_stmt_bind_param($stmt, 'issssssi', $assigned_user_id, $data['title'], $desc, $data['start_time'], $data['end_time'], $all_day, $color, $data['id']);
    if (mysqli_stmt_execute($stmt)) {
        $sql = "SELECT * FROM appointments WHERE id = ?";
        $stmt2 = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt2, 'i', $data['id']);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);
        $appt = mysqli_fetch_assoc($result);
        response(true, 'Cita actualizada', $appt);
    } else {
        response(false, 'Error al actualizar cita', null, 500);
    }
}

if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    if (!$id) {
        response(false, 'ID no especificado', null, 400);
    }
    $sql = "DELETE FROM appointments WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        response(true, 'Cita eliminada');
    } else {
        response(false, 'Error al eliminar cita', null, 500);
    }
}

// Si llega aquí, método no soportado
response(false, 'Método no soportado', null, 405); 