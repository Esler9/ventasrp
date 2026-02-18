# Auditoría inicial y plan por fases

## Resumen de auditoría (estado actual)
- **Arquitectura frontend:** Laravel + Inertia + Vue 3 (no SPA API separada).
- **Entradas frontend:** `resources/js/pos-app.js` monta Inertia; `resources/js/app.js` está mínimo.
- **Rutas actuales:** Web monolítica en `routes/web.php`; módulos activos: Login, POS, Productos, Ventas, Usuarios.
- **Auth actual:** Sesión Laravel con login por correo/contraseña o usuario/PIN.
- **Roles existentes antes de Fase A:** `admin` y `seller` sin permisos granulares backend.
- **Navegación móvil existente:** bottom bar con POS, Productos, Ventas, Panel.
- **Dominio ventas/inventario actual:**
  - Venta rápida por 1 producto en POS (`PosController@store`).
  - Descuento de stock inmediato al cerrar venta.
  - `sales`, `sale_items`, `products`, `inventory_movements`, `product_presentations` ya existen.
- **Sistema de temas:** oscuro/claro básico con `useTheme`; sin design tokens configurables por admin para color primario/secundario.

## Gaps y riesgos detectados
- No había autorización granular en backend: riesgo de acceso por URL directa.
- No existía share global de permisos/rol al frontend.
- La navegación no seguía el nuevo esquema `Inicio + POS + Inventario + Más`.
- Varios módulos objetivo (Clientes/CxC, Caja, Gastos, Bancos, Reportes extendidos, Devoluciones, Cotizaciones) aún no existen.
- POS aún incluye flujo de escáner/barcode, mientras la nueva prioridad es búsqueda/autocompletado.
- `README.md` está base de Laravel y no documenta flujo real del proyecto.

## Plan de implementación por fases (PR-style)

### Fase A: Roles/permisos + share frontend + navegación por rol
- [x] Config central de roles/permisos granulares.
- [x] Alias de roles legacy (`admin/seller`) para compatibilidad incremental.
- [x] Middleware backend de permiso por ruta/acción.
- [x] Shared props Inertia con `role_key`, `role_label`, `permissions`, `home`, `can_switch_branch`.
- [x] Bottom bar base: Inicio + POS + Inventario + Más.
- [x] Menú “Más” ordenado: Clientes, Caja, Reportes, Gastos, Bancos.
- [x] Gestión de usuarios adaptada a 5 roles.
- [x] Tests de autorización/props.

### Fase B: Design tokens + theming profesional
- [ ] Tokenizar colores/espaciado/radios/sombras (light/dark desde base).
- [x] Configuración admin de color primario/secundario persistente.
- [ ] Sistema de componentes base (button/input/card/tab/modal/toast/skeleton).

### Fase C: POS ultra rápido
- [x] Rehacer flujo a carrito multi-item con autocompletado debounced.
- [x] Cliente obligatorio con default CF.
- [ ] Pagos mixtos por venta.
- [ ] Restricciones `change_price` por permiso.
- [ ] Series/IMEI (obligatorio/opcional por producto).
- [ ] Cotización/pendiente y conversión 1 clic sin reservar stock.

### Fase D: Caja completa
- [x] Modelo caja/sesiones/movimientos/cierres con arqueo.
- [ ] Asignación de caja a vendedor (máx. 3 por sucursal).
- [x] Totales por método (incluyendo mixtos) y trazabilidad de usuario/fecha.

### Fase E: Clientes + CxC
- [ ] Entidad clientes completa + límite de crédito.
- [ ] Ventas a crédito, abonos, historial y alertas de límite.
- [ ] Integración de CxC con POS.

### Fase F: Inventario mobile cards + detalle + series
- [ ] Rediseño principal a cards mobile-first.
- [ ] Detalle producto con stock/precio/acciones arriba.
- [ ] Gestión series/IMEI en inventario.
- [ ] Preparación de estructura para multi-almacén futuro.

### Fase G: Gastos y Bancos
- [ ] Gastos rápidos (monto/categoría/método/nota).
- [ ] Bancos simples (movimientos + balance).

### Fase H: Anulaciones y devoluciones
- [ ] Flujo con motivo obligatorio.
- [ ] Efectos correctos en caja/stock + trazabilidad.

### Fase I: Dashboard y reportes
- [ ] KPI Dueño/Gerente (ventas hoy, utilidad hoy, ventas mes, saldo bancos).
- [ ] Pantalla de reportes con filtros/cards/list y export si aplica.

## Checklist transversal por fase
- [ ] Migraciones y modelo de datos.
- [ ] Validaciones frontend y backend.
- [ ] Authorization backend (policy/gate/middleware).
- [ ] UI responsive mobile-first.
- [ ] Tests (feature + unit + frontend si existen).
- [ ] `php artisan test` + formatter/linter.
- [ ] Nota en `CHANGELOG.md`.
