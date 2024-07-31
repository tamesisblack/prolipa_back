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
  			{!! Form::label('name', 'Nombre Completo') !!}
  			{!! Form::text('name', null,['class'=>'form-control','placeholder'=>'nombre completo']) !!}
  		</div>

  		<div class="form-group">
  			{!! Form::label('email', 'Correo Electronico') !!}
  			{!! Form::email('email', null,['class'=>'form-control','placeholder'=>'example@gmail.com']) !!}
  		</div>

  		<div class="form-group">
  			{!! Form::label('password', 'contrasena') !!}
  			{!! Form::password('password',['class'=>'form-control','placeholder'=>'*******************']) !!}
  		</div>

<div class="form-group">
  			{!! Form::submit('Guarda', ['class'=>'btn btn-primary']) !!}

  		</div>

  	{!! Form::close() !!}
  
</div>

</body>
</html>
