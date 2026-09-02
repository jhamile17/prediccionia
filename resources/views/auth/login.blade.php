<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Iniciar sesión | PrediccionIA</title>

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;

            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef2ff 0%,
                    #f8fafc 50%,
                    #eef2ff 100%
                );

            color: #172033;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;

            box-shadow:
                0 20px 50px rgba(15, 23, 42, 0.10);

            border: 1px solid #e5e7eb;
        }

        .login-logo {
            width: 58px;
            height: 58px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin: 0 auto 20px;

            border-radius: 15px;

            background: #4f46e5;
            color: #ffffff;

            font-size: 26px;
        }

        .login-title {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-subtitle {
            text-align: center;
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;

            font-size: 14px;
            font-weight: 600;
            color: #172033;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;

            transform: translateY(-50%);

            color: #94a3b8;
            font-size: 17px;

            pointer-events: none;
        }

        .form-input {
            width: 100%;

            height: 48px;

            padding: 0 15px 0 44px;

            border: 1px solid #e5e7eb;
            border-radius: 10px;

            outline: none;

            font-size: 14px;
            color: #172033;

            background: #ffffff;

            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .form-input:focus {
            border-color: #4f46e5;

            box-shadow:
                0 0 0 3px rgba(79, 70, 229, .10);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .login-button {
            width: 100%;
            height: 48px;

            border: none;
            border-radius: 10px;

            background: #4f46e5;
            color: #ffffff;

            font-size: 14px;
            font-weight: 600;

            cursor: pointer;

            transition:
                background .2s ease,
                transform .15s ease;
        }

        .login-button:hover {
            background: #4338ca;
        }

        .login-button:active {
            transform: scale(.98);
        }

        .alert {
            margin-bottom: 20px;

            padding: 12px 14px;

            border-radius: 10px;

            font-size: 13px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .login-footer {
            text-align: center;
            margin-top: 25px;

            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 480px) {

            .login-card {
                padding: 30px 22px;
            }

            .login-title {
                font-size: 24px;
            }
        }

    </style>
</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="login-logo">
            <i class="bi bi-graph-up-arrow"></i>
        </div>

        <h1 class="login-title">
            PrediccionIA
        </h1>

        <p class="login-subtitle">
            Ingresa al panel administrativo
        </p>


        {{-- Errores de validación --}}
        @if ($errors->any())

            <div class="alert alert-error">

                @foreach ($errors->all() as $error)

                    <div>
                        {{ $error }}
                    </div>

                @endforeach

            </div>

        @endif


        {{-- Mensaje de éxito --}}
        @if (session('success'))

            <div class="alert alert-success">
                {{ session('success') }}
            </div>

        @endif


        <form
            method="POST"
            action="{{ route('login.store') }}"
        >

            @csrf


            {{-- CORREO --}}

            <div class="form-group">

                <label
                    class="form-label"
                    for="email"
                >
                    Correo electrónico
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-envelope input-icon"></i>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="admin@ejemplo.com"
                        value="{{ old('email') }}"
                        required
                        autofocus
                    >

                </div>

            </div>


            {{-- CONTRASEÑA --}}

            <div class="form-group">

                <label
                    class="form-label"
                    for="password"
                >
                    Contraseña
                </label>

                <div class="input-wrapper">

                    <i class="bi bi-lock input-icon"></i>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Ingresa tu contraseña"
                        required
                    >

                </div>

            </div>


            {{-- BOTÓN --}}

            <button
                type="submit"
                class="login-button"
            >
                <i class="bi bi-box-arrow-in-right"></i>
                Iniciar sesión
            </button>

        </form>


        <div class="login-footer">
            Sistema de gestión de inventario y predicción de demanda
        </div>

    </div>

</div>

</body>
</html>