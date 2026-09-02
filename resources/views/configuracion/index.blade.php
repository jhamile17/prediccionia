@extends('layouts.app')

@section('title', 'Configuración | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/configuracion.css') }}">
@endpush

@section('content')

<div class="settings-page">

    {{-- HEADER --}}
    <div class="page-header">
        <div>
            <div class="page-kicker">ADMINISTRACIÓN DEL SISTEMA</div>

            <h1>Configuración</h1>

            <p>
                Administra las preferencias y parámetros generales
                del sistema.
            </p>
        </div>
    </div>


    {{-- CONTENIDO --}}
    <div class="settings-layout">

        {{-- MENÚ LATERAL --}}
        <aside class="settings-menu">

            <div class="settings-menu-title">
                CONFIGURACIÓN
            </div>

            <button
                type="button"
                class="settings-menu-item active"
            >
                <i class="bi bi-person-circle"></i>

                <span>
                    <strong>Perfil</strong>
                    <small>Información del administrador</small>
                </span>
            </button>

            <button
                type="button"
                class="settings-menu-item"
            >
                <i class="bi bi-shield-lock"></i>

                <span>
                    <strong>Seguridad</strong>
                    <small>Contraseña y acceso</small>
                </span>
            </button>

            <button
                type="button"
                class="settings-menu-item"
            >
                <i class="bi bi-bell"></i>

                <span>
                    <strong>Notificaciones</strong>
                    <small>Alertas del sistema</small>
                </span>
            </button>

            <button
                type="button"
                class="settings-menu-item"
            >
                <i class="bi bi-sliders"></i>

                <span>
                    <strong>Preferencias</strong>
                    <small>Opciones generales</small>
                </span>
            </button>

        </aside>


        {{-- PANEL PRINCIPAL --}}
        <section class="settings-content">

            {{-- PERFIL --}}
            <div class="settings-card">

                <div class="settings-card-header">

                    <div>
                        <h2>Información del perfil</h2>

                        <p>
                            Consulta la información de la cuenta
                            administradora.
                        </p>
                    </div>

                    <div class="settings-card-icon">
                        <i class="bi bi-person"></i>
                    </div>

                </div>


                <div class="profile-header">

                    <div class="profile-avatar">
                        A
                    </div>

                    <div class="profile-name">

                        <h3>Administrador</h3>

                        <span>
                            Administrador del sistema
                        </span>

                    </div>

                </div>


                <div class="settings-form-grid">

                    <div class="settings-field">

                        <label for="nombre">
                            Nombre
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            value="Administrador"
                            disabled
                        >

                    </div>


                    <div class="settings-field">

                        <label for="correo">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            value="admin@sistemaprediccion.com"
                            disabled
                        >

                    </div>


                    <div class="settings-field">

                        <label for="rol">
                            Rol
                        </label>

                        <input
                            type="text"
                            id="rol"
                            value="Administrador"
                            disabled
                        >

                    </div>


                    <div class="settings-field">

                        <label for="estado">
                            Estado de cuenta
                        </label>

                        <div class="account-status">

                            <span class="status-dot"></span>

                            Activa

                        </div>

                    </div>

                </div>

            </div>


            {{-- SEGURIDAD --}}
            <div class="settings-card">

                <div class="settings-card-header">

                    <div>
                        <h2>Seguridad</h2>

                        <p>
                            Configura las opciones relacionadas
                            con el acceso al sistema.
                        </p>
                    </div>

                    <div class="settings-card-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>

                </div>


                <div class="security-option">

                    <div class="security-option-icon">
                        <i class="bi bi-key"></i>
                    </div>

                    <div class="security-option-info">

                        <strong>
                            Contraseña
                        </strong>

                        <span>
                            Cambia la contraseña de acceso
                            a tu cuenta.
                        </span>

                    </div>

                    <button
                        type="button"
                        class="settings-action"
                    >
                        Cambiar
                    </button>

                </div>


                <div class="security-option">

                    <div class="security-option-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <div class="security-option-info">

                        <strong>
                            Tiempo de sesión
                        </strong>

                        <span>
                            Configura el tiempo de inactividad
                            antes de cerrar la sesión.
                        </span>

                    </div>

                    <span class="security-value">
                        30 minutos
                    </span>

                </div>

            </div>


            {{-- NOTIFICACIONES --}}
            <div class="settings-card">

                <div class="settings-card-header">

                    <div>
                        <h2>Notificaciones</h2>

                        <p>
                            Selecciona qué alertas deseas recibir
                            del sistema.
                        </p>
                    </div>

                    <div class="settings-card-icon">
                        <i class="bi bi-bell"></i>
                    </div>

                </div>


                <div class="notification-option">

                    <div>

                        <strong>
                            Alertas de stock crítico
                        </strong>

                        <span>
                            Recibir avisos cuando un producto
                            alcance un nivel crítico.
                        </span>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="notification-option">

                    <div>

                        <strong>
                            Recomendaciones de reposición
                        </strong>

                        <span>
                            Mostrar avisos sobre productos que
                            requieren reposición.
                        </span>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                            checked
                        >

                        <span class="slider"></span>

                    </label>

                </div>


                <div class="notification-option">

                    <div>

                        <strong>
                            Actualizaciones del sistema
                        </strong>

                        <span>
                            Recibir información sobre procesos
                            y actualizaciones.
                        </span>

                    </div>

                    <label class="switch">

                        <input
                            type="checkbox"
                        >

                        <span class="slider"></span>

                    </label>

                </div>

            </div>


            {{-- INFORMACIÓN --}}
            <div class="settings-info">

                <div class="settings-info-icon">
                    <i class="bi bi-info-circle"></i>
                </div>

                <div>

                    <strong>
                        Configuración del sistema
                    </strong>

                    <p>
                        Algunas opciones se encuentran disponibles
                        únicamente cuando se conecten los módulos
                        correspondientes del sistema.
                    </p>

                </div>

            </div>

        </section>

    </div>

</div>

@endsection