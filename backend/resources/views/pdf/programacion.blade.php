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
        @font-face {
            font-family: 'FontAwesome';
            src: url("{{ resource_path('fonts/fa-solid-900.ttf') }}") format('truetype');
            font-weight: 900;
            font-style: normal;
        }

        @page {
            size: 1920px 1080px;
            margin: 80px 100px 160px 100px;
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
        .custom-cover {
            position: absolute;
            top: -80px;
            left: -100px;
            width: 1920px;
            height: 1080px;
            z-index: 100;
        }

        /* ─── Salto de Página ────────────────────────────────────────────────── */
        .page-break {
            page-break-after: always;
            clear: both;
        }

        /* ─── Contenido General (Escalado para 1920x1080) ─────────────────────────────────────────────── */
        .content-page {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            position: relative;
        }

        .page-header-line {
            border-bottom: 4px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 60px;
        }

        .page-header-title {
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 4px;
            color: #64748b;
            font-weight: bold;
            float: right;
            margin-top: 10px;
        }

        .page-header-club {
            font-size: 24px;
            font-weight: bold;
            color: #0d3a77;
            float: left;
        }

        .clear { clear: both; }

        .page-footer {
            position: fixed;
            bottom: -120px;
            left: 0;
            right: 0;
            height: 90px;
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
            font-size: 18px;
            color: #64748b;
            font-weight: bold;
            z-index: 1;
        }

        .color-legend { margin-bottom: 10px; }
        .legend-item { display: inline-block; margin-right: 24px; }
        
        .legend-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        .page-number {
            font-size: 18px;
            color: #94a3b8;
            margin-top: 6px;
        }

        .shift-title {
            text-align: center;
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 12px;
            color: #0d3a77;
            margin-top: 20px;
            margin-bottom: 70px;
            text-transform: uppercase;
            border-bottom: 6px double #cbd5e1;
            padding-bottom: 16px;
        }

        .discipline-section {
            margin-bottom: 60px;
            page-break-inside: avoid;
        }

        .discipline-header {
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .discipline-icon-container {
            display: inline-block;
            vertical-align: middle;
            margin-right: 12px;
        }

        .discipline-icon {
            font-family: 'FontAwesome' !important;
            font-weight: 900;
            font-size: 32px;
            color: #0d3a77;
            vertical-align: middle;
            line-height: 1;
            display: inline-block;
        }

        .discipline-title {
            display: inline-block;
            font-size: 32px;
            font-weight: bold;
            color: #0d3a77;
            text-transform: uppercase;
            letter-spacing: 2px;
            vertical-align: middle;
            margin: 0;
        }

        .sessions-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sessions-table th {
            font-size: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #0d3a77;
            font-weight: bold;
            padding: 20px 24px;
            background-color: #f8fafc;
            border-bottom: 4px solid #e2e8f0;
            text-align: center;
        }

        .sessions-table td {
            font-size: 22px;
            padding: 20px 24px;
            vertical-align: middle;
            border-bottom: 2px solid #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: center;
        }

        .col-space { width: 30%; text-align: center; }
        .col-day { width: 18%; text-align: center; }
        .col-time { width: 27%; text-align: center; }
        .col-instructor { width: 25%; text-align: center; }

        .day-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 24px;
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: center;
            min-width: 130px;
        }

        .day-lunes { background-color: #3b82f6; }
        .day-martes { background-color: #ef4444; }
        .day-miercoles { background-color: #10b981; }
        .day-jueves { background-color: #f59e0b; }
        .day-viernes { background-color: #8b5cf6; }
        .day-sabado { background-color: #ec4899; }
        .day-domingo { background-color: #6b7280; }

        .time-text {
            font-size: 22px;
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

    if (!function_exists('getDisciplineIconHtml')) {
        function getDisciplineIconHtml($name) {
            $n = strtolower(trim($name));

            // ── Mapeo EXACTO por nombre de disciplina (26 disciplinas) ──────────
            // Codepoints de FontAwesome 6 Free Solid
            $exactMap = [
                // 1
                'spinning'                 => '\uf84a', // person-biking
                // 2
                'padel'                    => '\uf45d', // table-tennis-paddle-ball
                // 3
                'zumba'                    => '\uf51f', // compact-disc
                // 4
                'futbol adultos'           => '\uf1e3', // futbol
                // 5
                'ludoteca'                 => '\uf12e', // puzzle-piece
                // 6
                'entrenamiento funcional'  => '\uf21e', // heart-pulse
                // 7
                'gym uso libre'            => '\uf44b', // dumbbell
                // 8
                'barre'                    => '\uf54b', // shoe-prints
                // 9
                'voleibol'                 => '\uf45f', // volleyball
                // 10
                'jazz'                     => '\uf86f', // guitar
                // 11
                'higiene de columna'       => '\uf5d7', // bone
                // 12
                'frontenis'                => '\uf45d', // table-tennis-paddle-ball
                // 13
                'meditacion'               => '\uf679', // om
                'meditación'               => '\uf679',
                // 14
                'basquetbol'               => '\uf434', // basketball
                'basketball'               => '\uf434',
                // 15
                'tenis'                    => '\uf45d', // table-tennis-paddle-ball
                // 16
                'baile'                    => '\uf001', // music
                // 17
                'futbol infantil'          => '\uf1e3', // futbol
                // 18
                'pilates'                  => '\uf5bb', // spa
                // 19
                'squash'                   => '\uf45d', // table-tennis-paddle-ball
                // 20
                'gimnasia olimpica'        => '\uf5a2', // medal
                'gimnasia olímpica'        => '\uf5a2',
                // 21
                'natacion'                 => '\uf5c4', // person-swimming
                'natación'                 => '\uf5c4',
                // 22
                'gym instructor'           => '\uf7f3', // clipboard-user
                // 23
                'acondicionamiento fisico' => '\uf2f2', // stopwatch
                'acondicionamiento físico' => '\uf2f2',
                // 24
                'yoga'                     => '\uf6ad', // yin-yang
                // 25
                'tae kwon do'              => '\uf504', // user-ninja
                // 26
                'aerobics'                 => '\uf70c', // person-running
            ];

            // Buscar primero por nombre exacto
            $codepoint = null;
            if (isset($exactMap[$n])) {
                $codepoint = $exactMap[$n];
            }

            // Si no coincide exactamente, buscar por palabra clave (fallback)
            if (!$codepoint) {
                $keywords = [
                    'futbol'    => '\uf1e3',
                    'soccer'    => '\uf1e3',
                    'basket'    => '\uf434',
                    'voleibol'  => '\uf45f',
                    'tenis'     => '\uf45d',
                    'tennis'    => '\uf45d',
                    'padel'     => '\uf45d',
                    'squash'    => '\uf45d',
                    'frontenis' => '\uf45d',
                    'spinning'  => '\uf84a',
                    'bici'      => '\uf84a',
                    'ciclismo'  => '\uf84a',
                    'tae kwon'  => '\uf6de',
                    'karate'    => '\uf6de',
                    'marciales' => '\uf6de',
                    'yoga'      => '\uf6ad',
                    'pilates'   => '\uf6ad',
                    'meditac'   => '\uf6ad',
                    'barre'     => '\uf554',
                    'aerobic'   => '\uf70c',
                    'funcional' => '\uf44b',
                    'acondiciona' => '\uf70c',
                    'zumba'     => '\uf001',
                    'baile'     => '\uf001',
                    'jazz'      => '\uf86f',
                    'natac'     => '\uf5c4',
                    'gimnasia'  => '\uf5a2',
                    'gym'       => '\uf44b',
                    'ludoteca'  => '\uf1ae',
                    'columna'   => '\uf481',
                ];
                foreach ($keywords as $kw => $code) {
                    if (strpos($n, $kw) !== false) {
                        $codepoint = $code;
                        break;
                    }
                }
            }

            // Icono genérico si no hay coincidencia
            if (!$codepoint) {
                $codepoint = '\uf005'; // star (FontAwesome default)
            }

            // Convertir el codepoint \uXXXX a entidad HTML &#xXXXX;
            $hex = str_replace('\u', '', $codepoint);

            return '<span class="discipline-icon">&#x' . $hex . ';</span>';
        }
    }
    @endphp

    <!-- ─── PORTADA (PÁGINA 1) ────────────────────────────────────────────── -->
    @if(file_exists(public_path('Programacion_disciplinas.png')))
        <img class="custom-cover" src="{{ public_path('Programacion_disciplinas.png') }}" alt="Portada">
    @endif

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
                        {!! getDisciplineIconHtml($disciplinaName) !!}
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
                        {!! getDisciplineIconHtml($disciplinaName) !!}
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
