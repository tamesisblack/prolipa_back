<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Recordatorio de pago</title>
</head>
<body style="font-size: 18px;">
    <div>

        <h1 style="color: #2A67E3;">Recordatorio de pago</h1> <br>

        Estimado/a estudiante {{ $nombres }} {{ $apellidos }}, <br><br>

        Le informamos que su cuota correspondiente a la fecha {{ $fecha_a_pagar }} registra pendiente el pago mínimo de {{ $valor_pendiente }} USD <br><br>
        
        Le recordamos que la fecha tope de pago fue el {{ $fecha_a_pagar }} <br><br>
        
        Por favor realice el pago por los medios oficiales de la institución. <br><br>

        <div align="center">
            <div style="width: 75%; padding: 20px; border-radius: 5px; color: #E3862A; background-color: #FEDEBD;">
                En caso de que usted ya haya realizado su pago, por favor no considerar la información de este mensaje
            </div>
        </div>

    </div>

</body>
</html>