<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Reporte de asesores</title>

    <style>
        table, td, th {
          border: 1px solid #ddd;
          text-align: left;
        }
        table {
          border-collapse: collapse;
          width: 100%;
        }
        th, td {
          padding:2px;
          text-align: center;
        }
    </style>

</head>
<body style="font-size: 18px;">
    <div>

        <h1 style="color: #2A67E3;">Reporte de asesores del {{$fecha_inicio}} al {{$fecha_fin}} </h1> <br>

        Estimado/a administrador, <br><br>

        Adjunto se encuentra el reporte de las agendas de los asesores. <br><br>

        <div align="center">
            <table>
                <thead>
                    <th>Nombres</th>
                    <th>Titulo</th>
                    <th>Fecha inicio</th>
                    <th>Fecha fin</th>
                    <th>Observación</th>
                    <th>Institución temporal</th>
                    <th>Periodo</th>
                </thead>
                <tbody>
                    @foreach($agendas as $agenda)
                        <tr>
                            <td> {{$agenda->nombres}} {{$agenda->apellidos}} <br> {{$agenda->email}} </td>
                            <td> {{$agenda->title}} </td>
                            <td> {{$agenda->startDate}} {{$agenda->hora_inicio}} </td>
                            <td> {{$agenda->endDate}} {{$agenda->hora_fin}} </td>
                            <td> {{$agenda->url}} </td>
                            <td> {{$agenda->nombre_institucion_temporal}} </td>
                            <td> {{$agenda->periodoescolar}} </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>
