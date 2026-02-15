# Changelog

## 2026-02-15 - Fase A (roles/permisos + navegación por rol)
- Se implementó configuración central de roles y permisos granulares en `config/access.php`.
- Se agregaron 5 roles objetivo: `owner_manager`, `seller_cashier`, `warehouse`, `accounting`, `technician`.
- Se añadieron aliases de compatibilidad para roles legacy (`admin`, `seller`).
- Se incorporó middleware `permission` para autorización backend por ruta.
- Se aplicaron permisos en rutas de POS, productos, ventas y usuarios.
- Se compartieron props globales de auth/permisos hacia Inertia (`role_key`, `role_label`, `permissions`, `home`, `can_switch_branch`).
- Se actualizó la navegación mobile a base: Inicio + POS + Inventario + Más.
- Se ordenó menú “Más” como: Clientes, Caja, Reportes, Gastos, Bancos (deshabilitados hasta fases siguientes).
- Se actualizó gestión de usuarios para selección de los 5 roles.
- Se añadió migración para normalizar roles legacy en BD.
- Se añadieron tests feature de autorización y shared props.
