<!DOCTYPE html>
<html lang="en">
<head>
    <title>Prolipa</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="img/prolipa.png" />
    <link rel="stylesheet" type="text/css" href="vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/animate/animate.css">
    <link rel="stylesheet" type="text/css" href="vendor/css-hamburgers/hamburgers.min.css">
    <link rel="stylesheet" type="text/css" href="vendor/select2/select2.min.css">
    <link rel="stylesheet" type="text/css" href="css/util.css">
    <link rel="stylesheet" type="text/css" href="css/main.css">
</head>
<body>
    <div class="limiter">
        <div class="container-login100">
            <div class="container">
                <span class="login100-form-title">
                    Registro del Estudiante
                </span>
                <form class="" method="POST" action="{{route('register')}}">
                @csrf
                    <div class="row">
                        <div class="col col-md-6 form-group {{ $errors->has('cedula') ? 'has-error text-danger' : '' }}">
                            <div class="wrap-input100">
                                <input class="input100" type="text" id="cedula" name="cedula" placeholder="Cedula">
                                <span class="focus-input100"></span>
                                <span class="symbol-input100">
                                    <i class="fa fa-id-card-o" aria-hidden="true"></i>
                                </span>
                            </div>
                            {!! $errors->first('cedula','<span class="help-block">:message</span>') !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col col-md-6">
                            <div class="form-group {{ $errors->has('nombres') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="text" id="npmbres" name="nombres" placeholder="Nombres">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-user" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('nombres','<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="form-group {{ $errors->has('email') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="text" id="email" name="email" placeholder="Correo">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-envelope-o" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('email','<span class="help-block">:message</span>') !!}
                            </div>
                            <!-- <div class="form-group {{ $errors->has('codigo') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="text" id="codigo" name="codigo" placeholder="Codigo de la Clase">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-key" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('codigo','<span class="help-block">:message</span>') !!}
                            </div> -->
                        </div>
                        <div class="col col-md-6">
                            <div class="form-group {{ $errors->has('apellidos') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="text" id="apellidos" name="apellidos" placeholder="Apellidos">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-user" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('apellidos','<span class="help-block">:message</span>') !!}
                            </div>
                        </div>
                    </div>
                    <div class="container-login100-form-btn">
                        <button type="submit" class="login100-form-btn">
                            Acceder
                        </button>
                    </div>
                </form>
                <div class="container-login100-form-btn">
                    <a href="./" class="login100-form-btn">
                        Salir
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap/js/popper.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="vendor/tilt/tilt.jquery.min.js"></script>
    <script data-ad-client="ca-pub-4551947831153083" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
</body>
</html>