<!doctype html>
<html lang="es">
   <head>
      <!-- Required meta tags -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Prolipa</title>
      <link rel="shortcut icon" href="img/prolipa.png" />
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <link rel="stylesheet" href="css/typography.css">
      <link rel="stylesheet" href="css/style.css">
      <link rel="stylesheet" href="css/responsive.css">
   </head>
   <body>
   <div id="loading">
         <div id="loading-center">
         </div>
      </div>
        <section class="sign-in-page">
            <div class="container bg-white mt-5 p-0">
                <div class="row no-gutters">
                    <div class="col-sm-6 align-self-center">
                        <div class="sign-in-from">
                            <h1 class="mb-0">Bienvenido</h1>
                            <p>Ingrese su dirección de correo electrónico y contraseña para acceder.</p>
                            <form class="mt-4" method="POST" action="{{route('login')}}">
                                {{ csrf_field() }}
                                @guest
                                @if (Route::has('register'))
                                <div class="form-group" {{ $errors->has('name_usuario') ? 'has-error invalid-feedback' : '' }}>
                                    <label for="exampleInputEmail1">Usuario</label>
                                    <input type="text" class="form-control mb-0" name="name_usuario" placeholder="Enter email">
                                    {!! $errors->first('name_usuario','<div class="help-block text-danger">:message</div>') !!}
                                </div>
                                <div class="form-group" {{ $errors->has('password') ? 'has-error invalid-feedback' : '' }}">
                                    <label for="exampleInputPassword1">Contraseña</label>
                                    <input type="password" class="form-control mb-0" name="password" placeholder="Contraseña">
                                    {!! $errors->first('password','<div class="help-block text-danger">:message</div>') !!}
                                </div>
                                <div class="d-inline-block w-100">
                                    <div class="custom-control custom-checkbox d-inline-block mt-2 pt-1">
                                        <input type="checkbox" class="custom-control-input" id="customCheck1">
                                    </div>
                                    <button type="submit" class="btn btn-primary float-right">Acceder</button>
                                </div>
                                <div class="sign-info">
                                    <span class="dark-color d-inline-block line-height-2">Don't have an account? <a href="#">Sign up</a></span>
                                    <ul class="iq-social-media">
                                        <li><a href="#"><i class="ri-facebook-box-line"></i></a></li>
                                        <li><a href="#"><i class="ri-twitter-line"></i></a></li>
                                        <li><a href="#"><i class="ri-instagram-line"></i></a></li>
                                    </ul>
                                </div>
                                @endif
                                @else
                                    <a class="btn btn-success" href="./home">
                                        <i class="fa fa-user"></i> &nbsp; Ingresar
                                    </a>
                                @endguest
                            </form>
                        </div>
                    </div>
                    <div class="col-sm-6 text-center">
                        <div class="sign-in-detail text-white">
                            <a class="sign-in-logo mb-5" href="#"><img src="images/logo-white.png" class="img-fluid" alt="logo"></a>
                            <div class="owl-carousel" data-autoplay="true" data-loop="true" data-nav="false" data-dots="true" data-items="1" data-items-laptop="1" data-items-tab="1" data-items-mobile="1" data-items-mobile-sm="1" data-margin="0">
                                <div class="item">
                                    <img src="images/login/1.png" class="img-fluid mb-4" alt="logo">
                                    <h4 class="mb-1 text-white">Manage your orders</h4>
                                    <p>It is a long established fact that a reader will be distracted by the readable content.</p>
                                </div>
                                <div class="item">
                                    <img src="images/login/1.png" class="img-fluid mb-4" alt="logo">
                                    <h4 class="mb-1 text-white">Manage your orders</h4>
                                    <p>It is a long established fact that a reader will be distracted by the readable content.</p>
                                </div>
                                <div class="item">
                                    <img src="images/login/1.png" class="img-fluid mb-4" alt="logo">
                                    <h4 class="mb-1 text-white">Manage your orders</h4>
                                    <p>It is a long established fact that a reader will be distracted by the readable content.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
      <script src="js/jquery.min.js"></script>
      <script src="js/popper.min.js"></script>
      <script src="js/bootstrap.min.js"></script>
      <script src="js/jquery.appear.js"></script>
      <script src="js/countdown.min.js"></script>
      <script src="js/waypoints.min.js"></script>
      <script src="js/jquery.counterup.min.js"></script>
      <script src="js/wow.min.js"></script>
      <script src="js/apexcharts.js"></script>
      <script src="js/slick.min.js"></script>
      <script src="js/select2.min.js"></script>
      <script src="js/owl.carousel.min.js"></script>
      <script src="js/jquery.magnific-popup.min.js"></script>
      <script src="js/smooth-scrollbar.js"></script>
      <script src="js/chart-custom.js"></script>
      <script src="js/custom.js"></script>
   </body>
</html>
    <!-- <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <div class="login100-pic js-tilt" data-tilt>
                    <img src="img/prolipa.png" alt="IMG">
                </div>

                <form class="login100-form " method="POST" action="{{route('login')}}">
                    {{ csrf_field() }}
                    <span class="login100-form-title">
                        Bienvenido
                    </span>
                    @guest
                        @if (Route::has('register'))
                            <div class="form-group {{ $errors->has('name_usuario') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="text" name="name_usuario" placeholder="Usuario">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-envelope" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('name_usuario','<span class="help-block">:message</span>') !!}
                            </div>

                            <div class="form-group {{ $errors->has('password') ? 'has-error text-danger' : '' }}">
                                <div class="wrap-input100">
                                    <input class="input100" type="password" name="password" placeholder="Contraseña">
                                    <span class="focus-input100"></span>
                                    <span class="symbol-input100">
                                        <i class="fa fa-lock" aria-hidden="true"></i>
                                    </span>
                                </div>
                                {!! $errors->first('password','<span class="help-block">:message</span>') !!}
                            </div>
                            <div class="container-login100-form-btn">
                                <button type="submit" class="login100-form-btn">
                                    Acceder
                                </button>
                            </div>
                            <div class="container-login100-form-btn">
                                <a href="./register" class="login100-form-btn">
                                    Estudiantes
                                </a>
                            </div>
                            <div class="row">
                                <div class="container-login100-form-btn col-md-6">
                                    <a class=" btn-danger login100-form-btn " href="login/google">
                                        <i class="fa fa-google"></i>
                                    </a>
                                </div>
                                <div class="container-login100-form-btn col-md-6">
                                    <a class=" btn-danger login100-form-btn " href="login/facebook">
                                        <i class="fa fa-facebook"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="text-center p-t-12">
                                <a class="txt2" href="{{ route('password.request') }}">
                                    He olvidado mi contraseña?
                                </a>
                            </div>
                        @endif
                        @else
                            <div class="row">
                                <div class="container-login100-form-btn col-md-12">
                                    <a class=" btn-danger login100-form-btn " href="./home">
                                        <i class="fa fa-user"></i> &nbsp; Ingresar
                                    </a>
                                </div>
                            </div>
                    @endguest


                </form>
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
</html> -->