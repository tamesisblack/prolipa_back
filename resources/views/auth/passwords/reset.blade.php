@extends('layouts.app')

@section('content')
<div class="container-login100">
    <div class="wrap-login100">
        <div class="login100-pic js-tilt" data-tilt>
            <img src="../img/prolipa.png" alt="IMG">
        </div>
        <form class="login100-form" method="POST" action="{{ route('password.update') }}">
            @csrf
            <span class="login100-form-title">Restablecer Contraseña</span>

            <input class="input100" type="hidden" name="token" value="{{ $token }}">

            <div class="form-group {{ $errors->has('email') ? 'has-error text-danger' : '' }}">
                <div class="wrap-input100">
                    <input class="input100" type="text" id="email" name="email" value="{{ $email ?? old('email') }}"
                        placeholder="Correo">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                    </span>
                </div>
                {!! $errors->first('email','<span class="help-block">:message</span>') !!}
            </div>

            <div class="form-group {{ $errors->has('password') ? 'has-error text-danger' : '' }}">
                <div class="wrap-input100">
                    <input class="input100" type="password" id="password" name="password" placeholder="Contraseña">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                    </span>
                </div>
                {!! $errors->first('password','<span class="help-block">:message</span>') !!}
            </div>

            <div class="form-group {{ $errors->has('password') ? 'has-error text-danger' : '' }}">
                <div class="wrap-input100">
                    <input class="input100" type="password" id="password-confirm" name="password_confirmation" placeholder="Confirma Contraseña">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                    </span>
                </div>
            </div>

            <div class="container-login100-form-btn">
                <button type="submit" class="login100-form-btn">
                    {{ __('Enviar') }}
                </button>
            </div>
        </form>
    </div>
</div>



@endsection