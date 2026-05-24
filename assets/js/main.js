/**
 * main.js — Utilidades globales
 * Sistema SaaS Restaurante | R.DEV
 */

/* ── TOAST NOTIFICATIONS ─────────────────────────────────────── */
const Toast = {
    container: null,

    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    show(mensaje, tipo = 'info', duracion = 3500) {
        const iconMap = {
            exito:      'fa-circle-check',
            error:      'fa-circle-xmark',
            advertencia:'fa-triangle-exclamation',
            info:       'fa-circle-info'
        };
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        toast.innerHTML = `<i class="fa-solid ${iconMap[tipo] || 'fa-bell'}"></i> <span>${mensaje}</span>`;
        this.container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('saliendo');
            setTimeout(() => toast.remove(), 350);
        }, duracion);
    },

    exito(msg)       { this.show(msg, 'exito'); },
    error(msg)       { this.show(msg, 'error', 5000); },
    advertencia(msg) { this.show(msg, 'advertencia', 4000); },
};

/* ── MODAL ──────────────────────────────────────────────────── */
const Modal = {
    abrir(id) {
        const overlay = document.getElementById(id);
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    },
    cerrar(id) {
        const overlay = document.getElementById(id);
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    },
    cerrarTodos() {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
        document.body.style.overflow = '';
    }
};

/* Cerrar modal clickeando el overlay */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        Modal.cerrarTodos();
    }
});

/* ── LOADING OVERLAY ─────────────────────────────────────────── */
const Loading = {
    overlay: null,
    init() {
        this.overlay = document.getElementById('loading-overlay');
        if (!this.overlay) {
            this.overlay = document.createElement('div');
            this.overlay.id = 'loading-overlay';
            this.overlay.className = 'loading-overlay';
            this.overlay.innerHTML = '<div class="spinner"></div><p>Procesando…</p>';
            document.body.appendChild(this.overlay);
        }
    },
    show() { this.overlay.classList.add('show'); },
    hide() { this.overlay.classList.remove('show'); }
};

/* ── POLLING (tiempo real) ───────────────────────────────────── */
function iniciarPolling(endpoint, callback, intervalo = 15000) {
    // Ejecutar inmediatamente
    fetch(endpoint)
        .then(async r => {
            if (!r.ok) throw new Error(`HTTP error ${r.status}`);
            const text = await r.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("NO JSON:", text);
                throw new Error("Invalid JSON: " + text.substring(0,20));
            }
        })
        .then(data => { if (data.success) callback(data.data); })
        .catch(e => { console.warn(e); });

    return setInterval(() => {
        fetch(endpoint)
            .then(async r => {
                if (!r.ok) throw new Error(`HTTP error ${r.status}`);
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error("Invalid JSON: " + text.substring(0,20));
                }
            })
            .then(data => { if (data.success) callback(data.data); })
            .catch(e => { console.warn(e); });
    }, intervalo);
}

/* ── OPCIONES SECUENCIALES ───────────────────────────────────── */
/**
 * Muestra modales secuenciales para platos con opciones.
 * Retorna una promesa que resuelve al array de selecciones.
 */
async function mostrarOpcionesSecuenciales(productoId, nombreProducto) {
    const res = await fetch(`${window.BASE_URL}/api/get_opciones.php?producto_id=${productoId}`);
    const json = await res.json();
    if (!json.success || !json.data || json.data.length === 0) return [];

    const selecciones = [];
    for (const grupo of json.data) {
        const valorId = await abrirModalOpcion(grupo, nombreProducto, json.data.indexOf(grupo) + 1, json.data.length);
        if (valorId === null) return null; // usuario canceló
        selecciones.push({ grupo_id: grupo.id, valor_id: valorId });
    }
    return selecciones;
}

function abrirModalOpcion(grupo, nombreProducto, paso, total) {
    return new Promise((resolve) => {
        const overlay   = document.getElementById('modal-opciones');
        const titulo    = document.getElementById('modal-opciones-titulo');
        const subtitulo = document.getElementById('modal-opciones-subtitulo');
        const lista     = document.getElementById('modal-opciones-lista');
        const btnConf   = document.getElementById('modal-opciones-confirmar');
        const btnCan    = document.getElementById('modal-opciones-cancelar');

        titulo.textContent    = grupo.nombre;
        subtitulo.textContent = `${nombreProducto} · Paso ${paso} de ${total}`;
        lista.innerHTML       = '';

        let valorSeleccionado = null;

        grupo.valores.forEach(val => {
            const item = document.createElement('div');
            item.className = 'opcion-item';
            item.dataset.id = val.id;
            item.innerHTML = `
                <div class="opcion-radio"></div>
                <span class="opcion-texto">${val.valor}</span>
            `;
            item.addEventListener('click', () => {
                lista.querySelectorAll('.opcion-item').forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                valorSeleccionado = val.id;
            });
            lista.appendChild(item);
        });

        btnConf.onclick = () => {
            if (!valorSeleccionado) {
                Toast.advertencia('Selecciona una opción para continuar');
                return;
            }
            Modal.cerrar('modal-opciones');
            resolve(valorSeleccionado);
        };

        btnCan.onclick = () => {
            Modal.cerrar('modal-opciones');
            resolve(null);
        };

        Modal.abrir('modal-opciones');
    });
}

/* ── FORMATEAR MONEDA ────────────────────────────────────────── */
function formatearSol(monto) {
    return 'S/ ' + parseFloat(monto || 0).toFixed(2);
}

/* ── TIEMPO TRANSCURRIDO ─────────────────────────────────────── */
function tiempoTranscurrido(fechaStr) {
    const ahora  = new Date();
    const fecha  = new Date(fechaStr);
    const minutos = Math.floor((ahora - fecha) / 60000);
    if (minutos < 1)  return 'Ahora';
    if (minutos < 60) return `${minutos} min`;
    const horas = Math.floor(minutos / 60);
    return `${horas}h ${minutos % 60}m`;
}

/* ── CONFIRMACIÓN ────────────────────────────────────────────── */
/**
 * @param {string}   mensaje    - Texto a mostrar
 * @param {Function} callback   - Se ejecuta al confirmar
 * @param {Function} [onCancel] - Se ejecuta al cancelar (opcional)
 */
function confirmar(mensaje, callback, onCancel) {
    const overlay = document.getElementById('modal-confirmar');
    if (!overlay) {
        if (window.confirm(mensaje)) callback();
        else if (onCancel) onCancel();
        return;
    }
    document.getElementById('modal-confirmar-msg').textContent = mensaje;

    document.getElementById('modal-confirmar-btn').onclick = () => {
        Modal.cerrar('modal-confirmar');
        callback();
    };

    const btnCan = document.querySelector('#modal-confirmar .btn-ghost');
    if (btnCan) {
        btnCan.onclick = () => {
            Modal.cerrar('modal-confirmar');
            if (onCancel) onCancel();
        };
    }

    Modal.abrir('modal-confirmar');
}

/* ── SIDEBAR ACTIVO ─────────────────────────────────────────── */
function marcarMenuActivo() {
    const url = window.location.pathname;
    document.querySelectorAll('.sidebar-menu a, .mobile-nav a').forEach(a => {
        if (a.href && a.href.includes(url.split('/').pop())) {
            a.classList.add('active');
        }
    });
}

/* ── INIT ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    Toast.init();
    Loading.init();
    marcarMenuActivo();

    // Inyectar botón hamburguesa y lógica del menú lateral
    const navbar  = document.querySelector('.navbar');
    const sidebar = document.querySelector('.sidebar');
    if (navbar && sidebar) {
        const brand = navbar.querySelector('.navbar-brand');
        if (brand) {
            const ham = document.createElement('button');
            ham.className = 'btn-hamburguesa';
            ham.id = 'btn-hamburguesa';
            ham.title = 'Menú';
            ham.setAttribute('aria-label', 'Abrir menú lateral');
            ham.innerHTML = '<i class="fa-solid fa-bars"></i>';
            brand.insertBefore(ham, brand.firstChild);

            let overlay = document.getElementById('sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.id = 'sidebar-overlay';
                document.body.appendChild(overlay);
            }

            ham.addEventListener('click', () => toggleSidebar());
            overlay.addEventListener('click', () => toggleSidebar(false));

            function toggleSidebar(force) {
                if (window.innerWidth > 860) {
                    const collapsed = (force === false) ? true : (force === true ? false : !sidebar.classList.contains('collapsed'));
                    sidebar.classList.toggle('collapsed', collapsed);
                    // Forzar que no tenga la clase abierto para evitar inconsistencias si cambia de tamaño
                    sidebar.classList.remove('abierto');
                    overlay.classList.remove('activo');
                } else {
                    const open = (force === undefined) ? !sidebar.classList.contains('abierto') : force;
                    sidebar.classList.toggle('abierto', open);
                    overlay.classList.toggle('activo', open);
                    ham.innerHTML = open
                        ? '<i class="fa-solid fa-xmark"></i>'
                        : '<i class="fa-solid fa-bars"></i>';
                }
            }
        }
    }
});
