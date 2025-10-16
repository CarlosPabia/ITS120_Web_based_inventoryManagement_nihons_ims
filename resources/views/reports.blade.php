<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Nihon Café</title>
    
    <link rel="stylesheet" href="{{ asset('main.css') }}">
    <link rel="stylesheet" href="{{ asset('reports.css') }}"> 
    
    <style>
        /* Minimal Layout Styles for Integration (omitted for brevity) */
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; }
        .dashboard-layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
        .main-content { margin-left: 200px; padding: 20px; }
        .panel-title { color: #a03c3c; }
        .panel { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-top: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        
        <main class="main-content">
            <h1 class="panel-title">Sales & Orders Reports (Manager Only)</h1>
            
            <div class="panel">
                <h2 style="margin-top: 0; font-size: 18px;">Metrics Summary</h2>
                
                <h3>Weekly Sales Performance</h3>
                <p>Period: **{{ $weeklySales['start_date'] }} to {{ $weeklySales['end_date'] }}**</p>
                <p>Total Revenue: <strong>₱{{ $weeklySales['total_revenue'] }}</strong></p>
                <p>Total Orders Completed: <strong>{{ $weeklySales['total_orders'] }}</strong></p>
                
                <hr style="margin: 15px 0;">

                <h3>Monthly Sales Performance</h3>
                <p>Period: **{{ $monthlySales['start_date'] }} to {{ $monthlySales['end_date'] }}**</p>
                <p>Total Revenue: <strong>₱{{ $monthlySales['total_revenue'] }}</strong></p>
                <p>Total Orders Completed: <strong>{{ $monthlySales['total_orders'] }}</strong></p>

                <hr style="margin: 15px 0;">
                
                <h3>Inventory Health Indicators</h3>
                <p style="color: orange;">Items Below Reorder Threshold: <strong>{{ $lowStockCount }}</strong> (Critical Shortage)</p>
                <p style="color: red;">Items Nearing Expiry (30 days): <strong>{{ $expiryCount }}</strong> (Potential Waste)</p>
                <p>Pending Supplier Orders: <strong>{{ $pendingOrdersCount }}</strong></p>
            </div>
            
        </main>
    </div>
</body>
</html>