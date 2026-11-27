@extends('errors.layout')
@section('titulo', 'Todavía no está listo')
@section('mensaje', request()->is('firma/*')
    ? 'El taller todavía está capturando el inventario de tu vehículo. Intenta abrir el enlace de nuevo en unos minutos.'
    : 'Esta acción no se puede hacer todavía.')
