<?php
/**
 * roles/admin/caja.php — Gestión de Apertura y Cierre de Caja
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// Turno activo
$stTurnoActivo = $db->prepare("
    SELECT t.*, u.nombre AS abierto_por
    FROM turnos t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.restaurante_id = ? AND t.fin IS NULL
    ORDER BY t.inicio DESC LIMIT 1
");
$stTurnoActivo->execute([$restauranteId]);
$turnoActivo = $stTurnoActivo->fetch();

// Recalcular totales en tiempo real desde pagos (para visualización precisa)
if ($turnoActivo) {
    $stTotalesRT = $db->prepare("
        SELECT p.metodo, SUM(p.monto) as total
        FROM pagos p
        JOIN pedidos pe ON pe.id = p.pedido_id
        WHERE pe.restaurante_id = ? AND pe.estado = 'cobrado' AND p.anulado = 0 AND p.created_at >= ?
        GROUP BY p.metodo
    ");
    $stTotalesRT->execute([$restauranteId, $turnoActivo['inicio']]);
    $pagosRT = $stTotalesRT->fetchAll();
    // Resetear y recalcular
    $turnoActivo['total_efectivo'] = 0;
    $turnoActivo['total_yape'] = 0;
    $turnoActivo['total_transferencia'] = 0;
    $turnoActivo['total_tarjeta'] = 0;
    $turnoActivo['total_otros'] = 0;
    $turnoActivo['total_general'] = 0;
    foreach ($pagosRT as $pRT) {
        $m = floatval($pRT['total']);
        switch($pRT['metodo']) {
            case 'efectivo':      $turnoActivo['total_efectivo'] += $m; break;
            case 'yape':          $turnoActivo['total_yape'] += $m; break;
            case 'transferencia': $turnoActivo['total_transferencia'] += $m; break;
            case 'tarjeta':       $turnoActivo['total_tarjeta'] += $m; break;
            default:              $turnoActivo['total_otros'] += $m; break;
        }
        $turnoActivo['total_general'] += $m;
    }

    $stGastosAct = $db->prepare("SELECT COALESCE(SUM(monto), 0) FROM gastos WHERE restaurante_id = ? AND created_at >= ? AND activo = 1");
    $stGastosAct->execute([$restauranteId, $turnoActivo['inicio']]);
    $turnoActivo['total_gastos'] = floatval($stGastosAct->fetchColumn());
}

// Filtro por fecha (día, mes, año)
$fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');

// Historial de turnos cerrados del día
$stHistorial = $db->prepare("
    SELECT t.*, u.nombre AS abierto_por,
           (SELECT COUNT(DISTINCT p.pedido_id) FROM pagos p JOIN pedidos pe ON pe.id = p.pedido_id WHERE pe.restaurante_id = t.restaurante_id AND pe.estado = 'cobrado' AND p.anulado = 0 AND p.created_at BETWEEN t.inicio AND t.fin) as num_pedidos
    FROM turnos t
    JOIN usuarios u ON u.id = t.usuario_id
    WHERE t.restaurante_id = ? AND t.fin IS NOT NULL
      AND DATE(t.inicio) = ?
    ORDER BY t.inicio DESC
");
$stHistorial->execute([$restauranteId, $fechaFiltro]);
$historial = $stHistorial->fetchAll();

$pageTitle  = 'Caja';
$activeMenu = 'caja';
require_once '../../includes/header.php';
?>

<style>
.caja-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 24px 16px;
    border-radius: var(--radius-lg);
    border: 2px solid var(--border);
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    transition: border-color .3s;
}
.caja-hero.abierta {
    background: linear-gradient(135deg, rgba(34,197,94,.06) 0%, rgba(16,185,129,.06) 100%);
    border-color: var(--success);
}
.caja-hero.cerrada {
    background: linear-gradient(135deg, rgba(239,68,68,.04) 0%, rgba(220,38,38,.04) 100%);
    border-color: var(--danger);
}
.caja-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(255,255,255,.04) 0%, transparent 70%);
    pointer-events: none;
}
.caja-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 18px;
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .4px;
    text-transform: uppercase;
    margin-bottom: 20px;
}
.caja-status-badge.abierta {
    background: rgba(34,197,94,.15);
    color: var(--success);
    border: 1px solid rgba(34,197,94,.3);
}
.caja-status-badge.cerrada {
    background: rgba(239,68,68,.1);
    color: var(--danger);
    border: 1px solid rgba(239,68,68,.25);
}
.caja-status-badge .pulse-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: currentColor;
    animation: pulse-ring 1.5s ease-in-out infinite;
}
@keyframes pulse-ring {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: .4; transform: scale(1.3); }
}
.caja-hero-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    line-height: 1;
}
.caja-hero-title {
    font-size: 1.7rem;
    font-weight: 800;
    margin-bottom: 8px;
}
.caja-hero-meta {
    color: var(--text-secondary);
    font-size: .88rem;
    margin-bottom: 28px;
    line-height: 1.6;
}
.caja-totales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}
.caja-metodo-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 16px 12px;
    text-align: center;
    transition: transform .2s, box-shadow .2s;
}
.caja-metodo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
}
.caja-metodo-card .metodo-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}
.caja-metodo-card .metodo-monto {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-bottom: 4px;
}
.caja-metodo-card .metodo-label {
    font-size: .72rem;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.caja-total-general {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--primary-light, rgba(99,102,241,.1));
    border: 1.5px solid var(--primary);
    border-radius: var(--radius-md);
    padding: 18px 24px;
    margin-bottom: 28px;
}
.caja-total-general .label {
    font-size: .9rem;
    color: var(--text-secondary);
    font-weight: 600;
}
.caja-total-general .monto {
    font-size: 1.8rem;
    font-weight: 900;
    color: var(--primary);
}
.historial-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid var(--border);
}
.historial-row:last-child { border-bottom: none; }
.historial-index {
    width: 28px; height: 28px;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 700;
    color: var(--text-muted);
    flex-shrink: 0;
}
.historial-duracion {
    font-size: .72rem;
    color: var(--text-muted);
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 3px;
}
.btn-caja {
    min-width: 220px;
    min-height: 52px;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .3px;
    border-radius: var(--radius-md);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: transform .2s, box-shadow .2s;
    cursor: pointer;
    border: none;
}
.btn-caja:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,.2);
}
.btn-abrir {
    background: var(--success);
    color: #fff;
}
.btn-cerrar {
    background: var(--danger);
    color: #fff;
}
.btn-caja:disabled {
    opacity: .6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
.historial-monto {
    text-align: right;
    white-space: nowrap;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}
.historial-monto .monto-info {
    text-align: right;
}
@media (max-width: 640px) {
    .caja-hero-icon { font-size: 3rem; }
    .caja-hero-title { font-size: 1.35rem; }
    .caja-total-general { flex-direction: column; gap: 4px; text-align: center; }
    .btn-caja { min-width: 100%; }
    .historial-row { grid-template-columns: auto 1fr; }
    .historial-monto {
        grid-column: 1 / -1;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed var(--border);
    }
    .historial-monto .monto-info {
        text-align: left;
    }
    .historial-monto a {
        margin-top: 0 !important;
    }
}
</style>

<div class="page-content">

    <!-- Cabecera -->
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;">
        <div>
            <h1 style="font-size:1.55rem;font-weight:800;margin-bottom:4px;">
                <i class="fa-solid fa-cash-register" style="color:var(--primary);margin-right:8px;"></i>Caja
            </h1>
            <p style="color:var(--text-secondary);font-size:.88rem;">Gestión de turnos de caja.</p>
        </div>
    </div>

    <!-- Estado de la Caja -->
    <div class="caja-hero <?= $turnoActivo ? 'abierta' : 'cerrada' ?>">
        <div class="caja-status-badge <?= $turnoActivo ? 'abierta' : 'cerrada' ?>">
            <?php if ($turnoActivo): ?><span class="pulse-dot"></span><?php endif; ?>
            Caja <?= $turnoActivo ? 'Abierta' : 'Cerrada' ?>
        </div>

        <div class="caja-hero-icon"><?= $turnoActivo ? '🟢' : '🔴' ?></div>

        <div class="caja-hero-title">
            <?= $turnoActivo ? 'Turno en Curso' : 'Caja Cerrada' ?>
        </div>

        <div class="caja-hero-meta">
            <?php if ($turnoActivo): ?>
                Abierta el <strong><?= date('d/m/Y', strtotime($turnoActivo['inicio'])) ?></strong>
                a las <strong><?= date('H:i', strtotime($turnoActivo['inicio'])) ?></strong>
                &nbsp;·&nbsp;
                Abierta por <strong><?= htmlspecialchars($turnoActivo['abierto_por']) ?></strong>
            <?php else: ?>
                No hay ningún turno abierto actualmente.
            <?php endif; ?>
        </div>

        <?php if ($turnoActivo): ?>
            <button id="btn-cerrar" class="btn-caja btn-cerrar" onclick="confirmarCierre()">
                <i class="fa-solid fa-flag-checkered"></i> Cerrar Turno
            </button>
        <?php else: ?>
            <button id="btn-abrir" class="btn-caja btn-abrir" onclick="abrirCaja()">
                <i class="fa-solid fa-lock-open"></i> Abrir Caja
            </button>
        <?php endif; ?>
    </div>

    <?php if ($turnoActivo): ?>
    <!-- Totales del turno actual -->
    <div class="card mb-24">
        <div class="card-title"><i class="fa-solid fa-coins"></i> Totales del Turno Actual</div>

        <div class="caja-totales-grid">
            <?php
            $metodoInfo = [
                'total_efectivo'      => ['fa-money-bill-wave',  'Efectivo',      '#22c55e'],
                'total_yape'          => ['fa-mobile-screen',    'Yape',           '#8b5cf6'],
                'total_transferencia' => ['fa-building-columns', 'Transferencia',  '#3b82f6'],
                'total_tarjeta'       => ['fa-credit-card',      'Tarjeta',        '#f59e0b'],
                'total_otros'         => ['fa-rotate',           'Otros',          '#6b7280'],
            ];
            foreach ($metodoInfo as $campo => [$icon, $label, $color]):
                $monto = floatval($turnoActivo[$campo] ?? 0);
            ?>
            <div class="caja-metodo-card">
                <div class="metodo-icon" style="color:<?= $color ?>;">
                    <i class="fa-solid <?= $icon ?>"></i>
                </div>
                <div class="metodo-monto">S/ <?= number_format($monto, 2) ?></div>
                <div class="metodo-label"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="caja-total-general">
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:32px;">
                <div style="text-align:center;">
                    <div class="label" style="margin-bottom:4px;color:var(--text-secondary);"><i class="fa-solid fa-sack-dollar" style="margin-right:6px;color:var(--success);"></i>Total Ingresos</div>
                    <div class="monto" style="font-size:1.6rem;color:var(--text-primary);">S/ <?= number_format(floatval($turnoActivo['total_general'] ?? 0), 2) ?></div>
                </div>
                <div style="text-align:center;">
                    <div class="label" style="margin-bottom:4px;color:var(--text-secondary);"><i class="fa-solid fa-money-bill-transfer" style="margin-right:6px;color:var(--danger);"></i>Total Gastos</div>
                    <div class="monto" style="font-size:1.6rem;color:var(--text-primary);">S/ <?= number_format(floatval($turnoActivo['total_gastos'] ?? 0), 2) ?></div>
                </div>
                <div style="text-align:center;">
                    <div class="label" style="margin-bottom:4px;color:var(--text-secondary);"><i class="fa-solid fa-piggy-bank" style="margin-right:6px;color:var(--primary);"></i>Utilidad</div>
                    <div class="monto" style="font-size:1.6rem;color:var(--primary);">S/ <?= number_format(floatval($turnoActivo['total_general'] ?? 0) - floatval($turnoActivo['total_gastos'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial de Turnos -->
    <div class="card">
        <div class="card-title d-flex justify-between align-center" style="margin-bottom:20px;">
            <div><i class="fa-solid fa-clock-rotate-left"></i> Historial de Turnos Cerrados</div>
            <form method="GET" style="display:flex;gap:10px;">
                <input type="date" name="fecha" class="form-control" style="width:160px;padding:6px 12px;margin:0;" value="<?= htmlspecialchars($fechaFiltro) ?>" onchange="this.form.submit()">
            </form>
        </div>

        <?php if ($historial): ?>
        <div>
            <?php foreach ($historial as $i => $t):
                $inicio   = strtotime($t['inicio']);
                $fin      = strtotime($t['fin']);
                $duracion = $fin - $inicio;
                $horas    = floor($duracion / 3600);
                $minutos  = floor(($duracion % 3600) / 60);
            ?>
            <div class="historial-row">
                <div class="historial-index"><?= $i + 1 ?></div>
                <div>
                    <div style="font-size:.88rem;font-weight:600;color:var(--text-primary);">
                        <?= date('d/m/Y', $inicio) ?>
                        &nbsp;<span style="color:var(--text-muted);font-weight:400;"><?= date('H:i', $inicio) ?> → <?= date('H:i', $fin) ?></span>
                    </div>
                    <div class="historial-duracion">
                        <span><i class="fa-regular fa-clock"></i> <?= $horas ?>h <?= $minutos ?>m</span>
                        <span><i class="fa-regular fa-user"></i> <?= htmlspecialchars($t['abierto_por']) ?></span>
                    </div>
                    <!-- Mini resumen por método -->
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                        <?php
                        $metodosMini = [
                            'total_efectivo'      => ['Efec',  '#22c55e'],
                            'total_yape'          => ['Yape',  '#8b5cf6'],
                            'total_transferencia' => ['Trans', '#3b82f6'],
                            'total_tarjeta'       => ['Tarj',  '#f59e0b'],
                            'total_otros'         => ['Otro',  '#6b7280'],
                        ];
                        foreach ($metodosMini as $campo => [$lbl, $clr]):
                            $m = floatval($t[$campo] ?? 0);
                            if ($m <= 0) continue;
                        ?>
                        <span style="font-size:.7rem;padding:2px 8px;border-radius:999px;background:<?= $clr ?>22;color:<?= $clr ?>;border:1px solid <?= $clr ?>44;font-weight:600;">
                            <?= $lbl ?>: S/ <?= number_format($m, 2) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="historial-monto">
                    <?php if (floatval($t['total_general']) == 0): ?>
                    <div class="monto-info">
                        <div style="font-size:.85rem;font-weight:700;color:var(--warning,#f59e0b);background:#fffbeb;padding:4px 8px;border-radius:4px;border:1px solid #fde68a;display:inline-block;">Sin ventas</div>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px;">0 pedidos</div>
                    </div>
                    <?php else: ?>
                    <div class="monto-info">
                        <div style="font-size:1rem;font-weight:800;color:var(--success);">S/ <?= number_format(floatval($t['total_general']), 2) ?></div>
                        <div style="font-size:.72rem;color:var(--text-muted);"><?= $t['num_pedidos'] ?> pedido(s)</div>
                    </div>
                    <a href="turno_detalle.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm" style="font-size:.75rem;padding:4px 8px;margin-top:6px;">
                        <i class="fa-solid fa-chart-pie"></i> Detalles
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon"><i class="fa-solid fa-box-open"></i></div>
            <p>Aún no hay turnos cerrados registrados.</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal de confirmación de cierre -->
<div id="modal-cierre" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:36px 32px;max-width:420px;width:90%;text-align:center;animation:slideUp .25s ease;">
        <div style="font-size:3rem;margin-bottom:16px;color:var(--danger);">🏁</div>
        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:10px;">Cerrar el Turno Actual</h2>
        <p style="color:var(--text-secondary);font-size:.95rem;margin-bottom:20px;line-height:1.5;">
            Selecciona si deseas solo cerrar el turno o cerrarlo y reabrir uno nuevo inmediatamente.
        </p>

        <?php if ($turnoActivo): ?>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-md);padding:16px;margin-bottom:24px;text-align:left;">
            <div style="font-size:.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:8px;">Resumen del Turno</div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Apertura:</span>
                <strong><?= date('H:i', strtotime($turnoActivo['inicio'])) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Efectivo:</span>
                <strong>S/ <?= number_format(floatval($turnoActivo['total_efectivo'] ?? 0), 2) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="color:var(--text-secondary);">Otros Métodos:</span>
                <strong>S/ <?= number_format(floatval($turnoActivo['total_general']) - floatval($turnoActivo['total_efectivo']), 2) ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid var(--border);">
                <span style="color:var(--text-primary);font-weight:700;">Total Acumulado:</span>
                <strong style="color:var(--primary);font-size:1.1rem;">S/ <?= number_format(floatval($turnoActivo['total_general'] ?? 0), 2) ?></strong>
            </div>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
            <button id="btn-confirmar-cierre" class="btn btn-danger" onclick="cerrarCaja(false)">
                <i class="fa-solid fa-flag-checkered"></i> Confirmar Cierre
            </button>
            <button id="btn-confirmar-reabrir" class="btn" style="background:var(--success);color:#fff;border-color:var(--success);" onclick="cerrarCaja(true)">
                <i class="fa-solid fa-rotate"></i> Confirmar y Reabrir Caja
            </button>
        </div>
    </div>
</div>

<script>
function confirmarCierre() {
    const modal = document.getElementById('modal-cierre');
    modal.style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('modal-cierre').style.display = 'none';
}

function cerrarCaja(reabrir) {
    const btnId = reabrir ? 'btn-confirmar-reabrir' : 'btn-confirmar-cierre';
    const btn = document.getElementById(btnId);
    
    document.getElementById('btn-confirmar-cierre').disabled = true;
    document.getElementById('btn-confirmar-reabrir').disabled = true;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    fetch('<?= BASE_URL ?>/api/cerrar_turno.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ reabrir: reabrir })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modal-cierre').style.display = 'none';
            showToast('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast('❌ ' + data.message, 'error');
            document.getElementById('btn-confirmar-cierre').disabled = false;
            document.getElementById('btn-confirmar-reabrir').disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(() => {
        showToast('❌ Error de conexión', 'error');
        document.getElementById('btn-confirmar-cierre').disabled = false;
        document.getElementById('btn-confirmar-reabrir').disabled = false;
        btn.innerHTML = originalText;
    });
}

function abrirCaja() {
    const btn = document.getElementById('btn-abrir');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Abriendo...';

    fetch('<?= BASE_URL ?>/api/abrir_turno.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 900);
        } else {
            showToast('❌ ' + data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Abrir Caja';
        }
    })
    .catch(() => {
        showToast('❌ Error de conexión', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-lock-open"></i> Abrir Caja';
    });
}

// Cerrar modal clickando fuera
document.getElementById('modal-cierre').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});

// Función helper toast (si no existe globalmente)
function showToast(msg, type = 'info') {
    if (typeof window.showToast === 'function' && window.showToast !== showToast) {
        window.showToast(msg, type); return;
    }
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:10px;
        background:var(--surface);border:1px solid var(--border);color:var(--text-primary);
        font-size:.9rem;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.25);
        animation:slideUp .25s ease;max-width:340px;`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
}
</script>

<?php require_once '../../includes/footer.php'; ?>
