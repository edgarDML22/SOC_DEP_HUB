<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
</head>

<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:40px;">

    <div style="
        max-width:600px;
        margin:auto;
        background:white;
        border-radius:12px;
        padding:30px;
        box-shadow:0 2px 10px rgba(0,0,0,0.1);
    ">

        <h1 style="color:#7c3aed; margin-top:0;">
            Sesión Cancelada
        </h1>

        <p style="font-size:16px; color:#333;">
            Hola{{ $nombreDestinatario ? ', ' . $nombreDestinatario : '' }},
        </p>

        <p style="font-size:16px; color:#333;">
            Te informamos que, por decisión de la administración del club, la siguiente sesión a la que estabas inscrito ha sido <strong>cancelada</strong>:
        </p>

        @php
            $actividad = $sesion->actividadPlantilla;
        @endphp

        <div style="
            background:#f5f3ff;
            border-left:5px solid #7c3aed;
            padding:16px 20px;
            margin:20px 0;
            border-radius:0 8px 8px 0;
        ">
            <table style="width:100%; border-collapse:collapse; font-size:15px; color:#333;">
                <tr>
                    <td style="padding:4px 0; font-weight:bold; width:40%;">Disciplina</td>
                    <td style="padding:4px 0;">{{ $actividad?->disciplina?->nombre_disciplina ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; font-weight:bold;">Instructor</td>
                    <td style="padding:4px 0;">{{ $actividad?->instructor?->nombre_completo ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; font-weight:bold;">Espacio</td>
                    <td style="padding:4px 0;">{{ $actividad?->espacioFisico?->nombre_espacio ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:4px 0; font-weight:bold;">Fecha</td>
                    <td style="padding:4px 0;">
                        @if($sesion->fecha_sesion)
                            {{ \Carbon\Carbon::parse($sesion->fecha_sesion)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:4px 0; font-weight:bold;">Horario</td>
                    <td style="padding:4px 0;">
                        {{ $actividad?->hora_inicio ? substr($actividad->hora_inicio, 0, 5) : '—' }}
                        –
                        {{ $actividad?->hora_fin ? substr($actividad->hora_fin, 0, 5) : '—' }}
                    </td>
                </tr>
            </table>
        </div>

        <p style="font-size:16px; color:#333;">
            Pedimos una <strong>sincera disculpa</strong> por los inconvenientes que esto pueda ocasionarte. Valoramos tu participación y tu tiempo.
        </p>

        <p style="font-size:14px; color:#555;">
            Si tienes alguna duda o deseas mayor información, te invitamos a comunicarte con la administración del club. Estamos a tus órdenes.
        </p>

        <p style="font-size:14px; color:#777; margin-top:30px;">
            SOC-DEP HUB · Centro Deportivo
        </p>

    </div>

</body>

</html>
