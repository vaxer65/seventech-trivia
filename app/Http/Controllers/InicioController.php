<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HistorialEvento;
use Illuminate\View\View;

class InicioController extends Controller
{
    public function index(): View
    {
        $historial = HistorialEvento::latest()->take(6)->get();

        return view('inicio', compact('historial'));
    }
}

