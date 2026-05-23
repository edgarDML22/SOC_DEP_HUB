<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Programación de Disciplinas Deportivas - {{ $plantilla->nombre_plantilla }}</title>
    <style>
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Regular.ttf') }}") format('truetype');
            font-weight: 300;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Regular.ttf') }}") format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Regular.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Medium.ttf') }}") format('truetype');
            font-weight: 500;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Bold.ttf') }}") format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Bold.ttf') }}") format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Bold.ttf') }}") format('truetype');
            font-weight: 800;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Bold.ttf') }}") format('truetype');
            font-weight: 900;
            font-style: normal;
        }
        @font-face {
            font-family: 'Inter';
            src: url("{{ resource_path('fonts/Inter-Bold.ttf') }}") format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @page {
            margin: 0px;
        }
        
        html, body, h1, h2, h3, h4, h5, h6, table, thead, tbody, tr, th, td, span, div, p, a, b, strong, em, small {
            font-family: 'Inter', sans-serif !important;
        }

        body {
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }

        /* ─── Portada ────────────────────────────────────────────────────────── */
        .cover {
            background: linear-gradient(135deg, #0d3a77 0%, #001a40 100%);
            color: #ffffff;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            text-align: center;
            padding-top: 15%;
            box-sizing: border-box;
        }

        .cover-logo {
            width: 160px;
            height: 160px;
            border-radius: 30px;
            object-fit: cover;
            margin-bottom: 30px;
            border: 4px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        .cover-logo-placeholder {
            width: 160px;
            height: 160px;
            border-radius: 30px;
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            line-height: 160px;
            margin: 0 auto 30px auto;
            border: 4px solid rgba(255, 255, 255, 0.15);
        }

        .cover-subtitle {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 5px;
            font-weight: 800;
            color: #3b82f6;
            margin-bottom: 10px;
            margin-top: 20px;
        }

        .cover-title {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: -1px;
            margin: 0 auto 10px auto;
            max-width: 80%;
            line-height: 1.2;
        }

        .cover-slogan {
            font-size: 16px;
            color: #94a3b8;
            font-style: italic;
            margin-bottom: 50px;
        }

        .cover-qr-container {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
        }

        .cover-qr {
            width: 130px;
            height: 130px;
            display: block;
        }

        .cover-footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            font-size: 11px;
            color: #64748b;
            letter-spacing: 1px;
        }

        /* ─── Salto de Página ────────────────────────────────────────────────── */
        .page-break {
            page-break-after: always;
            clear: both;
        }

        /* ─── Contenido General ─────────────────────────────────────────────── */
        .content-page {
            margin: 0;
            padding: 40px 50px 80px 50px;
            box-sizing: border-box;
            position: relative;
        }

        /* Cabecera y Pie de página fijos para el contenido */
        .page-header-line {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        .page-header-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            font-weight: bold;
            float: right;
            margin-top: 5px;
        }

        .page-header-club {
            font-size: 12px;
            font-weight: 900;
            color: #0d3a77;
            float: left;
        }

        .clear {
            clear: both;
        }

        /* Pie de página con leyenda de colores */
        .page-footer {
            position: fixed;
            bottom: 20px;
            left: 50px;
            right: 50px;
            height: 45px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            font-weight: bold;
        }

        .color-legend {
            margin-bottom: 5px;
        }

        .legend-item {
            display: inline-block;
            margin-right: 12px;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
            vertical-align: middle;
        }

        .page-number {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ─── Secciones Matutino/Vespertino ──────────────────────────────────── */
        .shift-title {
            text-align: center;
            font-size: 24px;
            font-weight: 900;
            letter-spacing: 6px;
            color: #0d3a77;
            margin-top: 10px;
            margin-bottom: 35px;
            text-transform: uppercase;
            border-bottom: 3px double #cbd5e1;
            padding-bottom: 8px;
        }

        .discipline-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .discipline-header {
            margin-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }

        .discipline-icon-container {
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
            color: #1e3a8a;
        }

        .discipline-icon-container svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
            display: block;
        }

        .discipline-title {
            display: inline-block;
            font-size: 16px;
            font-weight: 800;
            color: #0d3a77;
            text-transform: uppercase;
            letter-spacing: 1px;
            vertical-align: middle;
            margin: 0;
        }

        /* ─── Tabla Limpia Sin Cuadrícula ────────────────────────────────────── */
        .sessions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sessions-table th {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0d3a77;
            font-weight: 800;
            padding: 10px 12px;
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            text-align: center;
        }

        .sessions-table td {
            font-size: 11px;
            padding: 10px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: center;
        }

        .col-space { width: 30%; text-align: center; }
        .col-day { width: 18%; text-align: center; }
        .col-time { width: 27%; text-align: center; }
        .col-instructor { width: 25%; text-align: center; }

        /* Badges de días (Código de colores) */
        .day-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 800;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            min-width: 65px;
        }

        /* Clases de colores para días */
        .day-lunes { background-color: #3b82f6; }
        .day-martes { background-color: #ef4444; }
        .day-miercoles { background-color: #10b981; }
        .day-jueves { background-color: #f59e0b; }
        .day-viernes { background-color: #8b5cf6; }
        .day-sabado { background-color: #ec4899; }
        .day-domingo { background-color: #6b7280; }

        .time-text {
            font-size: 11px;
            color: #0f172a;
        }
    </style>
</head>
<body>

    @php
    if (!function_exists('formatTimePdf')) {
        function formatTimePdf($time) {
            return date("g:i a", strtotime($time));
        }
    }

    if (!function_exists('getDisciplineSvgHtml')) {
        function getDisciplineSvgHtml($name) {
            $n = strtolower($name);
            
            // Yoga
            if (strpos($n, 'yoga') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 3a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0 15a3 3 0 0 1-3-3v-1H7v2a1 1 0 0 1-2 0v-3a2 2 0 0 1 2-2h6v2H9v1a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-1h-2v-2h4a2 2 0 0 1 2 2v3a1 1 0 0 1-2 0v-2h-2v1a3 3 0 0 1-3 3z"/></svg>';
            }
            // Pilates
            if (strpos($n, 'pilates') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z"/></svg>';
            }
            // Fútbol
            if (strpos($n, 'futbol') !== false || strpos($n, 'fútbol') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm0 2c1.47 0 2.84.4 4.02 1.09l-1.42 2.45h-5.2L7.98 5.09C9.16 4.4 10.53 4 12 4zm-5.4 2.44L8.7 9.87l-.92 2.82-2.92-.95C4.34 10.52 4 8.87 4 7.15c.67.62 1.54 1.04 2.6-.71zM4.1 14.85l2.92-.95.92 2.82-2.1 2.44C4.84 18.04 4 16.53 4 14.85zm7.9 5.15c-1.47 0-2.84-.4-4.02-1.09l1.42-2.45h5.2l1.42 2.45C14.84 19.6 13.47 20 12 20zm5.4-2.44l-2.1-2.44.92-2.82 2.92.95c.52 1.22.86 2.87.86 4.59-.67-.62-1.54-1.04-2.6.72zm2.5-2.71l-2.92-.95-.92-2.82 2.1-2.44c1 1.12 1.66 2.63 1.74 4.31a3.9 3.9 0 0 0-2.6.72z"/></svg>';
            }
            // Zumba / Baile / Dance
            if (strpos($n, 'zumba') !== false || strpos($n, 'baile') !== false || strpos($n, 'dance') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm9 7h-6v11h-2v-6h-2v6H9V9H3V7h18v2z"/></svg>';
            }
            // Spinning / Bici
            if (strpos($n, 'spinning') !== false || strpos($n, 'bici') !== false || strpos($n, 'ciclismo') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M15.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM5 12c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm14-8.5c-2.8 0-5 2.2-5 5s2.2 5 5 5 5-2.2 5-5-2.2-5-5-5zm0 8.5c-1.9 0-3.5-1.6-3.5-3.5s1.6-3.5 3.5-3.5 3.5 1.6 3.5 3.5-1.6 3.5-3.5 3.5zm-5.7-9.3l-1.8-2.4c-.4-.5-1-.8-1.7-.8H7.3c-.6 0-1.1.3-1.4.8L3.5 11.2c-.4.6-.2 1.4.4 1.8.6.4 1.4.2 1.8-.4l1.8-2.6h1.2l3.2 4.3c.4.5 1 .8 1.7.8h2.5c.7 0 1.2-.6 1.2-1.2 0-.7-.5-1.2-1.2-1.2h-2.1l-2.4-3.2z"/></svg>';
            }
            // Tenis / Padel
            if (strpos($n, 'tenis') !== false || strpos($n, 'tennis') !== false || strpos($n, 'padel') !== false || strpos($n, 'pádel') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.31 6.5l-4.1 4.1-1.41-1.41 4.1-4.1 1.41 1.41zm-6.22 6.22l-1.41-1.41 1.41-1.41 1.41 1.41-1.41 1.41zM6.5 15.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>';
            }
            // Tae Kwon Do / Karate / Boxeo / Martial Arts
            if (strpos($n, 'tae kwon') !== false || strpos($n, 'karate') !== false || strpos($n, 'box') !== false || strpos($n, 'artes marciales') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
            }
            // Aerobics
            if (strpos($n, 'aerobics') !== false || strpos($n, 'aerobicos') !== false || strpos($n, 'aeróbicos') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 21h2.2l1.9-8.2 2.1 2V21h2v-7.8l-2.4-2.4.6-3.2c.8 1.3 2.1 2.2 3.7 2.4V8c-1.3-.2-2.4-1-3-2.1l-.9-1.5C12.8 3.7 12 3.2 11.2 3.2c-.3 0-.6.1-.9.2L6 5.1v4.8h2V6.6l1.8-.7-.6 3z"/></svg>';
            }
            // Gimnasia
            if (strpos($n, 'gimnasia') !== false) {
                return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
            }
            
            // Default icon
            return '<svg viewBox="0 0 24 24" width="20" height="20" fill="#0d3a77"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/></svg>';
        }
    }
    @endphp

    <!-- ─── PORTADA (PÁGINA 1) ────────────────────────────────────────────── -->
    <div class="cover">
        @if($base64Logo)
            <img class="cover-logo" src="data:image/png;base64,{{ $base64Logo }}" alt="Logo">
        @else
            <div class="cover-logo-placeholder">SOC-DEP</div>
        @endif
        
        <div class="cover-subtitle">Programación de</div>
        <h1 class="cover-title">DISCIPLINAS DEPORTIVAS</h1>
        <div class="cover-slogan">Tu espacio, tu horario, tu pasión.</div>
        
        <div class="cover-qr-container">
            <img class="cover-qr" src="data:image/svg+xml;base64,{{ $base64Qr }}" alt="QR Code">
        </div>
        
        <div class="cover-footer">
            {{ strtoupper($plantilla->nombre_plantilla) }} &nbsp;·&nbsp; SOC-DEP HUB
        </div>
    </div>

    <!-- SALTO DE PÁGINA PARA EMPEZAR LA PROGRAMACIÓN -->
    <div class="page-break"></div>

    <!-- ─── CABECERA Y PIE DE PÁGINA FIJOS (PÁGINAS SIGUIENTES) ──────────────── -->
    <div class="page-footer">
        <div class="color-legend">
            <span class="legend-item"><span class="legend-dot" style="background-color: #3b82f6;"></span> LUNES</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #ef4444;"></span> MARTES</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #10b981;"></span> MIÉRCOLES</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #f59e0b;"></span> JUEVES</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #8b5cf6;"></span> VIERNES</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #ec4899;"></span> SÁBADO</span>
            <span class="legend-item"><span class="legend-dot" style="background-color: #6b7280;"></span> DOMINGO</span>
        </div>
        <div class="page-number">Página de programación semanal · Soc-Dep Hub</div>
    </div>

    <!-- ─── SECCIÓN MATUTINO ─────────────────────────────────────────────── -->
    @if(count($matutino) > 0)
    <div class="content-page">
        <!-- Encabezado de página -->
        <div class="page-header-line">
            <span class="page-header-club">SOC-DEP HUB</span>
            <span class="page-header-title">Sesiones Matutinas</span>
            <div class="clear"></div>
        </div>

        <div class="shift-title">MATUTINO</div>

        @foreach($matutino as $disciplinaName => $grupo)
            <div class="discipline-section">
                <div class="discipline-header">
                    <span class="discipline-icon-container">
                        {!! getDisciplineSvgHtml($disciplinaName) !!}
                    </span>
                    <h3 class="discipline-title">{{ $disciplinaName }}</h3>
                </div>

                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th class="col-space">Espacio</th>
                            <th class="col-day">Día</th>
                            <th class="col-time">Hora</th>
                            <th class="col-instructor">Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grupo['actividades'] as $act)
                            <tr>
                                <td class="col-space">
                                    {{ $act->espacioFisico ? $act->espacioFisico->nombre_espacio : 'Sin espacio' }}
                                </td>
                                <td class="col-day">
                                    <span class="day-badge day-{{ strtolower($act->dia_semana) }}">
                                        {{ $act->dia_semana }}
                                    </span>
                                </td>
                                <td class="col-time">
                                    <span class="time-text">
                                        {{ formatTimePdf($act->hora_inicio) }} - {{ formatTimePdf($act->hora_fin) }}
                                    </span>
                                </td>
                                <td class="col-instructor">
                                    {{ $act->instructor ? $act->instructor->nombre_completo : 'Sin instructor' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    @endif

    <!-- SALTO DE PÁGINA SI EXISTEN AMBOS HORARIOS -->
    @if(count($matutino) > 0 && count($vespertino) > 0)
    <div class="page-break"></div>
    @endif

    <!-- ─── SECCIÓN VESPERTINO ────────────────────────────────────────────── -->
    @if(count($vespertino) > 0)
    <div class="content-page">
        <!-- Encabezado de página -->
        <div class="page-header-line">
            <span class="page-header-club">SOC-DEP HUB</span>
            <span class="page-header-title">Sesiones Vespertinas</span>
            <div class="clear"></div>
        </div>

        <div class="shift-title">VESPERTINO</div>

        @foreach($vespertino as $disciplinaName => $grupo)
            <div class="discipline-section">
                <div class="discipline-header">
                    <span class="discipline-icon-container">
                        {!! getDisciplineSvgHtml($disciplinaName) !!}
                    </span>
                    <h3 class="discipline-title">{{ $disciplinaName }}</h3>
                </div>

                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th class="col-space">Espacio</th>
                            <th class="col-day">Día</th>
                            <th class="col-time">Hora</th>
                            <th class="col-instructor">Instructor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grupo['actividades'] as $act)
                            <tr>
                                <td class="col-space">
                                    {{ $act->espacioFisico ? $act->espacioFisico->nombre_espacio : 'Sin espacio' }}
                                </td>
                                <td class="col-day">
                                    <span class="day-badge day-{{ strtolower($act->dia_semana) }}">
                                        {{ $act->dia_semana }}
                                    </span>
                                </td>
                                <td class="col-time">
                                    <span class="time-text">
                                        {{ formatTimePdf($act->hora_inicio) }} - {{ formatTimePdf($act->hora_fin) }}
                                    </span>
                                </td>
                                <td class="col-instructor">
                                    {{ $act->instructor ? $act->instructor->nombre_completo : 'Sin instructor' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    @endif

</body>
</html>
