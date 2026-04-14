<?php
/**
 * api/superadmin_restaurantes.php — CRUD de restaurantes (solo superadmin)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input  = json_decode(file_get_contents('php://input'), true);
$accion = $input['accion'] ?? '';
$db     = getDB();

switch ($accion) {

    case 'crear':
        $nombre = trim($input['nombre'] ?? '');
        $activo = (int)($input['activo'] ?? 1);
        if (empty($nombre)) jsonResponse(false, null, 'El nombre es requerido');

        $st = $db->prepare("INSERT INTO restaurantes (nombre, activo) VALUES (?,?)");
        $st->execute([$nombre, $activo]);
        jsonResponse(true, ['id' => $db->lastInsertId()], "Restaurante \"$nombre\" creado correctamente");

    case 'editar':
        $id     = (int)($input['id']     ?? 0);
        $nombre = trim($input['nombre']  ?? '');
        $activo = (int)($input['activo'] ?? 1);
        if (!$id || empty($nombre)) jsonResponse(false, null, 'Datos inválidos');

        $st = $db->prepare("UPDATE restaurantes SET nombre=?, activo=? WHERE id=?");
        $st->execute([$nombre, $activo, $id]);
        jsonResponse(true, null, "Restaurante actualizado correctamente");

    case 'toggle':
        $id        = (int)($input['id']          ?? 0);
        $nuevoActivo= (int)($input['nuevo_activo']?? 0);
        if (!$id) jsonResponse(false, null, 'ID requerido');

        $st = $db->prepare("UPDATE restaurantes SET activo=? WHERE id=?");
        $st->execute([$nuevoActivo, $id]);
        $msg = $nuevoActivo ? 'Restaurante activado' : 'Restaurante desactivado';
        jsonResponse(true, null, $msg);

    case 'crear_admin':
        $restId   = (int)($input['restaurante_id'] ?? 0);
        $nombre   = trim($input['nombre']   ?? '');
        $email    = trim($input['email']    ?? '');
        $password = trim($input['password'] ?? '');

        if (!$restId || empty($nombre) || empty($email) || strlen($password) < 6) {
            jsonResponse(false, null, 'Datos incompletos o contraseña muy corta');
        }

        // Verificar restaurante existe
        $stRest = $db->prepare("SELECT id FROM restaurantes WHERE id = ?");
        $stRest->execute([$restId]);
        if (!$stRest->fetch()) jsonResponse(false, null, 'Restaurante no encontrado');

        // Email único
        $stDup = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stDup->execute([$email]);
        if ($stDup->fetch()) jsonResponse(false, null, 'El correo ya está registrado');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st   = $db->prepare("INSERT INTO usuarios (restaurante_id, nombre, email, password, rol, activo) VALUES (?,?,?,?,'admin',1)");
        $st->execute([$restId, $nombre, $email, $hash]);
        jsonResponse(true, ['id' => $db->lastInsertId()], "Administrador \"$nombre\" creado correctamente");

    default:
        jsonResponse(false, null, 'Acción no reconocida');
}
