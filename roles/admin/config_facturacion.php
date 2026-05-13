<?php
/**
 * roles/admin/config_facturacion.php
 * Configuración de datos de facturación del restaurante.
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$db = getDB();

// ── Guardar configuración ────────────────────────────────────────
$saved = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruc          = preg_replace('/\D/', '', trim($_POST['ruc']          ?? ''));
    $razonSocial  = trim($_POST['razon_social']   ?? '');
    $direccion    = trim($_POST['direccion_fiscal']?? '');
    $serieBoleta  = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['serie_boleta']  ?? 'B001')));
    $serieFactura = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['serie_factura'] ?? 'F001')));
    $pieMensaje   = trim($_POST['pie_mensaje'] ?? '');

    if ($ruc && strlen($ruc) !== 11) {
        $error = 'El RUC del restaurante debe tener exactamente 11 dígitos.';
    } elseif (strlen($serieBoleta) < 2 || strlen($serieFactura) < 2) {
        $error = 'Las series deben tener al menos 2 caracteres.';
    } else {
        // Manejar logo upload
        $logoPath = null;
        if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg'])) {
                $error = 'Formato de logo no válido. Usa JPG, PNG, WebP o SVG.';
            } else {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/logos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'logo_rest_' . $restauranteId . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                    $logoPath = BASE_URL . '/assets/logos/' . $filename;
                }
            }
        }

        if (!$error) {
            // Obtener config actual (para correlativo)
            $stCurrent = $db->prepare("SELECT correlativo_boleta, correlativo_factura, logo FROM facturacion_config WHERE restaurante_id = ?");
            $stCurrent->execute([$restauranteId]);
            $current = $stCurrent->fetch();

            if ($current) {
                $finalLogo = $logoPath ?? $current['logo'];
                $st = $db->prepare("
                    UPDATE facturacion_config
                    SET ruc = ?, razon_social = ?, direccion_fiscal = ?,
                        serie_boleta = ?, serie_factura = ?,
                        pie_mensaje = ?, logo = ?
                    WHERE restaurante_id = ?
                ");
                $st->execute([$ruc ?: null, $razonSocial, $direccion, $serieBoleta, $serieFactura, $pieMensaje, $finalLogo, $restauranteId]);
            } else {
                $st = $db->prepare("
                    INSERT INTO facturacion_config
                        (restaurante_id, ruc, razon_social, direccion_fiscal, serie_boleta, serie_factura, pie_mensaje, logo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $st->execute([$restauranteId, $ruc ?: null, $razonSocial, $direccion, $serieBoleta, $serieFactura, $pieMensaje, $logoPath]);
            }
            $saved = true;
        }
    }
}

// ── Leer configuración actual ────────────────────────────────────
$stCfg = $db->prepare("SELECT * FROM facturacion_config WHERE restaurante_id = ?");
$stCfg->execute([$restauranteId]);
$cfg = $stCfg->fetch() ?: [];

$pageTitle  = 'Configuración de Facturación';
$activeMenu = 'facturacion';
require_once '../../includes/header.php';
?>

<div class="layout-admin">
    <!-- Sidebar -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><span class="menu-icon"><i class="fa-solid fa-chart-bar"></i></span> Dashboard</a></li>
            <li><a href="mesas.php"><span class="menu-icon"><i class="fa-solid fa-chair"></i></span> Mesas</a></li>
            <li><a href="menu_categorias.php"><span class="menu-icon"><i class="fa-solid fa-folder"></i></span> Categorías</a></li>
            <li><a href="menu_productos.php"><span class="menu-icon"><i class="fa-solid fa-utensils"></i></span> Productos</a></li>
            <li><a href="usuarios.php"><span class="menu-icon"><i class="fa-solid fa-users"></i></span> Usuarios</a></li>
            <li><a href="reportes.php"><span class="menu-icon"><i class="fa-solid fa-chart-line"></i></span> Reportes</a></li>
            <li><a href="historial.php"><span class="menu-icon"><i class="fa-solid fa-clock-rotate-left"></i></span> Historial</a></li>
            <li><a href="historial_comprobantes.php" class="<?= $activeMenu === 'comprobantes' ? 'active' : '' ?>"><span class="menu-icon"><i class="fa-solid fa-file-invoice"></i></span> Comprobantes</a></li>
            <li><a href="config_facturacion.php" class="active"><span class="menu-icon"><i class="fa-solid fa-gear"></i></span> Facturación</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-gear" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Configuración de Facturación</h1>
                    <p>Datos del restaurante para boletas y facturas</p>
                </div>
                <a href="historial_comprobantes.php" class="btn btn-ghost btn-sm">
                    <i class="fa-solid fa-file-invoice"></i> Ver comprobantes
                </a>
            </div>

            <?php if ($saved): ?>
            <div class="alert-exito" style="background:var(--success-bg,rgba(34,197,94,.12));border:1px solid var(--success);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:20px;color:var(--success);font-weight:600;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-circle-check"></i> Configuración guardada correctamente.
            </div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert-error" style="background:rgba(239,68,68,.1);border:1px solid var(--danger);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:20px;color:var(--danger);font-weight:600;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <!-- Datos del restaurante -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-building"></i> Datos del restaurante</div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-16">
                        <div>
                            <label class="form-label">RUC del restaurante</label>
                            <input type="text" name="ruc" class="form-control"
                                value="<?= htmlspecialchars($cfg['ruc'] ?? '') ?>"
                                placeholder="20123456789" maxlength="11"
                                inputmode="numeric" pattern="\d{11}"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">11 dígitos. Requerido para emitir facturas.</div>
                        </div>
                        <div>
                            <label class="form-label">Razón social</label>
                            <input type="text" name="razon_social" class="form-control"
                                value="<?= htmlspecialchars($cfg['razon_social'] ?? '') ?>"
                                placeholder="RESTAURANTE SABOR PERU S.A.C.">
                        </div>
                    </div>

                    <div class="mb-16">
                        <label class="form-label">Dirección fiscal</label>
                        <input type="text" name="direccion_fiscal" class="form-control"
                            value="<?= htmlspecialchars($cfg['direccion_fiscal'] ?? '') ?>"
                            placeholder="Av. Los Álamos 123, Tarapoto, San Martín">
                    </div>

                    <div>
                        <label class="form-label">Logo del restaurante <span style="font-weight:400;font-size:.8rem;color:var(--text-secondary);">(JPG, PNG, WebP — se mostrará en comprobantes)</span></label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if (!empty($cfg['logo'])): ?>
                        <div style="margin-top:10px;display:flex;align-items:center;gap:10px;">
                            <img src="<?= htmlspecialchars($cfg['logo']) ?>" alt="Logo actual" style="max-height:48px;max-width:120px;object-fit:contain;border-radius:4px;border:1px solid var(--border);">
                            <span style="font-size:.78rem;color:var(--text-secondary);">Logo actual</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Series de comprobantes -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-hashtag"></i> Series de comprobantes</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label class="form-label">Serie de Boleta</label>
                            <input type="text" name="serie_boleta" class="form-control"
                                value="<?= htmlspecialchars($cfg['serie_boleta'] ?? 'B001') ?>"
                                placeholder="B001" maxlength="4"
                                oninput="this.value=this.value.toUpperCase()">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">
                                Próximo: <?= htmlspecialchars($cfg['serie_boleta'] ?? 'B001') ?>-<?= str_pad(intval($cfg['correlativo_boleta'] ?? 0) + 1, 5, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Serie de Factura</label>
                            <input type="text" name="serie_factura" class="form-control"
                                value="<?= htmlspecialchars($cfg['serie_factura'] ?? 'F001') ?>"
                                placeholder="F001" maxlength="4"
                                oninput="this.value=this.value.toUpperCase()">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">
                                Próximo: <?= htmlspecialchars($cfg['serie_factura'] ?? 'F001') ?>-<?= str_pad(intval($cfg['correlativo_factura'] ?? 0) + 1, 5, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensaje de pie -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-comment-dots"></i> Mensaje de pie de comprobante</div>
                    <textarea name="pie_mensaje" class="form-control" rows="2"
                        placeholder="¡Gracias por su visita! Vuelva pronto 😊"
                        maxlength="300"><?= htmlspecialchars($cfg['pie_mensaje'] ?? '¡Gracias por su visita!') ?></textarea>
                    <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">Aparecerá al pie de todas las boletas y facturas.</div>
                </div>

                <!-- Configuración de API -->
                <div class="card mb-20" style="border-left:3px solid var(--warning);">
                    <div class="card-title"><i class="fa-solid fa-key"></i> Token API (DNI/RUC lookup)</div>
                    <div style="font-size:.875rem;color:var(--text-secondary);margin-bottom:12px;">
                        El token de <strong>api.apis.net.pe</strong> se configura directamente en
                        <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:.8rem;">config/db.php</code>
                        como la constante <code style="background:var(--bg-secondary);padding:2px 6px;border-radius:4px;font-size:.8rem;">APIS_NET_TOKEN</code>.
                        <br><br>
                        Ejemplo:<br>
                        <code style="background:var(--bg-secondary);padding:6px 10px;border-radius:6px;font-size:.8rem;display:block;margin-top:6px;">define('APIS_NET_TOKEN', 'apis-token-xxxxx');</code>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;font-size:.82rem;">
                        <?php if (defined('APIS_NET_TOKEN') && !empty(APIS_NET_TOKEN)): ?>
                        <i class="fa-solid fa-circle-check" style="color:var(--success);"></i>
                        <span style="color:var(--success);font-weight:600;">Token configurado correctamente.</span>
                        <?php else: ?>
                        <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i>
                        <span style="color:var(--warning);font-weight:600;">Token no configurado. La consulta automática de DNI/RUC no funcionará, pero el cobro manual sí.</span>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-primario btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
                </button>

            </form>

        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
