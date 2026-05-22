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

        <h1 style="color:#dc2626;">
            Torneo Cancelado
        </h1>

        <p style="font-size:16px; color:#333;">
            Hola participante,
        </p>

        <p style="font-size:16px; color:#333;">
            El torneo
            <strong>{{ $torneo->nombre_torneo }}</strong>
            ha sido cancelado.
        </p>

        <div style="
            background:#fef2f2;
            border-left:5px solid #dc2626;
            padding:15px;
            margin:20px 0;
        ">
            <strong>Motivo:</strong><br>
            {{ $motivo }}
        </div>

        <p style="font-size:14px; color:#777;">
            SOC-DEP HUB · Centro Deportivo
        </p>

    </div>

</body>

</html>