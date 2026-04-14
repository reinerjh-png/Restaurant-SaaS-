<?php
/**
 * api/admin_productos.php — CRUD de productos del menú (con opciones)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../config/db.php';
requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, null, 'Método no permitido');

$input         = json_decode(file_get_contents('php://input'), true);
$accion        = $input['accion'] ?? '';
$restauranteId = $_SESSION['restaurante_id'];
$db            = getDB();

switch ($accion) {

    case 'crear':
    case 'editar':
        $id           = (int)($input['id']           ?? 0);
        $categoriaId  = (int)($input['categoria_id'] ?? 0);
        $nombre       = trim($input['nombre']         ?? '');
        $descripcion  = trim($input['descripcion']    ?? '');
        $precio       = floatval($input['precio']     ?? 0);
        $activo       = (int)($input['activo']        ?? 1);
        $tieneOpc     = (int)($input['tiene_opciones']?? 0);
        $grupos       = $input['grupos']              ?? [];

        if (empty($nombre) || !$categoriaId || $precio <= 0) {
            jsonResponse(false, null, 'Nombre, categoría y precio son requeridos');
        }

        // Verificar que la categoría pertenece al restaurante
        $stCat = $db->prepare("SELECT id FROM categorias WHERE id = ? AND restaurante_id = ?");
        $stCat->execute([$categoriaId, $restauranteId]);
        if (!$stCat->fetch()) jsonResponse(false, null, 'Categoría no válida');

        try {
            $db->beginTransaction();

            if ($accion === 'crear') {
                $st = $db->prepare("
                    INSERT INTO productos (restaurante_id, categoria_id, nombre, descripcion, precio, tiene_opciones, activo)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $st->execute([$restauranteId, $categoriaId, $nombre, $descripcion ?: null, $precio, $tieneOpc, $activo]);
                $productoId = $db->lastInsertId();
                $msg = "Producto \"$nombre\" creado correctamente";
            } else {
                if (!$id) { $db->rollBack(); jsonResponse(false, null, 'ID requerido para editar'); }
                $st = $db->prepare("
                    UPDATE productos
                    SET categoria_id=?, nombre=?, descripcion=?, precio=?, tiene_opciones=?, activo=?
                    WHERE id=? AND restaurante_id=?
                ");
                $st->execute([$categoriaId, $nombre, $descripcion ?: null, $precio, $tieneOpc, $activo, $id, $restauranteId]);
                $productoId = $id;
                // Borrar grupos/valores anteriores para reescribirlos
                $stDelVal = $db->prepare("DELETE ov FROM opciones_valor ov JOIN opciones_grupo og ON og.id = ov.grupo_id WHERE og.producto_id = ?");
                $stDelVal->execute([$productoId]);
                $stDelGrp = $db->prepare("DELETE FROM opciones_grupo WHERE producto_id = ?");
                $stDelGrp->execute([$productoId]);
                $msg = "Producto \"$nombre\" actualizado correctamente";
            }

            // Insertar nuevos grupos y valores si tiene opciones
            if ($tieneOpc && !empty($grupos)) {
                $orden = 1;
                foreach ($grupos as $grupo) {
                    $nombreGrupo = trim($grupo['nombre'] ?? '');
                    $valores     = $grupo['valores']    ?? [];
                    if (empty($nombreGrupo)) continue;

                    $stGrp = $db->prepare("INSERT INTO opciones_grupo (producto_id, nombre, orden, requerido) VALUES (?,?,?,1)");
                    $stGrp->execute([$productoId, $nombreGrupo, $orden]);
                    $grupoId = $db->lastInsertId();

                    foreach ($valores as $val) {
                        $val = trim($val);
                        if (empty($val)) continue;
                        $stVal = $db->prepare("INSERT INTO opciones_valor (grupo_id, valor) VALUES (?,?)");
                        $stVal->execute([$grupoId, $val]);
                    }
                    $orden++;
                }
            }

            $db->commit();
            jsonResponse(true, ['id' => $productoId], $msg);

        } catch (PDOException $e) {
            $db->rollBack();
            jsonResponse(false, null, 'Error al guardar: ' . $e->getMessage());
        }
        break;

    case 'eliminar':
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(false, null, 'ID requerido');

        // Verificar que no tenga pedidos activos asociados
        $stPed = $db->prepare("
            SELECT COUNT(*) FROM pedido_items pi
            JOIN pedidos pe ON pe.id = pi.pedido_id
            WHERE pi.producto_id = ? AND pe.estado = 'activo'
        ");
        $stPed->execute([$id]);
        if ($stPed->fetchColumn() > 0) {
            jsonResponse(false, null, 'No se puede eliminar: el producto está en un pedido activo');
        }

        // Eliminar opciones associadas primero
        $stDelVal = $db->prepare("DELETE ov FROM opciones_valor ov JOIN opciones_grupo og ON og.id = ov.grupo_id WHERE og.producto_id = ?");
        $stDelVal->execute([$id]);
        $stDelGrp = $db->prepare("DELETE FROM opciones_grupo WHERE producto_id = ?");
        $stDelGrp->execute([$id]);

        $st = $db->prepare("DELETE FROM productos WHERE id=? AND restaurante_id=?");
        $st->execute([$id, $restauranteId]);
        jsonResponse(true, null, 'Producto eliminado correctamente');

    default:
        jsonResponse(false, null, 'Acción no reconocida');
}
