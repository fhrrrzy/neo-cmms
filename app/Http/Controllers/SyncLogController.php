<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SyncLogController extends Controller
{
    /**
     * Display the sync log page
     */
    public function index(): Response
    {
        return Inertia::render('sync-log/Index');
    }
}
