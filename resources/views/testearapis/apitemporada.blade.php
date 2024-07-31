<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">

    <title>Hello, world!</title>
  </head>
  <body>

    <h1>Datos que debe enviar el Sr. Milton, !</h1>
    <br>

    <form id="formulario" >
        @csrf

       <div class="form-group">
        <label for="exampleInputEmail1">contrato</label>
        <input type="text" name="contrato" value="003" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

      <div class="form-group">
        <label for="exampleInputEmail1">Year</label>
        <input type="text" name="year" value="21" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

       <div class="form-group">
        <label for="exampleInputEmail1">Ciudad</label>
        <input type="text" name="ciudad" value="Quito" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

       <div class="form-group">
        <label for="exampleInputEmail1">Temporada</label>
        <input type="text" name="temporada" value="S" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

       <div class="form-group">
        <label for="exampleInputEmail1">Docente</label>
        <input type="text" name="temporal_nombre_docente" value="SILVIA AGUILAR" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>


       <div class="form-group">
        <label for="exampleInputEmail1">Cedula Docente</label>
        <input type="text" name="temporal_cedula_docente" value="1714892328" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

    
        
        
      <div class="form-group">
        <label for="exampleInputEmail1">Institucion</label>
        <input type="text" name="temporal_institucion" value="SANTO DOMINGO DE GUZMAN" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>

      <div class="form-group">
        <label for="exampleInputEmail1">Asesor</label>
        <input type="text" name="nombre_asesor" value="Jenny Guilca" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
        
      </div>


     
     
    
      <button type="submit" onclick="obtenerdata()" class="btn btn-primary">Acceder</button>
    </form>
    <!-- Optional JavaScript; choose one of the two! -->

    <!-- Option 1: jQuery and Bootstrap Bundle (includes Popper) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>


     <script>

     function obtenerdata(){
       var form = $('#formulario');
       $.ajax({
            type: 'GET',
            url: '/temporadas/temporadaapi',
            data: form.serialize(),
            success: function(data) {
              alert("gurdado con exito")
            }
     })
     }

    </script> 
  </body>
</html>