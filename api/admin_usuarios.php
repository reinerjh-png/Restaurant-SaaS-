<?php
/**
 * api/admin_usuarios.php — CRUD de usuarios del restaurante
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$accion        = $input['accion'] ?? '';
$restauranteId = $_SESSION['restaurante_id'];
$usuarioActual = $_SESSION['usuario_id'];
$rolActual     = $_SESSION['rol'];
$db            = getDB();

switch ($accion) {

    case 'crear':
        $nombre   = trim($input['nombre']   ?? '');
        $email    = trim($input['email']    ?? '');
        $rol      = trim($input['rol']      ?? '');
        $password = trim($input['password'] ?? '');
        $activo   = !empty($input['activo']) ? 1 : 0;

        $rolesPermitidos = ($rolActual === 'superadmin')
            ? ['admin','atencion','cocina']
            : ['atencion','cocina'];

        if (empty($nombre) || empty($email) || !in_array($rol, $rolesPermitidos)) {
            jsonResponse(false, null, 'Datos inválidos o rol no permitido');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, null, 'Correo electrónico inválido');
        if (mb_strlen($nombre) > 100) jsonResponse(false, null, 'El nombre es demasiado largo (máx 100 caracteres)');
        if (strlen($password) < 6) jsonResponse(false, null, 'La contraseña debe tener al menos 6 caracteres');

        // Verificar email único
        $stDup = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stDup->execute([$email]);
        if ($stDup->fetch()) jsonResponse(false, null, 'El correo ya está registrado');

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $st   = $db->prepare("INSERT INTO usuarios (restaurante_id, nombre, email, password, rol, activo) VALUES (?,?,?,?,?,?)");
        $st->execute([$restauranteId, $nombre, $email, $hash, $rol, $activo]);
        jsonResponse(true, ['id' => $db->lastInsertId()], "Usuario \"$nombre\" creado correctamente");

    case 'editar':
        $id       = (int)($input['id']      ?? 0);
        $nombre   = trim($input['nombre']   ?? '');
        $email    = trim($input['email']    ?? '');
        $rol      = trim($input['rol']      ?? '');
        $password = trim($input['password'] ?? '');
        $activo   = !empty($input['activo']) ? 1 : 0;

        if (!$id || empty($nombre) || empty($email)) jsonResponse(false, null, 'Datos incompletos');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(false, null, 'Correo electrónico inválido');
        if (mb_strlen($nombre) > 100) jsonResponse(false, null, 'El nombre es demasiado largo (máx 100 caracteres)');

        // Mismos roles permitidos que en 'crear' — previene escalada de privilegios
        $rolesPermitidos = ($rolActual === 'superadmin')
            ? ['admin', 'atencion', 'cocina']
            : ['atencion', 'cocina'];

        if (!in_array($rol, $rolesPermitidos)) {
            jsonResponse(false, null, 'Rol no permitido');
        }

        // No puede desactivarse a sí mismo
        if ($id === $usuarioActual && !$activo) jsonResponse(false, null, 'No puedes desactivar tu propia cuenta');

        // No se permite editar un superadmin desde este endpoint
        $stChkRol = $db->prepare("SELECT rol FROM usuarios WHERE id = ? AND restaurante_id = ?");
        $stChkRol->execute([$id, $restauranteId]);
        $usuarioTarget = $stChkRol->fetch();
        if (!$usuarioTarget) jsonResponse(false, null, 'Usuario no encontrado');
        if ($usuarioTarget['rol'] === 'superadmin') jsonResponse(false, null, 'No se puede editar una cuenta superadmin desde este panel');

        // Verificar email único excluyendo el propio
        $stDup = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stDup->execute([$email, $id]);
        if ($stDup->fetch()) jsonResponse(false, null, 'El correo ya está en uso por otro usuario');

        if (!empty($password)) {
            if (strlen($password) < 6) jsonResponse(false, null, 'La contraseña debe tener al menos 6 caracteres');
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $st   = $db->prepare("UPDATE usuarios SET nombre=?, email=?, password=?, rol=?, activo=? WHERE id=? AND restaurante_id=?");
            $st->execute([$nombre, $email, $hash, $rol, $activo, $id, $restauranteId]);
        } else {
            $st = $db->prepare("UPDATE usuarios SET nombre=?, email=?, rol=?, activo=? WHERE id=? AND restaurante_id=?");
            $st->execute([$nombre, $email, $rol, $activo, $id, $restauranteId]);
        }
        jsonResponse(true, null, "Usuario \"$nombre\" actualizado correctamente");

    case 'eliminar':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'ID requerido');
        if ($id === $usuarioActual) jsonResponse(false, null, 'No puedes eliminarte a ti mismo');

        $st = $db->prepare("DELETE FROM usuarios WHERE id=? AND restaurante_id=? AND rol != 'superadmin'");
        $st->execute([$id, $restauranteId]);
        jsonResponse(true, null, 'Usuario eliminado correctamente');

    default:
        jsonResponse(false, null, 'Acción no reconocida');
}
