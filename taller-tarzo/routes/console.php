<?php

use App\Support\TareasProgramadas;
use Illuminate\Console\Scheduling\Schedule;

TareasProgramadas::registrar(app(Schedule::class));
