<div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur md:hidden">
    <div class="mx-auto flex max-w-md items-center justify-around px-4 py-3 text-xs text-gray-700">
        <a href="/pos" class="flex flex-col items-center gap-1 {{ request()->is('pos') ? 'text-amber-500' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M5 7l1 12h12l1-12M9 11v2m6-2v2" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2" />
            </svg>
            <span>POS</span>
        </a>
        <a href="/admin/products" class="flex flex-col items-center gap-1 {{ request()->is('admin/products*') ? 'text-amber-500' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8-4 8 4m-14 0v10l6 3m0-13 6-3v10l-6 3m0-13v13" />
            </svg>
            <span>Productos</span>
        </a>
        <a href="/admin/sales" class="flex flex-col items-center gap-1 {{ request()->is('admin/sales*') ? 'text-amber-500' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v4H4V7Zm0 6h16v4H4v-4Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 9h.01M7 15h.01m4-6h6M11 15h6" />
            </svg>
            <span>Ventas</span>
        </a>
        <a href="/admin" class="flex flex-col items-center gap-1 {{ request()->is('admin') ? 'text-amber-500' : '' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m-8-8h16" />
            </svg>
            <span>Panel</span>
        </a>
    </div>
</div>
