<div align="center">

# 🚀 RESTAURANT SAAS PRO
**Sistema Avanzado de Gestión de Comandas y Facturación Electrónica**

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com/)
[![License](https://img.shields.io/badge/Licencia-Privada-FF4B4B?style=for-the-badge&logo=github&logoColor=white)](#)
[![UI/UX](https://img.shields.io/badge/UI/UX-Pro_Max-FFD700?style=for-the-badge&logo=figma&logoColor=black)](#)

*Una plataforma en la nube (SaaS) ultrarrápida, escalable y con una interfaz de usuario minimalista y futurista, diseñada para revolucionar la gestión de restaurantes, cevicherías y afines.*

</div>

---

## 🌟 Visión General

**Restaurant SaaS Pro** es más que un simple punto de venta (POS). Es un ecosistema completo diseñado bajo la filosofía **"UI/UX Pro Max"**, con layouts tipo *Bento-grid*, tipografías de alta gama, y una paleta monocromática cálida que proporciona una experiencia de usuario utilitaria, elegante y premium.

Esta plataforma multi-tenant permite la gestión de múltiples restaurantes, con integración directa de facturación electrónica, control de comandas en tiempo real, administración de turnos y protección avanzada mediante variables de entorno y prevención de ataques XSS.

---

## ⚡ Funcionalidades Principales

### 👑 Arquitectura Multi-Tenant & Roles
- **SuperAdmin:** Control total de la plataforma y restaurantes suscritos.
- **Admin:** Gestión del negocio, configuración de marca, menú, y reportes de ventas.
- **Atención (Caja/Mozos):** Toma de pedidos optimizada, cobro multicanal y apertura/cierre de turnos de caja.
- **Cocina:** Pantalla interactiva en tiempo real para visualización y despacho de comandas.

### 💳 Módulo POS y Gestión de Mesas
- Panel visual del estado de las mesas (`Libre`, `Ocupada`, `Reservada`).
- Pedidos clasificados para consumir `Aquí` o para `Llevar`.
- División de pagos inteligente con soporte multicanal: **Efectivo, Yape, Transferencia, Tarjeta y Otros**.

### 🧾 Módulo de Facturación Electrónica
- Emisión de **Boletas y Facturas** integradas a la SUNAT.
- Protección y gestión segura del **Token de API** mediante archivos `.env`.
- Tickets en formato PDF y térmico con estructura legal validada.
- Respaldo atómico de cobros con JSON Snapshots (para reimpresión inmutable).

### 🧑‍🍳 Monitor de Cocina (KDS) en Tiempo Real
- Cambio de estados de platillos con alertas visuales: `Pendiente` ➜ `En Preparación` ➜ `Listo` ➜ `Entregado`.
- Alertas sonoras y notificaciones visuales responsive.
- Optimización de tiempos y orden de llegada.

### 🎨 Personalización de Branding por Restaurante
- Cada restaurante puede configurar su propio **Nombre Comercial, RUC, Logo, Teléfono y Mensaje de Pie de Página**.
- Todo el entorno visual se adapta dinámicamente a la identidad de la marca.

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología | Descripción |
| :--- | :--- | :--- |
| **Backend** | `PHP 8.x` | Lógica de negocio orientada a seguridad y rendimiento. |
| **Base de Datos** | `MySQL 8.x` | Base de datos relacional con índices optimizados. |
| **Frontend UI** | `HTML5 + CSS3 Vainilla` | Diseño minimalista, glassmorphism, bento-grids y micro-animaciones. |
| **Integraciones** | `APIs REST` | Conexión con APIS Perú para comprobantes de pago. |
| **Seguridad** | `Dotenv (.env)` | Encriptación de contraseñas Bcrypt y protección XSS. |

---

## 📂 Estructura del Proyecto

```text
📁 system-restaurant/
├── 📁 api/           # Endpoints de facturación y consultas externas
├── 📁 assets/        # CSS, JS, Fuentes e Imágenes (UI/UX)
├── 📁 auth/          # Módulo de autenticación segura
├── 📁 config/        # Conexión a BD y carga de Dotenv
├── 📁 Database/      # Scripts SQL (db_completa.sql unificada)
├── 📁 includes/      # Componentes reutilizables (Headers, Footers)
├── 📁 roles/         # Vistas específicas por cada rol (Admin, Cocina, Atención)
├── 📄 .env.example   # Plantilla de variables de entorno
└── 📄 index.php      # Puerta de entrada y enrutador principal
```

---

## 🚀 Guía de Instalación Rápida

1. **Clonar el Repositorio**
   ```bash
   git clone https://github.com/tu-usuario/system-restaurant.git
   cd system-restaurant
   ```

2. **Configuración del Entorno**
   Copia el archivo `.env.example` y renómbralo a `.env`.
   ```bash
   cp .env.example .env
   ```
   Edita el archivo `.env` y configura tus accesos a la base de datos y tu API Token de facturación.

3. **Base de Datos**
   Importa el archivo maestro de instalación en tu gestor (ej. phpMyAdmin):
   ```text
   Ruta: Database/db_completa.sql
   ```
   *Nota: Este archivo creará las tablas, relaciones, la configuración inicial y optimizará los índices de búsqueda automáticamente.*

4. **Credenciales de Acceso Demo**
   - **Atención (Caja):** `atencion` | Pass: `atencion`
   - **Cocina:** `cocina` | Pass: `cocina`
   - **Administrador:** `admin` | Pass: `admin`
   - **Super Admin:** `reiner` | Pass: `reiner`

---

## 🛡️ Seguridad y Buenas Prácticas

- **No subas el archivo `.env` a GitHub.** El archivo `.gitignore` ya está configurado para omitirlo.
- Las vistas están sanitizadas utilizando `htmlspecialchars()` para prevenir inyección XSS.
- Contraseñas almacenadas de forma unidireccional utilizando **Bcrypt**.
- Consultas a BD preparadas y optimizadas (PDO/MySQLi binding) para prevenir SQL Injections.

---

<div align="center">

Hecho con 💡 y ☕ por **Reiner Jiménez / R.DEV**
<br>
*Optimizando el futuro gastronómico línea por línea.*

</div>
