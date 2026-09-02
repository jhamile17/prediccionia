<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'PrediccionIA')
    </title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/topbar.css') }}">

    {{-- Bootstrap Icons --}}
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    @stack('styles')
</head>

<body>

<div class="app-container">

    {{-- =========================================================
         SIDEBAR
    ========================================================== --}}

    <aside class="sidebar">

        {{-- LOGO --}}
        <div class="sidebar-header">

            <div class="brand-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>

            <div class="brand-text">
                <span>Prediccion</span><strong>IA</strong>
            </div>

        </div>


        {{-- =====================================================
             PRINCIPAL
        ====================================================== --}}

        <div class="menu-section">

            <span class="menu-title">
                PRINCIPAL
            </span>

            {{-- Dashboard --}}
            <a
                href="{{ route('dashboard') }}"
                class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            >
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>

        </div>


        {{-- =====================================================
             GESTIÓN
        ====================================================== --}}

        <div class="menu-section">

            <span class="menu-title">
                GESTIÓN
            </span>

            {{-- Productos --}}
            <a
                href="{{ route('productos.index') }}"
                class="menu-item {{ request()->routeIs('productos.*') ? 'active' : '' }}"
            >
                <i class="bi bi-box-seam"></i>
                <span>Productos</span>
            </a>

            {{-- Categorías --}}
            <a
                href="{{ route('categorias.index') }}"
                class="menu-item {{ request()->routeIs('categorias.*') ? 'active' : '' }}"
            >
                <i class="bi bi-tags"></i>
                <span>Categorías</span>
            </a>


            {{-- Inventario --}}
            <a
                href="{{ route('inventario.index') }}"
                class="menu-item {{ request()->routeIs('inventario.*') ? 'active' : '' }}"
            >
                <i class="bi bi-clipboard-data"></i>
                <span>Inventario</span>
            </a>

        </div>


        {{-- =====================================================
             INTELIGENCIA
        ====================================================== --}}

        <div class="menu-section">

            <span class="menu-title">
                INTELIGENCIA
            </span>

            {{-- Predicción mensual --}}
            <a 
                href="{{ route('prediccion.mensual') }}" 
                class="menu-item {{ request()->routeIs('prediccion.*') ? 'active' : '' }}"
            >
                <i class="bi bi-cpu"></i>
                <span>Predicción mensual</span>
            </a>


            {{-- Análisis de demanda --}}
            {{-- Ruta todavía no creada --}}
            <a
                href="{{ route('analisis.index') }}"
                class="menu-item {{ request()->routeIs('analisis.*') ? 'active' : '' }}"
            >
                <i class="bi bi-bar-chart-line"></i>
                <span>Análisis de demanda</span>
            </a>

            {{-- Alertas --}}
            {{-- Ruta todavía no creada --}}
            <a
                href="{{ route('alertas.index') }}"
                class="menu-item {{ request()->routeIs('alertas.*') ? 'active' : '' }}"
            >
                <i class="bi bi-exclamation-triangle"></i>
                <span>Alertas</span>
            </a>

        </div>


        {{-- =====================================================
             REPORTES
        ====================================================== --}}

        <div class="menu-section">

            <span class="menu-title">
                REPORTES
            </span>

            {{-- Ruta todavía no creada --}}
            <a
                href="{{ route('reportes.index') }}"
                class="menu-item {{ request()->routeIs('reportes.*') ? 'active' : '' }}"
            >
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Reportes</span>
            </a>

        </div>


        {{-- =====================================================
             CONFIGURACIÓN
        ====================================================== --}}

        <div class="sidebar-bottom">

            {{-- CONFIGURACIÓN --}}
            <a
                href="{{ route('configuracion.index') }}"
                class="menu-item {{ request()->routeIs('configuracion.*') ? 'active' : '' }}"
            >
                <i class="bi bi-gear"></i>
                <span>Configuración</span>
            </a>

            {{-- CERRAR SESIÓN --}}
            <form
                action="{{ route('logout') }}"
                method="POST"
                class="logout-form"
            >
                @csrf

                <button
                    type="submit"
                    class="menu-item logout-button"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>

        </div>

    </aside>


    {{-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== --}}

    <div class="main-container">


        {{-- =====================================================
             TOPBAR
        ====================================================== --}}

        <header class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="sidebar-toggle"
                    aria-label="Abrir menú"
                >
                    <i class="bi bi-list"></i>
                </button>

            </div>


            <div class="topbar-right">

                {{-- NOTIFICACIONES --}}
                <button
                    type="button"
                    class="notification-button"
                    aria-label="Notificaciones"
                >
                    <i class="bi bi-bell"></i>

                    <span class="notification-dot"></span>
                </button>


                {{-- USUARIO --}}
                <div class="user-profile">

                    <div class="user-avatar">
                        A
                    </div>

                    <div class="user-info">

                        <strong>
                            Administrador
                        </strong>

                        <span>
                            Administrador
                        </span>

                    </div>

                    <i class="bi bi-chevron-down user-arrow"></i>

                </div>

            </div>

        </header>


        {{-- =====================================================
             CONTENIDO DE LA VISTA
        ====================================================== --}}

        <main class="page-content">

            @yield('content')

        </main>

    </div>

</div>


{{-- =========================================================
     SCRIPTS
========================================================== --}}

@stack('scripts')

</body>
</html>