<?php
/**
 * roles/atencion/gastos.php — Registro de gastos del día (Atención)
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$usuarioId     = $_SESSION['usuario_id'];
$db = getDB();
$hoy = date('Y-m-d');

$mensaje = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
    $categoria   = trim($_POST['categoria'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto       = floatval($_POST['monto'] ?? 0);

    if ($categoria && $descripcion && $monto > 0) {
        $stIn = $db->prepare("INSERT INTO gastos (restaurante_id, usuario_id, categoria, descripcion, monto, fecha) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stIn->execute([$restauranteId, $usuarioId, $categoria, $descripcion, $monto, $hoy])) {
            $mensaje = "<div class='toast exito'><i class='fa-solid fa-circle-check'></i> Gasto registrado correctamente.</div>";
        } else {
            $mensaje = "<div class='toast error'><i class='fa-solid fa-circle-xmark'></i> Error al registrar gasto.</div>";
        }
    } else {
        $mensaje = "<div class='toast advertencia'><i class='fa-solid fa-triangle-exclamation'></i> Completa todos los campos correctamente.</div>";
    }
}

// Obtener gastos de HOY registrados por los usuarios de atención
$stGastos = $db->prepare("
    SELECT g.*, u.nombre AS usuario_nombre 
    FROM gastos g
    LEFT JOIN usuarios u ON u.id = g.usuario_id
    WHERE g.restaurante_id = ? AND g.fecha = ?
    ORDER BY g.created_at DESC
");
$stGastos->execute([$restauranteId, $hoy]);
$gastos = $stGastos->fetchAll();

$totalDia = array_sum(array_column($gastos, 'monto'));

$pageTitle  = 'Gastos del Día';
$activeMenu = 'gastos';
require_once '../../includes/header.php';
?>

<div class="page-content">
    <?= $mensaje ?>

    <div class="d-flex align-center justify-between mb-24">
        <div>
            <h1><i class="fa-solid fa-money-bill-wave" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Gastos del Día</h1>
            <p>Registra los gastos menores realizados hoy (<?= date('d/m/Y') ?>)</p>
        </div>
        <button class="btn btn-primario" onclick="Modal.abrir('modal-gasto')">
            <i class="fa-solid fa-plus"></i> Registrar Gasto
        </button>
    </div>

    <!-- Resumen -->
    <div class="stats-grid mb-24">
        <div class="stat-card rojo">
            <div class="stat-icon"><i class="fa-solid fa-money-bill-transfer"></i></div>
            <div class="stat-valor">S/ <?= number_format($totalDia, 2) ?></div>
            <div class="stat-label">Total Gastos Hoy</div>
        </div>
        <div class="stat-card naranja">
            <div class="stat-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="stat-valor"><?= count($gastos) ?></div>
            <div class="stat-label">Gastos Registrados</div>
        </div>
    </div>

    <!-- Tabla de Gastos de Hoy -->
    <div class="card">
        <div class="card-title"><i class="fa-solid fa-list"></i> Lista de gastos de hoy</div>
        <?php if ($gastos): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Registrado por</th>
                        <th class="text-right">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gastos as $g): ?>
                    <tr>
                        <td><?= date('H:i', strtotime($g['created_at'])) ?></td>
                        <td><span class="badge badge-gris"><?= htmlspecialchars($g['categoria']) ?></span></td>
                        <td><?= htmlspecialchars($g['descripcion']) ?></td>
                        <td><?= htmlspecialchars($g['usuario_nombre']) ?></td>
                        <td class="text-right fw-700" style="color: var(--danger);">S/ <?= number_format($g['monto'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:30px 0;">
            <div class="icon"><i class="fa-solid fa-money-bill-wave"></i></div>
            <p>No se han registrado gastos el día de hoy.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Registrar Gasto -->
<div class="modal-overlay" id="modal-gasto">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="fa-solid fa-plus"></i> Registrar Gasto</div>
            <button class="modal-close" onclick="Modal.cerrar('modal-gasto')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="accion" value="registrar">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select name="categoria" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <option value="Insumos">Insumos de Cocina</option>
                        <option value="Limpieza">Artículos de Limpieza</option>
                        <option value="Mantenimiento">Mantenimiento/Reparaciones</option>
                        <option value="Servicios">Servicios (Luz, Agua, etc.)</option>
                        <option value="Movilidad">Movilidad / Pasajes</option>
                        <option value="Otros">Otros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción del gasto</label>
                    <input type="text" name="descripcion" class="form-control" placeholder="Ej: Compra de gas, hielo, etc." required maxlength="255">
                </div>
                <div class="form-group">
                    <label class="form-label">Monto (S/)</label>
                    <input type="number" name="monto" class="form-control" step="0.01" min="0.10" placeholder="0.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="Modal.cerrar('modal-gasto')">Cancelar</button>
                <button type="submit" class="btn btn-primario">Registrar Gasto</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
