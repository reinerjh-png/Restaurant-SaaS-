<?php
/**
 * roles/atencion/cobrar.php — Flujo de cobro con opción de comprobante
 * Sistema SaaS Restaurante | R.DEV
 */
session_start();
require_once '../../config/db.php';
requireRole(['atencion', 'admin', 'superadmin']);

$restauranteId = $_SESSION['restaurante_id'];
$pedidoId = (int)($_GET['pedido_id'] ?? 0);

if (!$pedidoId) { header('Location: dashboard.php'); exit; }

$db = getDB();

$stPed = $db->prepare("
    SELECT pe.*, m.numero AS mesa_numero
    FROM pedidos pe
    LEFT JOIN mesas m ON m.id = pe.mesa_id
    WHERE pe.id = ? AND pe.restaurante_id = ? AND pe.estado = 'activo'
");
$stPed->execute([$pedidoId, $restauranteId]);
$pedido = $stPed->fetch();
if (!$pedido) { header('Location: dashboard.php'); exit; }

$stItems = $db->prepare("
    SELECT pi.*, pr.nombre AS producto_nombre,
           GROUP_CONCAT(ov.valor SEPARATOR ' · ') AS opciones_texto
    FROM pedido_items pi
    JOIN productos pr ON pr.id = pi.producto_id
    LEFT JOIN pedido_item_opciones pio ON pio.item_id = pi.id
    LEFT JOIN opciones_valor ov ON ov.id = pio.valor_id
    WHERE pi.pedido_id = ?
    GROUP BY pi.id
    ORDER BY pi.created_at ASC
");
$stItems->execute([$pedidoId]);
$items = $stItems->fetchAll();

$mesaLabel = $pedido['mesa_numero'] ? 'Mesa '.$pedido['mesa_numero'] : 'Para llevar';
$total     = $pedido['total'];

$pageTitle = "Cobrar · $mesaLabel";
require_once '../../includes/header.php';
?>

<div style="max-width:580px;margin:0 auto;padding:16px;" class="page-content">

    <div class="d-flex align-center justify-between mb-16">
        <div>
            <h1><i class="fa-solid fa-sack-dollar" style="font-size:1rem;margin-right:6px;color:var(--primary);"></i> Cobrar pedido</h1>
            <p>
                <i class="fa-solid <?= $pedido['tipo']==='aqui' ? 'fa-house' : 'fa-bag-shopping' ?>"></i>
                <?= htmlspecialchars($mesaLabel) ?> · #<?= $pedidoId ?>
            </p>
        </div>
        <a href="comanda.php?pedido_id=<?= $pedidoId ?>&mesa_id=<?= $pedido['mesa_id'] ?>" class="btn btn-ghost btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Resumen del pedido -->
    <div class="card mb-16">
        <div class="card-title"><i class="fa-solid fa-receipt"></i> Resumen del pedido</div>
        <?php
        $itemsAgrupados = [];
        foreach ($items as $item) {
            $key = $item['producto_nombre'] . '||' . ($item['opciones_texto'] ?? '');
            if (isset($itemsAgrupados[$key])) {
                $itemsAgrupados[$key]['cantidad'] += $item['cantidad'];
                $itemsAgrupados[$key]['subtotal'] += $item['subtotal'];
            } else {
                $itemsAgrupados[$key] = [
                    'nombre'   => $item['producto_nombre'],
                    'opciones' => $item['opciones_texto'] ?? '',
                    'cantidad' => $item['cantidad'],
                    'subtotal' => $item['subtotal'],
                ];
            }
        }
        foreach ($itemsAgrupados as $item): ?>
        <div class="pedido-item">
            <div style="flex:1;">
                <div class="pedido-item-nombre"><?= $item['cantidad'] ?>x <?= htmlspecialchars($item['nombre']) ?></div>
                <?php if ($item['opciones']): ?>
                <div class="pedido-item-opciones">· <?= htmlspecialchars($item['opciones']) ?></div>
                <?php endif; ?>
            </div>
            <div class="pedido-item-precio">S/ <?= number_format($item['subtotal'], 2) ?></div>
        </div>
        <?php endforeach; ?>
        <div class="pedido-total" style="margin-top:12px;padding-top:12px;border-top:2px solid var(--border);">
            <span>TOTAL A COBRAR</span>
            <span style="color:var(--success);font-size:1.4rem;">S/ <?= number_format($total, 2) ?></span>
        </div>
    </div>

    <!-- ══ PASO 1: ¿Emitir comprobante? ══════════════════════════ -->
    <div class="card mb-16" id="card-comprobante-opcion">
        <div class="card-title"><i class="fa-solid fa-file-invoice"></i> ¿Desea emitir comprobante?</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <label id="opc-no-label" class="metodo-item" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-md);border:2px solid var(--border);transition:all .2s;" onclick="seleccionarTipoComp('no')">
                <input type="radio" name="tipo_comp" value="no" id="opc-no" style="display:none;" checked>
                <div class="metodo-check" id="check-comp-no" style="background:var(--primary);"><i class="fa-solid fa-check"></i></div>
                <i class="fa-solid fa-xmark" style="font-size:1.1rem;color:var(--text-secondary);"></i>
                <span class="metodo-nombre">No, solo cobrar</span>
            </label>
            <label id="opc-simple-label" class="metodo-item" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-md);border:2px solid var(--border);transition:all .2s;" onclick="seleccionarTipoComp('simple')">
                <input type="radio" name="tipo_comp" value="simple" id="opc-simple" style="display:none;">
                <div class="metodo-check" id="check-comp-simple"><i class="fa-solid fa-check"></i></div>
                <i class="fa-solid fa-file-lines" style="font-size:1.1rem;color:var(--text-secondary);"></i>
                <span class="metodo-nombre">Comprobante Simple</span>
            </label>
            <label id="opc-boleta-label" class="metodo-item" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-md);border:2px solid var(--border);transition:all .2s;" onclick="seleccionarTipoComp('boleta')">
                <input type="radio" name="tipo_comp" value="boleta" id="opc-boleta" style="display:none;">
                <div class="metodo-check" id="check-comp-boleta"><i class="fa-solid fa-check"></i></div>
                <i class="fa-solid fa-receipt" style="font-size:1.1rem;color:var(--text-secondary);"></i>
                <span class="metodo-nombre">Boleta de Venta <span style="font-size:.78rem;color:var(--text-muted);">(DNI)</span></span>
            </label>
            <label id="opc-factura-label" class="metodo-item" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--radius-md);border:2px solid var(--border);transition:all .2s;" onclick="seleccionarTipoComp('factura')">
                <input type="radio" name="tipo_comp" value="factura" id="opc-factura" style="display:none;">
                <div class="metodo-check" id="check-comp-factura"><i class="fa-solid fa-check"></i></div>
                <i class="fa-solid fa-file-contract" style="font-size:1.1rem;color:var(--text-secondary);"></i>
                <span class="metodo-nombre">Factura <span style="font-size:.78rem;color:var(--text-muted);">(RUC)</span></span>
            </label>
        </div>
    </div>

    <!-- ══ PASO 2: Datos del cliente (DNI/RUC) ═══════════════════ -->
    <div class="card mb-16" id="card-datos-cliente" style="display:none;">
        <div class="card-title" id="titulo-datos-cliente"><i class="fa-solid fa-user"></i> Datos del cliente</div>

        <div style="display:flex;gap:8px;margin-bottom:12px;" id="buscador-doc-wrap">
            <div style="flex:1;">
                <label class="form-label" id="label-nro-doc">Número de DNI</label>
                <input type="text" id="input-nro-doc" class="form-control"
                    placeholder="Ingresa el DNI..." maxlength="11" inputmode="numeric"
                    oninput="this.value=this.value.replace(/\D/g,''); onDocumentoInput()">
            </div>
            <div style="display:flex;align-items:flex-end;">
                <button class="btn btn-ghost" id="btn-buscar-doc" onclick="buscarDocumento()" title="Consultar API" style="height:40px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>

        <!-- Estado de búsqueda -->
        <div id="doc-status" style="font-size:.82rem;min-height:20px;margin-bottom:8px;"></div>

        <!-- Campos autocomplete -->
        <div id="campos-cliente" style="display:none;">
            <div class="mb-12">
                <label class="form-label" id="label-nombre-cliente">Nombre completo</label>
                <input type="text" id="input-nombre-cliente" class="form-control" placeholder="Nombre del cliente">
            </div>
            <div id="campos-factura-extra" style="display:none;">
                <div class="mb-12">
                    <label class="form-label">Dirección fiscal</label>
                    <input type="text" id="input-direccion" class="form-control" placeholder="Dirección del cliente">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;" class="mb-12">
                    <div>
                        <label class="form-label">Distrito</label>
                        <input type="text" id="input-distrito" class="form-control" placeholder="Distrito">
                    </div>
                    <div>
                        <label class="form-label">Provincia</label>
                        <input type="text" id="input-provincia" class="form-control" placeholder="Provincia">
                    </div>
                    <div>
                        <label class="form-label">Departamento</label>
                        <input type="text" id="input-departamento" class="form-control" placeholder="Dpto.">
                    </div>
                </div>
            </div>
            <div style="font-size:.75rem;color:var(--text-secondary);margin-top:4px;">
                <i class="fa-solid fa-pencil"></i> Puedes editar los datos antes de continuar.
            </div>
        </div>

    </div>

    <!-- Métodos de pago -->
    <div class="card mb-16">
        <div class="card-title"><i class="fa-solid fa-credit-card"></i> Método(s) de pago</div>
        <div class="metodos-pago">
            <?php
            $metodos = [
                ['efectivo',      'fa-money-bill-wave',  'Efectivo'],
                ['yape',          'fa-mobile-screen',    'Yape / Plin'],
                ['transferencia', 'fa-building-columns', 'Transferencia'],
                ['tarjeta',       'fa-credit-card',      'Tarjeta'],
                ['otro',          'fa-rotate',           'Otro'],
            ];
            foreach ($metodos as [$key, $icon, $label]): ?>
            <div class="metodo-item" id="metodo-<?= $key ?>">
                <div class="metodo-header" onclick="toggleMetodo('<?= $key ?>')">
                    <div class="metodo-check" id="check-<?= $key ?>"><i class="fa-solid fa-check"></i></div>
                    <span class="metodo-icon"><i class="fa-solid <?= $icon ?>"></i></span>
                    <span class="metodo-nombre"><?= $label ?></span>
                </div>
                <div class="metodo-monto-wrap">
                    <label class="form-label" style="font-size:.78rem;">Monto (S/)</label>
                    <input type="number" id="monto-<?= $key ?>" class="form-control"
                        step="0.10" min="0" placeholder="0.00"
                        oninput="calcularDiferencia()">
                    <?php if ($key === 'yape' || $key === 'transferencia'): ?>
                    <input type="text" id="ref-<?= $key ?>" class="form-control mt-8"
                        placeholder="N° de operación (opcional)">
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="diferencia-aviso" id="aviso-diferencia"></div>

        <div class="resumen-totales mt-12">
            <div class="resumen-row"><span>Total del pedido</span><span>S/ <?= number_format($total,2) ?></span></div>
            <div class="resumen-row"><span>Total asignado</span><span id="total-asignado">S/ 0.00</span></div>
            <div class="resumen-row total"><span>Diferencia</span><span id="diferencia-val">S/ <?= number_format($total,2) ?></span></div>
        </div>
    </div>

    <!-- Notas opcionales -->
    <div class="card mb-16">
        <div class="card-title"><i class="fa-solid fa-note-sticky"></i> Notas del cobro (opcional)</div>
        <textarea id="notas-cobro" class="form-control" rows="2" placeholder="Ej: cliente pagó con billete de 50..."></textarea>
    </div>

    <!-- Botón confirmar -->
    <button class="btn btn-exito btn-full btn-lg" id="btn-confirmar-cobro" onclick="confirmarCobro()">
        <i class="fa-solid fa-circle-check"></i> Confirmar cobro
    </button>

</div>

<script>
const TOTAL_PEDIDO = <?= $total ?>;
const PEDIDO_ID    = <?= $pedidoId ?>;
const METODOS      = ['efectivo','yape','transferencia','tarjeta','otro'];
const activos      = new Set();

// ── Selección de tipo de comprobante ──────────────────────────
let tipoComp = 'no'; // 'no' | 'boleta' | 'factura' | 'simple'

const OPCIONES_COMP = ['no', 'simple', 'boleta', 'factura'];

function seleccionarTipoComp(tipo) {
    tipoComp = tipo;
    OPCIONES_COMP.forEach(o => {
        const label = document.getElementById(`opc-${o}-label`);
        const check = document.getElementById(`check-comp-${o}`);
        if (label) {
            label.style.borderColor = o === tipo ? 'var(--primary)' : 'var(--border)';
            label.style.background  = o === tipo ? 'var(--bg-secondary)' : '';
        }
        if (check) {
            check.style.background = o === tipo ? 'var(--primary)' : '';
        }
    });

    const cardCliente = document.getElementById('card-datos-cliente');
    const camposFactura = document.getElementById('campos-factura-extra');
    const labelNroDoc  = document.getElementById('label-nro-doc');
    const inputDoc     = document.getElementById('input-nro-doc');

    if (tipo === 'no' || tipo === 'simple') {
        cardCliente.style.display = 'none';
    } else {
        cardCliente.style.display = '';
        document.getElementById('titulo-datos-cliente').innerHTML =
            `<i class="fa-solid fa-user"></i> Datos para ${tipo === 'boleta' ? 'Boleta (DNI)' : 'Factura (RUC)'}`;

        if (tipo === 'boleta') {
            labelNroDoc.textContent = 'Número de DNI (8 dígitos)';
            inputDoc.placeholder    = 'Ingresa los 8 dígitos del DNI...';
            inputDoc.maxLength      = 8;
            if (camposFactura) camposFactura.style.display = 'none';
        } else {
            labelNroDoc.textContent = 'Número de RUC (11 dígitos)';
            inputDoc.placeholder    = 'Ingresa los 11 dígitos del RUC...';
            inputDoc.maxLength      = 11;
            if (camposFactura) camposFactura.style.display = '';
        }

        // Reset campos
        inputDoc.value = '';
        limpiarCamposCliente();
        document.getElementById('doc-status').innerHTML = '';
        document.getElementById('campos-cliente').style.display = 'none';
    }
}

// Init
seleccionarTipoComp('no');

// ── Autoconsulta de DNI/RUC ───────────────────────────────────
let docTimer = null;
function onDocumentoInput() {
    const val  = document.getElementById('input-nro-doc').value.replace(/\D/g, '');
    const need = tipoComp === 'boleta' ? 8 : 11;
    clearTimeout(docTimer);
    limpiarCamposCliente();
    document.getElementById('campos-cliente').style.display = 'none';
    document.getElementById('doc-status').innerHTML = '';

    if (val.length === need) {
        docTimer = setTimeout(buscarDocumento, 300);
    }
}

async function buscarDocumento() {
    const val  = document.getElementById('input-nro-doc').value.replace(/\D/g, '');
    const tipo = tipoComp === 'boleta' ? 'dni' : 'ruc';
    const need = tipoComp === 'boleta' ? 8 : 11;

    if (val.length !== need) {
        Toast.advertencia(`El ${tipo.toUpperCase()} debe tener ${need} dígitos.`);
        return;
    }

    const status = document.getElementById('doc-status');
    status.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando...';

    try {
        const res  = await fetch(`${BASE_URL}/api/consultar_documento.php?tipo=${tipo}&numero=${val}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const json = await res.json();

        if (json.success && json.data) {
            const d = json.data;
            status.innerHTML = '<i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Datos encontrados. Verifica antes de continuar.';
            document.getElementById('input-nombre-cliente').value = d.nombre || '';

            if (tipo === 'ruc') {
                document.getElementById('input-direccion').value   = d.direccion    || '';
                document.getElementById('input-distrito').value    = d.distrito     || '';
                document.getElementById('input-provincia').value   = d.provincia    || '';
                document.getElementById('input-departamento').value= d.departamento || '';
            }
            document.getElementById('campos-cliente').style.display = '';
        } else {
            // API falló o documento no encontrado → permitir ingreso manual
            status.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i> ${json.message || 'No encontrado.'} Ingresa los datos manualmente.`;
            document.getElementById('campos-cliente').style.display = '';
        }
    } catch(e) {
        status.innerHTML = '<i class="fa-solid fa-wifi" style="color:var(--danger);"></i> Sin conexión a la API. Ingresa los datos manualmente.';
        document.getElementById('campos-cliente').style.display = '';
    }
}

function limpiarCamposCliente() {
    ['input-nombre-cliente','input-direccion','input-distrito','input-provincia','input-departamento']
        .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
}

// ── Métodos de pago ──────────────────────────────────────────
function toggleMetodo(key) {
    const el = document.getElementById(`metodo-${key}`);
    if (activos.has(key)) {
        activos.delete(key);
        el.classList.remove('activo');
        document.getElementById(`monto-${key}`).value = '';
    } else {
        activos.add(key);
        el.classList.add('activo');
        if (activos.size === 1) {
            document.getElementById(`monto-${key}`).value = TOTAL_PEDIDO.toFixed(2);
        }
    }
    calcularDiferencia();
}

function calcularDiferencia() {
    let asignado = 0;
    activos.forEach(key => {
        asignado += parseFloat(document.getElementById(`monto-${key}`).value || 0);
    });
    const diferencia = TOTAL_PEDIDO - asignado;
    document.getElementById('total-asignado').textContent = `S/ ${asignado.toFixed(2)}`;
    document.getElementById('diferencia-val').textContent  = `S/ ${Math.abs(diferencia).toFixed(2)}`;

    const aviso = document.getElementById('aviso-diferencia');
    if (Math.abs(diferencia) > 0.10) {
        aviso.classList.add('show');
        aviso.innerHTML = diferencia > 0
            ? `<i class="fa-solid fa-triangle-exclamation"></i> Faltan S/ ${diferencia.toFixed(2)} por asignar`
            : `<i class="fa-solid fa-circle-info"></i> Hay S/ ${Math.abs(diferencia).toFixed(2)} de vuelto/propina`;
    } else {
        aviso.classList.remove('show');
    }
}

// ── Confirmar cobro ──────────────────────────────────────────
async function confirmarCobro() {
    if (activos.size === 0) { Toast.advertencia('Selecciona al menos un método de pago'); return; }

    // Armar array de pagos
    const pagos = [];
    for (const key of activos) {
        const monto = parseFloat(document.getElementById(`monto-${key}`).value || 0);
        if (monto <= 0) { Toast.advertencia(`Ingresa el monto para: ${key}`); return; }
        const pago = { metodo: key, monto };
        const refEl = document.getElementById(`ref-${key}`);
        if (refEl) pago.referencia = refEl.value.trim();
        pagos.push(pago);
    }

    const diferencia = TOTAL_PEDIDO - pagos.reduce((s, p) => s + p.monto, 0);
    if (Math.abs(diferencia) > 0.10) {
        const ok = confirm(`Hay una diferencia de S/ ${Math.abs(diferencia).toFixed(2)}. ¿Confirmar de todas formas?`);
        if (!ok) return;
    }

    // Validar datos de comprobante si aplica
    let clienteData = null;
    if (tipoComp !== 'no') {
        if (tipoComp !== 'simple') {
            const numDoc       = document.getElementById('input-nro-doc').value.trim().replace(/\D/g,'');
            const nombreCliente= document.getElementById('input-nombre-cliente')?.value.trim() || '';
            const tipoDoc      = tipoComp === 'boleta' ? 'dni' : 'ruc';
            const need         = tipoComp === 'boleta' ? 8 : 11;

            if (numDoc.length !== need) {
                Toast.advertencia(`El ${tipoDoc.toUpperCase()} debe tener ${need} dígitos.`);
                return;
            }
            if (!nombreCliente) {
                Toast.advertencia('Ingresa el nombre del cliente.');
                return;
            }
            clienteData = {
                tipo_documento:  tipoDoc,
                numero_documento: numDoc,
                nombre_cliente:   nombreCliente,
                direccion:        document.getElementById('input-direccion')?.value.trim()    || '',
                distrito:         document.getElementById('input-distrito')?.value.trim()     || '',
                provincia:        document.getElementById('input-provincia')?.value.trim()    || '',
                departamento:     document.getElementById('input-departamento')?.value.trim() || '',
            };
        } else {
            // Para comprobante simple
            clienteData = {
                tipo_documento: null,
                numero_documento: null,
                nombre_cliente: 'Cliente',
            };
        }
    }

    const btn = document.getElementById('btn-confirmar-cobro');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';

    try {
        let comprobanteId = null;

        if (tipoComp !== 'no' && clienteData) {
            // ── Ruta con comprobante ─────────────────────────────────
            const body = {
                pedido_id: PEDIDO_ID,
                tipo:      tipoComp,
                pagos,
                notas: document.getElementById('notas-cobro').value,
                ...clienteData,
            };

            const res  = await fetch(BASE_URL + '/api/guardar_comprobante.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify(body)
            });
            const json = await res.json();

            if (json.success) {
                Toast.exito(`¡${json.message || 'Cobro registrado!'} · ${json.data?.numero_comprobante || ''}`);
                comprobanteId = json.data?.comprobante_id;
                setTimeout(() => {
                    if (comprobanteId) {
                        window.location.href = `${BASE_URL}/roles/atencion/comprobante_view.php?id=${comprobanteId}`;
                    } else {
                        window.location.href = `${BASE_URL}/roles/atencion/dashboard.php`;
                    }
                }, 1200);
            } else {
                Toast.error(json.message || 'Error al procesar el cobro.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirmar cobro';
            }
        } else {
            // ── Ruta sin comprobante (flujo original) ────────────────
            const res  = await fetch(BASE_URL + '/api/cobrar_pedido.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({ pedido_id: PEDIDO_ID, pagos, notas: document.getElementById('notas-cobro').value })
            });
            const json = await res.json();
            if (json.success) {
                Toast.exito('¡Cobro registrado exitosamente!');
                setTimeout(() => { window.location.href = BASE_URL + '/roles/atencion/dashboard.php'; }, 1200);
            } else {
                Toast.error(json.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirmar cobro';
            }
        }
    } catch(e) {
        Toast.error('Error de conexión');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Confirmar cobro';
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
