@extends('errors.layout')
@section('titulo', request()->is('firma/*', 'presupuesto/*', 'seguimiento/*', 'encuesta/*', 'baja/*') ? 'Enlace no válido' : 'Acceso no permitido')
@section('mensaje', request()->is('firma/*', 'presupuesto/*', 'seguimiento/*', 'encuesta/*', 'baja/*')
    ? 'Este enlace no es válido o ya venció. Pide al taller que te envíe uno nuevo.'
    : 'No tienes permiso para ver esta página.')
