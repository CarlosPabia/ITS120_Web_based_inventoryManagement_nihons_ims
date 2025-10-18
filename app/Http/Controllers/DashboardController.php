<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class DashboardController extends Controller
{
    /**
     * Display the application dashboard with aggregated data.
     */
    public function index(ReportController $reportController)
    {
        // Fetch all the aggregated data from the ReportController
        $data = $reportController->getDashboardMetrics(); 
        
        // Return the dashboard view with the data
        return view('dashboard', $data);
    }
}