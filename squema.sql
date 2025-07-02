-- Esquema de base de datos para sistema de agendamiento de citas

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de citas (actualizada para incluir user_id)
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    all_day TINYINT(1) NOT NULL DEFAULT 0,
    color VARCHAR(10) DEFAULT '#43a047',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Configuración global del calendario
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    week_start_day TINYINT NOT NULL DEFAULT 1, -- 0=Domingo, 1=Lunes, etc.
    work_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5', -- Días hábiles (1=Lunes, 7=Domingo)
    work_start TIME NOT NULL DEFAULT '08:00:00',
    work_end TIME NOT NULL DEFAULT '18:00:00',
    hour_format VARCHAR(5) NOT NULL DEFAULT '12', -- '12' o '24'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
