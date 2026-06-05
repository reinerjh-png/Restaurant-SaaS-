<?php
/**
 * roles/admin/gastos.php — Gestión completa de gastos (Admin)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$usuarioId     = $_SESSION['usuario_id'];
$db = getDB();

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin    = $_GET['fecha_fin']    ?? date('Y-m-d');
$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion      = $_POST['accion'];
    $gastoId     = intval($_POST['id'] ?? 0);
    $categoria   = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto       = floatval($_POST['monto'] ?? 0);
    $fecha       = $_POST['fecha'] ?? date('Y-m-d');

    if ($accion === 'registrar' && $categoria && $descripcion && $monto > 0 && $fecha) {
        $st = $db->prepare("INSERT INTO gastos (restaurante_id, usuario_id, categoria, descripcion, monto, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        if ($st->execute([$restauranteId, $usuarioId, $categoria, $descripcion, $monto, $fecha])) {
            $mensaje = "<div class='toast exito'><i class='fa-solid fa-circle-check'></i> Gasto registrado.</div>";
        }
    } elseif ($accion === 'editar' && $gastoId > 0 && $categoria && $descripcion && $monto > 0 && $fecha) {
        $st = $db->prepare("UPDATE gastos SET categoria=?, descripcion=?, monto=?, fecha=? WHERE id=? AND restaurante_id=?");
        if ($st->execute([$categoria, $descripcion, $monto, $fecha, $gastoId, $restauranteId])) {
            $mensaje = "<div class='toast exito'><i class='fa-solid fa-circle-check'></i> Gasto actualizado.</div>";
        }
    } elseif ($accion === 'eliminar' && $gastoId > 0) {
        $st = $db->prepare("DELETE FROM gastos WHERE id=? AND restaurante_id=?");
        if ($st->execute([$gastoId, $restauranteId])) {
            $mensaje = "<div class='toast exito'><i class='fa-solid fa-circle-check'></i> Gasto eliminado.</div>";
        }
    } else {
        $mensaje = "<div class='toast advertencia'><i class='fa-solid fa-triangle-exclamation'></i> Datos inválidos.</div>";
    }
}

// Obtener gastos del periodo
$stGastos = $db->prepare("
    SELECT g.*, u.nombre AS usuario_nombre 
    FROM gastos g
    LEFT JOIN usuarios u ON u.id = g.usuario_id
    WHERE g.restaurante_id = ? AND g.fecha BETWEEN ? AND ?
    ORDER BY g.fecha DESC, g.created_at DESC
");
$stGastos->execute([$restauranteId, $fechaInicio, $fechaFin]);
$gastos = $stGastos->fetchAll();

$totalPeriodo = array_sum(array_column($gastos, 'monto'));

$pageTitle  = 'Gestión de Gastos';
$activeMenu = 'gastos';
require_once '../../includes/header.php';
?>

<div class="page-content">
    <?= $mensaje ?>

    <div class="d-flex align-center justify-between mb-16">
        <div>
            <h1><i class="fa-solid fa-money-bill-wave" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Gestión de Gastos</h1>
            <p>Control de gastos operativos del restaurante</p>
        </div>
        <button class="btn btn-primario" onclick="abrirModalGasto()">
            <i class="fa-solid fa-plus"></i> Registrar Gasto
        </button>
    </div>

    <!-- Filtro de fechas -->
    <div class="card mb-24">
        <form method="GET" style="display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                <label class="form-label">Desde</label>
                <input type="date" name="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:140px;">
                <label class="form-label">Hasta</label>
                <input type="date" name="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
            </div>
            <button type="submit" class="btn btn-primario">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <a href="gastos.php" class="btn btn-ghost">
                <i class="fa-solid fa-rotate-left"></i> Este mes
            </a>
        </form>
    </div>

    <div class="stats-grid mb-24">
        <div class="stat-card rojo">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div class="stat-valor">S/ <?= number_format($totalPeriodo, 2) ?></div>
            <div class="stat-label">Total Gastos (Período)</div>
        </div>
        <div class="stat-card naranja">
            <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="stat-valor"><?= count($gastos) ?></div>
            <div class="stat-label">Cantidad de Gastos</div>
        </div>
    </div>

    <div class="card">
        <div class="card-title"><i class="fa-solid fa-list"></i> Detalle de Gastos</div>
        <?php if ($gastos): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Registrado por</th>
                        <th class="text-right">Monto</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $g): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($g['fecha'])) ?></td>
                        <td><span class="badge badge-gris"><?= htmlspecialchars($g['categoria']) ?></span></td>
                        <td><?= htmlspecialchars($g['descripcion']) ?></td>
                        <td><?= htmlspecialchars($g['usuario_nombre']) ?></td>
                        <td class="text-right fw-700" style="color: var(--danger);">S/ <?= number_format($g['monto'], 2) ?></td>
                        <td class="text-right">
                            <button class="btn btn-ghost btn-sm" onclick='editarGasto(<?= json_encode($g) ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn btn-ghost btn-sm" style="color:var(--danger);" onclick='eliminarGasto(<?= $g["id"] ?>)'>
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:30px 0;">
            <div class="icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <p>No se han registrado gastos en este período.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Gasto -->
<div class="modal-overlay" id="modal-gasto">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title" id="modal-titulo"><i class="fa-solid fa-plus"></i> Registrar Gasto</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-gasto')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" id="accion" value="registrar">
            <input type="hidden" name="id" id="gasto_id" value="">
            <div class="modal-body">
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha" id="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" id="categoria" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="Insumos">Insumos de Cocina</option>
                            <option value="Limpieza">Artículos de Limpieza</option>
                            <option value="Mantenimiento">Mantenimiento/Reparaciones</option>
                            <option value="Servicios">Servicios (Luz, Agua, etc.)</option>
                            <option value="Movilidad">Movilidad / Pasajes</option>
                            <option value="Planilla">Planilla / Adelantos</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción del gasto</label>
                    <input type="text" name="descripcion" id="descripcion" class="form-control" placeholder="Ej: Compra de gas" required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">Monto (S/)</label>
                    <input type="number" name="monto" id="monto" class="form-control" step="0.01" min="0.10" placeholder="0.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="Modal.cerrar('modal-gasto')">Cancelar</button>
                <button type="submit" class="btn btn-primario" id="btn-submit">Registrar Gasto</button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario para eliminar oculto -->
<form method="POST" id="form-eliminar" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="eliminar_id" value="">
</form>

<script>
function abrirModalGasto() {
    document.getElementById('accion').value = 'registrar';
    document.getElementById('gasto_id').value = '';
    document.getElementById('fecha').value = '<?= date('Y-m-d') ?>';
    document.getElementById('categoria').value = '';
    document.getElementById('descripcion').value = '';
    document.getElementById('monto').value = '';
    document.getElementById('modal-titulo').innerHTML = '<i class="fa-solid fa-plus"></i> Registrar Gasto';
    document.getElementById('btn-submit').textContent = 'Registrar Gasto';
    Modal.abrir('modal-gasto');
}

function editarGasto(g) {
    document.getElementById('accion').value = 'editar';
    document.getElementById('gasto_id').value = g.id;
    document.getElementById('fecha').value = g.fecha;
    document.getElementById('categoria').value = g.categoria;
    document.getElementById('descripcion').value = g.descripcion;
    document.getElementById('monto').value = parseFloat(g.monto).toFixed(2);
    document.getElementById('modal-titulo').innerHTML = '<i class="fa-solid fa-pen"></i> Editar Gasto';
    document.getElementById('btn-submit').textContent = 'Guardar Cambios';
    Modal.abrir('modal-gasto');
}

function eliminarGasto(id) {
    confirmar('¿Está seguro de eliminar este gasto permanentemente?', () => {
        document.getElementById('eliminar_id').value = id;
        document.getElementById('form-eliminar').submit();
    });
}
</script>

<?php require_once '../../includes/footer.php'; ?>
