<div class="app-header-menu app-header-mobile-drawer align-items-start align-items-lg-center w-100" data-kt-drawer="true"
    data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
    data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
    data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
    <div class="menu menu-rounded menu-active-bg menu-state-primary menu-column menu-lg-row menu-title-gray-700 menu-icon-gray-500 menu-arrow-gray-500 menu-bullet-gray-500 my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
        id="kt_app_header_menu" data-kt-menu="true">
        <div
            class="menu-item menu-here-bg me-0 me-lg-2 menu-hover-bg menu-hover-bg-warning {{ request()->routeIs('dashboard') ? 'here show ' : '' }}">
            <a href="{{ route('dashboard') }}"
                class="menu-link px-4 {{ request()->routeIs('dashboard') ? 'active ' : '' }}">

                <span class="menu-title">Dashboards</span>
            </a>
        </div>
        {{-- DATA MASTER: Superadmin + admin --}}
        @can('view_data_master')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span
                    class="menu-link py-3  {{ request()->routeIs('categories.index', 'menus.index', 'tables.index', 'promos.index') ? 'active ' : '' }}">
                    <span class="menu-title">Data Master</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('categories.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('categories.index') }}">
                            <span class="menu-icon"><i class="ki-outline ki-category fs-2"></i></span>
                            <span class="menu-title">Kategori Menu</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('menus.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3" href="{{ route('menus.index') }}"> <span class="menu-icon"><i
                                    class="ki-outline ki-coffee fs-2"></i></span>
                            <span class="menu-title">Menu Makanan & Minuman</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('tables.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3" href="{{ route('tables.index') }}">
                            <span class="menu-icon"><i class="ki-outline ki-abstract-14 fs-2"></i></span>
                            <span class="menu-title">Manajemen Meja</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('promos.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3" href="{{ route('promos.index') }}">
                            <span class="menu-icon"><i class="ki-outline ki-discount fs-2"></i></span>
                            <span class="menu-title">Promo & Diskon</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- FINANCE: Superadmin + admin + kasir --}}
        @can('view_finance')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span class="menu-link py-3  {{ request()->routeIs('expenses.index') ? 'active ' : '' }}">
                    <span class="menu-title">Finance</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('expenses.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('expenses.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Expenses Management</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- REPORT: Superadmin + admin + kasir --}}
        @can('view_report')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span
                    class="menu-link py-3  {{ request()->routeIs('reports.sales.index', 'reports.items.index') ? 'active ' : '' }}">
                    <span class="menu-title">Report</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('reports.sales.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('reports.sales.index') }}"> <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Sales Report</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('reports.items.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('reports.items.index') }}"> <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Sales Items Report</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- RESOURCES: Superadmin only --}}
        @can('view_resources')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span class="menu-link py-3  {{ request()->routeIs('users.index', 'roles.index') ? 'active ' : '' }}">
                    <span class="menu-title">Resources</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('users.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('users.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">User Management</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('roles.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3" href="{{ route('roles.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-code fs-2"></i>
                            </span>
                            <span class="menu-title">Role Management</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan


        <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
            class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
            <span class="menu-link py-3  {{ request()->routeIs('log-activity.index') ? 'active ' : '' }}">
                <span class="menu-title">Help</span>
                <span class="menu-arrow d-lg-none">
                </span>
            </span>
            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px">
                <div class="menu-item {{ request()->routeIs('log-activity.index') ? 'here show ' : '' }}">
                    <a class="menu-link py-3 " href="{{ route('log-activity.index') }}">
                        <span class="menu-icon">
                            <i class="ki-outline ki-rocket fs-2"></i>
                        </span>
                        <span class="menu-title">Log Activity</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
