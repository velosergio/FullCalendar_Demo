# Sistema de Calendario - Instrucciones de Configuración

## Requisitos Previos
- XAMPP instalado y funcionando
- Apache y MySQL activos
- Navegador web moderno

## Pasos de Configuración

### 1. Configurar la Base de Datos
1. Abre phpMyAdmin (http://localhost/phpmyadmin)
2. Crea una nueva base de datos llamada `calendar`
3. Importa el archivo `squema.sql` en la base de datos `calendar`

### 2. Verificar la Configuración
1. Abre http://localhost:8000/test.php para verificar que PHP funciona
2. Abre http://localhost:8000/test_db.php para verificar la conexión a la base de datos

### 3. Acceder a la Aplicación
1. Abre http://localhost:8000/login.php en tu navegador
2. Regístrate con tu email y contraseña
3. Inicia sesión para acceder al calendario
4. El calendario será privado para tu usuario

## Estructura del Proyecto
- `index.php` - Página principal con el calendario (requiere autenticación)
- `login.php` - Página de inicio de sesión
- `register.php` - Página de registro de usuarios
- `forgot.php` - Página de recuperación de contraseña
- `logout.php` - Cerrar sesión
- `auth.php` - Funciones de autenticación y autorización
- `app.js` - Lógica JavaScript del frontend
- `appointments.php` - API backend para gestionar citas
- `config/config.php` - Configuración de la base de datos
- `style.css` - Estilos CSS
- `squema.sql` - Esquema de la base de datos

## Funcionalidades
- ✅ Sistema de autenticación completo (login, registro, logout)
- ✅ Recuperación de contraseña (simulada)
- ✅ Calendario privado por usuario
- ✅ Ver calendario en diferentes vistas (mes, semana, día)
- ✅ Crear nuevas citas
- ✅ Editar citas existentes
- ✅ Ver próximas citas en panel lateral
- ✅ Interfaz en español
- ✅ Responsive design

## Solución de Problemas

### Error 404 en appointments.php
- Verifica que estés ejecutando desde el puerto correcto (8000)
- Asegúrate de que todos los archivos estén en la misma carpeta

### Error de conexión a la base de datos
- Verifica que MySQL esté activo en XAMPP
- Confirma que la base de datos `calendar` existe
- Revisa la configuración en `config/config.php`

### El calendario no carga eventos
- Ejecuta `test_db.php` para verificar la conexión
- Asegúrate de que la tabla `appointments` existe
- Verifica que no haya errores en la consola del navegador 