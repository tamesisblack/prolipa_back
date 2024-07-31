<!DOCTYPE html>
<html lang="en">

<head>
    <title>Bootstrap Example</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>

<body>
    <div class="container">
   {!! Form::open(['route'=>'reporte.store','method'=>'POST']) !!}
        <div class="form-group">
            {!! Form::label('date', 'Fecha Inicio') !!}
            {!! Form::date('date1', null,['class'=>'form-control','placeholder'=>' ']) !!}
        </div>

        <div class="form-group">
            {!! Form::label('date', 'Fecha Final') !!}
            {!! Form::date('date2', null,['class'=>'form-control','placeholder'=>'']) !!}
        </div>        

        <div class="form-group">
            {!! Form::submit('Guarda', ['class'=>'btn btn-primary']) !!}

        </div>

    {!! Form::close() !!}

    </div>
</body>

</html>
