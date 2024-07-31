@extends('estudiante')
@section('contenido')
<template>
    <header-component></header-component>
</template>
<template v-if="menu==5">
    <estudientecurso-component></estudientecurso-component>
</template>
@endsection