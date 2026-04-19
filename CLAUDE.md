.# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full development environment (Laravel + Queue + Logs + Vite, all concurrent)
composer dev

# Individual services
php artisan serve          # Laravel HTTP server
npm run dev                # Vite HMR dev server
php artisan queue:listen   # Background job worker
php artisan pail           # Log streaming

# Build & setup
npm run build              # Production Vite build
composer setup             # First-time setup: install, key, migrate, build

# Testing & code quality
composer test              # PHPUnit tests
php artisan pint           # Laravel code formatter (pint.json config)
```

## Architecture

**VentasRP** is a POS and sales management system for retail and restaurant businesses. Built with Laravel 12 + Vue 3 + Inertia.js (no separate API — all data flows through Inertia page props and form submissions).

### Business Modes

The app has two modes controlled by `AppSetting::current()->business_mode`:
- `minorista` — retail POS: direct product sales, cart-based checkout
- `restaurante` — restaurant POS: table/delivery management, kitchen workflow, account-based ordering

`PosController` branches logic based on this setting and delegates to `RestaurantPosController` for restaurant mode.

### Frontend Entry Points

Two separate Vite entry points:
- `resources/js/app.js` — admin, dashboard, products, reports, settings
- `resources/js/pos-app.js` — POS interface (both retail and restaurant modes)

Pages live in `resources/js/Pages/` organized by feature. Key sub-trees:
- `Pages/Pos/` — POS views (Restaurant.vue, RestaurantWorkspace.vue, Kitchen.vue, Delivery.vue)
- `Pages/Admin/` — dashboard, cash, expenses, banks, users

### Access Control

Custom role-based permission system defined in `config/access.php`. Five roles: `owner_manager`, `seller_cashier`, `warehouse`, `accounting`, `technician`. Protected routes use `middleware('permission:permission.name')`. No third-party ACL package — it's fully custom.

### Restaurant Order Flow

Restaurant operations are account-based (not direct sales):
1. Table or delivery rider → `RestaurantAccount`
2. Items added → `RestaurantAccountItem` (status: draft → sent to kitchen → completed/cancelled)
3. Account closed → converts to `Sale` with `SalePayment` records

### Key Models

- `AppSetting` — singleton config (business mode, currency, brand colors, surcharge config)
- `RestaurantTable` — tables with service types (dine-in, takeaway)
- `CashSession` — tracks open/close of cash registers
- `Sale` / `SaleItem` / `SalePayment` — core sales records
- `Product` with optional `ProductRecipe` + `ProductRecipeItems` (for restaurant ingredient tracking)

### Database

SQLite by default (`database/database.sqlite`). Sessions, cache, and queues all use the database driver. Configure via `.env` (`DB_CONNECTION`, `DB_DATABASE`).

### Branding

Dynamic CSS variables are applied from `AppSetting` brand colors on each Inertia page update — no hardcoded primary colors in CSS.
