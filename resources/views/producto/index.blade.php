@extends('main.master')
@section('content')
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row page-titles">
            <div class="col-md-5 col-8 align-self-center">
                <h3 class="text-themecolor">Estudiante</h3>
            </div>
        </div>
        <div class="card card-body">
            <div class="row">
                <div id="app">
                    <producto-component></producto-component>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/app.js') }}"></script>
@endsection