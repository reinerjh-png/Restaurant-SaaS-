<?php
/**
 * roles/admin/config_facturacion.php
 * Configuración de identidad de marca y datos de facturación del restaurante.
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
    $ruc             = preg_replace('/\D/', '', trim($_POST['ruc']              ?? ''));
    $razonSocial     = trim($_POST['razon_social']     ?? '');
    $nombreComercial = trim($_POST['nombre_comercial'] ?? '');
    $direccion       = trim($_POST['direccion_fiscal'] ?? '');
    $telefono        = trim($_POST['telefono']         ?? '');
    $serieBoleta     = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['serie_boleta']  ?? 'B001')));
    $serieFactura    = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['serie_factura'] ?? 'F001')));
    $serieSimple     = strtoupper(preg_replace('/[^A-Z0-9]/', '', trim($_POST['serie_simple']  ?? 'T001')));
    $pieMensaje      = trim($_POST['pie_mensaje'] ?? '');

    if ($ruc && strlen($ruc) !== 11) {
        $error = 'El RUC del restaurante debe tener exactamente 11 dígitos.';
    } elseif (strlen($serieBoleta) < 2 || strlen($serieFactura) < 2 || strlen($serieSimple) < 2) {
        $error = 'Las series deben tener al menos 2 caracteres.';
    } else {
        // Manejar logo upload
        $logoPath = null;
        if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $tmpFile = $_FILES['logo']['tmp_name'];
            $fileSize = filesize($tmpFile);
            
            if ($fileSize > 2 * 1024 * 1024) {
                $error = 'El logo no debe pesar más de 2MB.';
            } elseif ($fileSize < 100) {
                // Rechazar archivos sospechosamente pequeños (posibles scripts vacíos o de prueba)
                $error = 'El archivo es demasiado pequeño para ser una imagen válida.';
            } else {
                // ── VALIDACIÓN REAL DE TIPO MIME (Seguridad XSS) ───────────────
                // Se usa finfo para leer los magic bytes del archivo, NO la extensión
                // del nombre original (que puede ser manipulada por el atacante).
                // SVG y cualquier tipo no rasterizado son rechazados explícitamente.
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmpFile);
                finfo_close($finfo);

                // Lista blanca estricta: solo imágenes rasterizadas seguras.
                // La extensión de salida se genera desde este mapa, nunca del nombre original.
                $allowedMimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif'
                    // 'image/svg+xml' PROHIBIDO: permite inyección de JS (XSS Almacenado)
                ];

                if (!array_key_exists($mime, $allowedMimes)) {
                    $error = 'Formato de logo no válido. Usa JPG, PNG, WebP o GIF (SVG no permitido por seguridad).';
                } else {
                    // La extensión proviene del MIME detectado, nunca del nombre del archivo original
                    $ext = $allowedMimes[$mime];
                    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/assets/logos/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    // Use time() to prevent caching old logos
                    $filename = 'logo_rest_' . $restauranteId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmpFile, $uploadDir . $filename)) {
                        $logoPath = BASE_URL . '/assets/logos/' . $filename;
                    } else {
                        $error = 'Error al guardar el logo en el servidor.';
                    }
                }
            }
        }

        if (!$error) {
            // Obtener config actual (para correlativo y logo)
            $stCurrent = $db->prepare("SELECT correlativo_boleta, correlativo_factura, logo FROM facturacion_config WHERE restaurante_id = ?");
            $stCurrent->execute([$restauranteId]);
            $current = $stCurrent->fetch();

            if ($current) {
                $finalLogo = $logoPath ?? $current['logo'];
                $st = $db->prepare("
                    UPDATE facturacion_config
                    SET ruc = ?, razon_social = ?, nombre_comercial = ?,
                        direccion_fiscal = ?, telefono = ?,
                        serie_boleta = ?, serie_factura = ?, serie_simple = ?,
                        pie_mensaje = ?, logo = ?
                    WHERE restaurante_id = ?
                ");
                $st->execute([
                    $ruc ?: null, $razonSocial, $nombreComercial ?: null,
                    $direccion, $telefono ?: null,
                    $serieBoleta, $serieFactura, $serieSimple,
                    $pieMensaje, $finalLogo,
                    $restauranteId
                ]);
            } else {
                $st = $db->prepare("
                    INSERT INTO facturacion_config
                        (restaurante_id, ruc, razon_social, nombre_comercial,
                         direccion_fiscal, telefono, serie_boleta, serie_factura, serie_simple, pie_mensaje, logo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $st->execute([
                    $restauranteId, $ruc ?: null, $razonSocial, $nombreComercial ?: null,
                    $direccion, $telefono ?: null,
                    $serieBoleta, $serieFactura, $serieSimple, $pieMensaje, $logoPath
                ]);
            }
            $saved = true;
        }
    }
}

// ── Leer configuración actual ────────────────────────────────────
$stCfg = $db->prepare("SELECT * FROM facturacion_config WHERE restaurante_id = ?");
$stCfg->execute([$restauranteId]);
$cfg = $stCfg->fetch() ?: [];

// Leer nombre del restaurante desde la tabla restaurantes
$stRest = $db->prepare("SELECT nombre FROM restaurantes WHERE id = ?");
$stRest->execute([$restauranteId]);
$restaurante = $stRest->fetch();

$pageTitle  = 'Identidad de Marca';
$activeMenu = 'facturacion';
require_once '../../includes/header.php';
?>

        <div class="page-content">

            <div class="d-flex align-center justify-between mb-24">
                <div>
                    <h1><i class="fa-solid fa-store" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Identidad de Marca</h1>
                    <p>Configura el branding y los datos legales de tu restaurante</p>
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <!-- ══ SECCIÓN 1: IDENTIDAD VISUAL ══════════════════════════ -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-palette"></i> Identidad Visual</div>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:16px;">
                        El logo y nombre comercial aparecerán en la barra superior del sistema y en todos los comprobantes emitidos.
                    </p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-16">
                        <!-- Vista previa del logo actual -->
                        <div>
                            <label class="form-label">Logo del restaurante <span style="font-weight:400;font-size:.8rem;color:var(--text-secondary);">(JPG, PNG, WebP, GIF)</span></label>
                            <input type="file" name="logo" id="logo-input" class="form-control" accept="image/jpeg, image/png, image/webp, image/gif" onchange="previsualizarLogo(this)">
                            <div style="margin-top:12px;display:flex;align-items:center;gap:12px;">
                                <?php
                                $logoActual = $cfg['logo'] ?? null;
                                $logoDisplay = ($logoActual && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoActual))
                                    ? htmlspecialchars($logoActual)
                                    : BASE_URL . '/assets/logo.png';
                                ?>
                                <div id="logo-preview-box" style="width:80px;height:80px;border-radius:var(--radius-md);border:2px solid var(--border);overflow:hidden;display:flex;align-items:center;justify-content:center;background:var(--bg-secondary);">
                                    <img id="logo-preview" src="<?= $logoDisplay ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                                </div>
                                <div>
                                    <?php if ($logoActual): ?>
                                    <div style="font-size:.78rem;color:var(--success);font-weight:600;margin-bottom:4px;"><i class="fa-solid fa-circle-check"></i> Logo configurado</div>
                                    <?php else: ?>
                                    <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:4px;">Usando logo del sistema</div>
                                    <?php endif; ?>
                                    <div style="font-size:.72rem;color:var(--text-secondary);">Recomendado: fondo transparente (PNG/WebP), mín. 200×200px</div>
                                </div>
                            </div>
                        </div>

                        <!-- Nombre comercial -->
                        <div>
                            <label class="form-label">Nombre comercial</label>
                            <input type="text" name="nombre_comercial" class="form-control"
                                value="<?= htmlspecialchars($cfg['nombre_comercial'] ?? '') ?>"
                                placeholder="Ej: Sabor Amazónico">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">
                                Nombre visible en el dashboard y encabezado del sistema. Si lo dejas vacío, se usa «<?= htmlspecialchars($restaurante['nombre'] ?? 'Restaurante') ?>».
                            </div>

                            <div style="margin-top:16px;">
                                <label class="form-label">Mensaje de pie de comprobante</label>
                                <textarea name="pie_mensaje" class="form-control" rows="3"
                                    placeholder="¡Gracias por su visita! Vuelva pronto 😊"
                                    maxlength="300"><?= htmlspecialchars($cfg['pie_mensaje'] ?? '¡Gracias por su visita!') ?></textarea>
                                <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">Aparecerá al pie de todas las boletas y facturas.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ SECCIÓN 2: DATOS LEGALES (SUNAT) ══════════════════════ -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-building-columns"></i> Datos Legales — SUNAT</div>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:16px;">
                        Requeridos para que las boletas y facturas sean válidas según la normativa de SUNAT Perú.
                    </p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="mb-16">
                        <div>
                            <label class="form-label">RUC del restaurante <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="ruc" class="form-control"
                                value="<?= htmlspecialchars($cfg['ruc'] ?? '') ?>"
                                placeholder="20123456789" maxlength="11"
                                inputmode="numeric" pattern="\d{11}"
                                oninput="this.value=this.value.replace(/\D/g,'').slice(0,11)">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">11 dígitos. Requerido para emitir facturas.</div>
                        </div>
                        <div>
                            <label class="form-label">Razón social <span style="color:var(--danger);">*</span></label>
                            <input type="text" name="razon_social" class="form-control"
                                value="<?= htmlspecialchars($cfg['razon_social'] ?? '') ?>"
                                placeholder="RESTAURANTE SABOR PERU S.A.C.">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">Nombre legal registrado en SUNAT.</div>
                        </div>
                    </div>

                    <div class="mb-16">
                        <label class="form-label">Dirección fiscal <span style="color:var(--danger);">*</span></label>
                        <input type="text" name="direccion_fiscal" class="form-control"
                            value="<?= htmlspecialchars($cfg['direccion_fiscal'] ?? '') ?>"
                            placeholder="Av. Los Álamos 123, Tarapoto, San Martín">
                    </div>

                    <div>
                        <label class="form-label">Teléfono de contacto</label>
                        <input type="text" name="telefono" class="form-control"
                            value="<?= htmlspecialchars($cfg['telefono'] ?? '') ?>"
                            placeholder="042 123456 / 999 888 777"
                            maxlength="20">
                        <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">Opcional. Aparecerá en los comprobantes emitidos.</div>
                    </div>
                </div>

                <!-- ══ SECCIÓN 3: SERIES DE COMPROBANTES ══════════════════════ -->
                <div class="card mb-20">
                    <div class="card-title"><i class="fa-solid fa-hashtag"></i> Series de comprobantes</div>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:16px;">
                        La serie identifica el punto de emisión. No cambies la serie si ya tienes comprobantes emitidos.
                    </p>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
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
                        <div>
                            <label class="form-label">Serie de Ticket (Simple)</label>
                            <input type="text" name="serie_simple" class="form-control"
                                value="<?= htmlspecialchars($cfg['serie_simple'] ?? 'T001') ?>"
                                placeholder="T001" maxlength="4"
                                oninput="this.value=this.value.toUpperCase()">
                            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">
                                Próximo: <?= htmlspecialchars($cfg['serie_simple'] ?? 'T001') ?>-<?= str_pad(intval($cfg['correlativo_simple'] ?? 0) + 1, 5, '0', STR_PAD_LEFT) ?>
                            </div>
                        </div>
                    </div>
                </div>


                <button type="submit" class="btn btn-primario btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
                </button>

            </form>

        </div>

<script>
function previsualizarLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logo-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
