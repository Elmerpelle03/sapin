<?php  
require '../config/session_admin.php';
require '../config/db.php';

if(isset($_SESSION['success_message'])){
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
elseif(isset($_SESSION['error_message'])){
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$months = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];
$currentYear = (int)date('Y');
$monthSelected = $_GET['month'] ?? date('F');
$yearSelected = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$pieMonthSelected = $_GET['piemonth'] ?? date('F');
$pieYearSelected = isset($_GET['pieyear']) ? (int)$_GET['pieyear'] : $currentYear;
$forecastMonthSelected = $_GET['forecastmonth'] ?? date('F', strtotime('+1 month'));
$forecastYearSelected = isset($_GET['forecastyear']) ? (int)$_GET['forecastyear'] : $currentYear;
$years = range(2021, $currentYear + 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Admin Reports">
    <meta name="author" content="AdminKit">
    <meta name="keywords" content="reports, sales, dashboard, analytics">

    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    <title>Reports</title>

    <!-- Core styles -->
    <link href="css/app.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { background-color: #f7f9fc; }
        
        .page-header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 2rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 1.75rem;
        }
        
        /* Card styles */
        .card { 
            border: none;
            border-radius: 14px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
            transition: transform 0.2s, box-shadow 0.2s; 
            position: relative; 
            overflow: hidden; 
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .card-body h3 { font-weight: 700; margin: 0; position: relative; z-index: 1; }
        .card-body h6 { font-weight: 500; color: #fff; position: relative; z-index: 1; }
        .card .icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 6rem; color: rgba(255,255,255,0.2); }

        /* Gradient backgrounds */
        .bg-gradient-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color:#fff; }
        .bg-gradient-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color:#fff; }
        .bg-gradient-yellow { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color:#fff; }
        .bg-gradient-red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color:#fff; }

        /* Chart styles */
        canvas { width: 100% !important; height: auto !important; }
        .chart-row { display: flex; flex-wrap: wrap; gap: 1rem; }
        .chart-col { flex: 1; min-width: 350px; }
        @media (max-width: 992px) { .chart-col { min-width: 100%; } }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            /* Reduce card spacing on mobile */
            .row.g-3 { gap: 0.75rem !important; }
            
            /* Make summary cards full width on mobile */
            .col-md-3.col-sm-6 {
                padding-left: 0.375rem;
                padding-right: 0.375rem;
            }
            
            /* Adjust card body padding */
            .card-body {
                padding: 1rem;
            }
            
            /* Make card titles smaller */
            .card-body h3 {
                font-size: 1.5rem;
            }
            
            .card-body h6 {
                font-size: 0.875rem;
            }
            
            /* Reduce icon size */
            .card .icon-bg {
                font-size: 4rem;
            }
            
            /* Make charts taller on mobile for better visibility */
            canvas {
                min-height: 300px !important;
            }
            
            /* Adjust chart card headers */
            .card-header {
                padding: 0.75rem 1rem;
            }
            
            .card-header h6 {
                font-size: 0.875rem;
                margin-bottom: 0.25rem;
            }
            
            .card-header small {
                font-size: 0.75rem;
            }
            
            /* Stack filter selects vertically on mobile */
            .card-header form.d-flex {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .card-header .form-select-sm {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            /* Further reduce spacing on small phones */
            .row.g-3.mb-4 {
                margin-bottom: 1rem !important;
            }
            
            /* Stack summary cards in single column */
            .col-md-3.col-sm-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            /* Reduce overall padding */
            .container-fluid.p-0 {
                padding: 0 0.5rem !important;
            }
            
            h1.h3 {
                font-size: 1.5rem;
            }
            
            /* Make charts even taller on small screens */
            canvas {
                min-height: 250px !important;
            }
        }
    </style>
</head>

<body>
<div class="wrapper">
    <?php $active = 'reports'; ?>
    <?php require ('../includes/sidebar_admin.php');?> 

    <div class="main">
        <?php require ('../includes/navbar_admin.php');?> 

        <main class="content">
            <div id="reportContent" class="container-fluid p-0">
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="bi bi-graph-up-arrow me-2"></i>Sales Reports & Analytics
                    </h1>
                    <p class="mb-0 mt-2" style="opacity: 0.9;">Track performance, analyze trends, and forecast sales</p>
                </div>

                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <?php 
                    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Delivered','Received')")->fetchColumn();
                    $unique_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE usertype_id = 2")->fetchColumn();
                    $sales_count = $pdo->query("SELECT SUM(amount) FROM orders WHERE status IN ('Delivered','Received')")->fetchColumn();
                    $aov = $pdo->query("SELECT SUM(amount)/COUNT(*) FROM orders WHERE status IN ('Delivered','Received')")->fetchColumn();
                    ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="card bg-gradient-blue text-white text-center">
                            <div class="card-body position-relative">
                                <h6 class="card-title">Total Orders</h6>
                                <h3><?= $total_orders ?></h3>
                                <i class="bi bi-cart icon-bg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card bg-gradient-green text-white text-center">
                            <div class="card-body position-relative">
                                <h6 class="card-title">Unique Customers</h6>
                                <h3><?= $unique_customers ?></h3>
                                <i class="bi bi-people icon-bg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card bg-gradient-yellow text-white text-center">
                            <div class="card-body position-relative">
                                <h6 class="card-title">Total Sales</h6>
                                <h3>₱<?= number_format($sales_count, 2) ?></h3>
                                <i class="bi bi-cash-stack icon-bg"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card bg-gradient-red text-white text-center">
                            <div class="card-body position-relative">
                                <h6 class="card-title">Avg. Order Value</h6>
                                <h3>₱<?= number_format($aov, 2) ?></h3>
                                <i class="bi bi-calculator icon-bg"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Charts -->
                <div class="chart-row mb-4">
                    <div class="chart-col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Actual Sales vs Predicted (<?= $yearSelected ?>)</h6>
                                    <small class="text-muted">Daily performance with AI forecasting baseline</small>
                                </div>
                                <form method="get" class="d-flex gap-2">
                                    <!-- Preserve other filters -->
                                    <input type="hidden" name="piemonth" value="<?= $pieMonthSelected ?>">
                                    <input type="hidden" name="pieyear" value="<?= $pieYearSelected ?>">
                                    <input type="hidden" name="forecastmonth" value="<?= $forecastMonthSelected ?>">
                                    <input type="hidden" name="forecastyear" value="<?= $forecastYearSelected ?>">
                                    <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($months as $m): ?>
                                            <option value="<?= $m ?>" <?= $m===$monthSelected?'selected':'' ?>><?= $m ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?= $y ?>" <?= $y==$yearSelected?'selected':'' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <canvas id="chart-actual"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-col">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Forecasted Sales (Next 3 Months)</h6>
                                <small class="text-muted">Advanced AI prediction with trend, seasonality & confidence intervals</small>
                            </div>
                            <div class="card-body">
                                <canvas id="chart-forecast"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Charts -->
                <div class="chart-row">
                    <div class="chart-col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Top-Selling Products (<?= $pieYearSelected ?>)</h6>
                                <form method="get" class="d-flex gap-2">
                                    <!-- Preserve other filters -->
                                    <input type="hidden" name="month" value="<?= $monthSelected ?>">
                                    <input type="hidden" name="year" value="<?= $yearSelected ?>">
                                    <input type="hidden" name="forecastmonth" value="<?= $forecastMonthSelected ?>">
                                    <input type="hidden" name="forecastyear" value="<?= $forecastYearSelected ?>">
                                    <select name="piemonth" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($months as $m): ?>
                                            <option value="<?= $m ?>" <?= $m===$pieMonthSelected?'selected':'' ?>><?= $m ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="pieyear" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?= $y ?>" <?= $y==$pieYearSelected?'selected':'' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <canvas id="pie-actual"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="chart-col">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Forecasted Top Products (compact)</h6>
                                    <small class="text-muted">Predicted top products for selected month</small>
                                </div>
                                <form method="get" class="d-flex gap-2">
                                    <!-- Preserve other filters -->
                                    <input type="hidden" name="month" value="<?= $monthSelected ?>">
                                    <input type="hidden" name="year" value="<?= $yearSelected ?>">
                                    <input type="hidden" name="piemonth" value="<?= $pieMonthSelected ?>">
                                    <input type="hidden" name="pieyear" value="<?= $pieYearSelected ?>">
                                    <select name="forecastmonth" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($months as $m): ?>
                                            <option value="<?= $m ?>" <?= $m===$forecastMonthSelected?'selected':'' ?>><?= $m ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="forecastyear" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <?php foreach ($years as $y): ?>
                                            <option value="<?= $y ?>" <?= $y==$forecastYearSelected?'selected':'' ?>><?= $y ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                            <div class="card-body">
                                <canvas id="pie-forecast"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <button id="downloadPDF" class="btn btn-danger mt-4">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
        </main>

        <footer class="footer">
            <div class="container-fluid"></div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
<script src="js/app.js"></script>
<script> feather.replace() </script>

<script>
function createGradient(ctx, colorStart, colorEnd){
    const gradient = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height);
    gradient.addColorStop(0, colorStart);
    gradient.addColorStop(1, colorEnd);
    return gradient;
}

// === Actual Sales Chart with Prediction ===
fetch("backend/fetch_chart_data.php?month=<?= $monthSelected ?>&year=<?= $yearSelected ?>")
.then(r => r.json())
.then(data => {
    const ctx = document.getElementById("chart-actual").getContext("2d");
    
    // Calculate accuracy metrics
    const actualSales = data.filter(d => d.sales > 0);
    const totalActual = actualSales.reduce((sum, d) => sum + d.sales, 0);
    const totalPredicted = actualSales.reduce((sum, d) => sum + d.predicted, 0);
    const accuracy = totalActual > 0 ? ((1 - Math.abs(totalActual - totalPredicted) / totalActual) * 100).toFixed(1) : 0;
    
    new Chart(ctx, {
        type: "line",
        data: {
            labels: data.map(d => "Day " + d.day),
            datasets: [
                {
                    label: "Actual Sales (₱)",
                    data: data.map(d => d.sales),
                    borderColor: "#007bff",
                    backgroundColor: createGradient(ctx,"rgba(0,123,255,0.2)","rgba(0,123,255,0)"),
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2
                },
                {
                    label: "Predicted Baseline (₱)",
                    data: data.map(d => d.predicted),
                    borderColor: "#28a745",
                    backgroundColor: "rgba(40,167,69,0.1)",
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 4
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            aspectRatio: window.innerWidth < 768 ? 1.2 : 2,
            plugins: { 
                legend: { display: true, position:"bottom" },
                tooltip: {
                    callbacks: {
                        footer: function(context) {
                            if (context[0].dataIndex === 0) {
                                return 'Forecast Accuracy: ' + accuracy + '%';
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

// === Forecasted Sales Chart (Next 3 Months - Advanced Algorithm) ===
// Pass context from Actual Sales filter to adjust forecast
fetch("backend/fetch_chart_data.php?forecast=1&contextmonth=<?= $monthSelected ?>&contextyear=<?= $yearSelected ?>")
.then(r => r.json())
.then(data => {
    const ctx = document.getElementById("chart-forecast").getContext("2d");

    if (!data || data.length === 0) {
        ctx.font = "16px Arial";
        ctx.fillStyle = "#666";
        ctx.textAlign = "center";
        ctx.fillText("Insufficient data for forecasting", ctx.canvas.width / 2, ctx.canvas.height / 2);
        return;
    }

    const predicted = data.map(d => d.predicted);
    const upper = data.map(d => d.upper);
    const lower = data.map(d => d.lower);
    const avgConfidence = data.reduce((sum, d) => sum + d.confidence, 0) / data.length;

    new Chart(ctx, {
        type: "line",
        data: {
            labels: data.map(d => d.month),
            datasets: [
                {
                    label: "Predicted Sales (₱)",
                    data: predicted,
                    borderColor: "#fd7e14",
                    backgroundColor: createGradient(ctx,"rgba(253,126,20,0.3)","rgba(253,126,20,0)"),
                    fill: true,
                    tension: 0.2,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: "#fd7e14",
                    pointBorderColor: "#fff",
                    pointBorderWidth: 2
                },
                {
                    label: "Upper Confidence Bound",
                    data: upper,
                    borderColor: 'rgba(220,53,69,0.6)',
                    backgroundColor: 'rgba(253,126,20,0.08)',
                    fill: '+1',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(220,53,69,0.4)',
                    pointBorderColor: 'rgba(220,53,69,0.8)',
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    tension: 0.5,
                    borderDash: [5, 3],
                    borderWidth: 2
                },
                {
                    label: "Lower Confidence Bound",
                    data: lower,
                    borderColor: 'rgba(40,167,69,0.6)',
                    backgroundColor: 'rgba(253,126,20,0.08)',
                    fill: '-2',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(40,167,69,0.4)',
                    pointBorderColor: 'rgba(40,167,69,0.8)',
                    pointBorderWidth: 1,
                    pointHoverRadius: 5,
                    tension: 0.5,
                    borderDash: [5, 3],
                    borderWidth: 2
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            aspectRatio: window.innerWidth < 768 ? 1.2 : 2,
            plugins: {
                legend: { display: true, position: 'bottom' },
                title: {
                    display: true,
                    text: 'Forecast Confidence: ' + avgConfidence.toFixed(1) + '%',
                    font: { size: 12, weight: 'normal' },
                    color: '#666'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '₱' + context.parsed.y.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

// === Pie Charts ===
function createPieChart(id, fetchURL){
    fetch(fetchURL)
    .then(r=>r.json())
    .then(data=>{
        const canvas = document.getElementById(id);
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (!data || data.length === 0) {
            ctx.fillStyle = "#f0f0f0";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.font = "16px Arial";
            ctx.fillStyle = "#666";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText("There is no sales made yet", canvas.width / 2, canvas.height / 2);
            return;
        }
        new Chart(ctx, {
            type: id.includes("forecast") ? "doughnut" : "pie",
            data: {
                labels: data.map(d=>d.product),
                datasets: [{
                    data: data.map(d=> d.quantity ?? d.predicted),
                    backgroundColor: ["#007bff","#28a745","#ffc107","#dc3545","#6f42c1","#20c997"]
                }]
            },
            options:{ 
                responsive:true, 
                maintainAspectRatio:true,
                aspectRatio: window.innerWidth < 768 ? 1 : 1.5,
                plugins:{ legend:{ position:"bottom" } } 
            }
        });
    });
}
createPieChart("pie-actual","backend/fetch_product_sales.php?piemonth=<?= $pieMonthSelected ?>&pieyear=<?= $pieYearSelected ?>");
createPieChart("pie-forecast","backend/fetch_product_sales.php?forecast=1&forecastmonth=<?= $forecastMonthSelected ?>&forecastyear=<?= $forecastYearSelected ?>");

// === Download PDF (Document Style) ===
document.getElementById("downloadPDF").addEventListener("click", function(e){
    e.preventDefault();
    
    // Show loading indicator
    const btn = e.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating...';
    
    // Open window immediately (before async operations) to prevent mobile popup blockers
    const printWindow = window.open('', '_blank');
    if (printWindow) {
        printWindow.document.write('<html><head><title>Generating Report...</title></head><body style="font-family: Arial, sans-serif; text-align: center; padding: 50px;"><h2>Generating your report...</h2><p>Please wait while we prepare your PDF.</p></body></html>');
    }
    
    // Now perform async operations
    generatePDF().then(pdfBlob => {
        // Restore button
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        
        if (printWindow && !printWindow.closed) {
            // Update the window with PDF
            printWindow.location.href = pdfBlob;
        } else {
            // Fallback: try opening new window
            const fallbackWindow = window.open(pdfBlob, '_blank');
            if (!fallbackWindow) {
                alert('Please allow popups for this site to print the report.');
            }
        }
    }).catch(error => {
        console.error('Error generating PDF:', error);
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        if (printWindow) printWindow.close();
        alert('Error generating report. Please try again.');
    });
});

async function generatePDF() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({
        orientation: "p",
        unit: "mm",
        format: "a4",
        putOnlyUsedFonts: true,
        floatPrecision: 16
    });
    
    // Page settings
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const margin = 15;
    let yPos = margin;
    // Load SAPIN logo
    async function loadImageAsDataURL(url) {
        const res = await fetch(url);
        const blob = await res.blob();
        return await new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(blob);
        });
    }
    const logoDataUrl = await loadImageAsDataURL('../assets/img/logo_forsapin.jpg');
    
    // Helper function to format numbers for PDF (avoid Unicode issues)
    function formatNumber(num) {
        const formatted = Number(num).toFixed(2);
        const parts = formatted.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return parts.join('.');
    }
    
    // Helper function to add new page if needed
    function checkPageBreak(requiredSpace) {
        if (yPos + requiredSpace > pageHeight - margin) {
            pdf.addPage();
            yPos = margin;
            return true;
        }
        return false;
    }
    
    // Header
    pdf.setFillColor(37, 99, 235);
    pdf.rect(0, 0, pageWidth, 40, 'F');
    if (logoDataUrl) {
        pdf.addImage(logoDataUrl, 'JPEG', margin, 8, 18, 18);
    }
    pdf.setTextColor(255, 255, 255);
    pdf.setFontSize(24);
    pdf.setFont(undefined, 'bold');
    pdf.text("SALES REPORT", pageWidth / 2, 20, { align: 'center' });
    pdf.setFontSize(12);
    pdf.setFont(undefined, 'normal');
    pdf.text("SAPIN", pageWidth / 2, 28, { align: 'center' });
    pdf.text("Generated: <?= date('F d, Y') ?>", pageWidth / 2, 35, { align: 'center' });
    
    yPos = 50;
    
    // Summary Section
    pdf.setTextColor(0, 0, 0);
    pdf.setFontSize(16);
    pdf.setFont(undefined, 'bold');
    pdf.text("Executive Summary", margin, yPos);
    yPos += 10;
    
    // Summary Cards Data
    const summaryData = [
        ['Total Orders', '<?= $total_orders ?>'],
        ['Unique Customers', '<?= $unique_customers ?>'],
        ['Total Sales', 'P<?= number_format($sales_count, 2, '.', ',') ?>'],
        ['Average Order Value', 'P<?= number_format($aov, 2, '.', ',') ?>']
    ];
    
    pdf.autoTable({
        startY: yPos,
        head: [['Metric', 'Value']],
        body: summaryData,
        theme: 'grid',
        styles: {
            font: 'helvetica',
            fontStyle: 'normal'
        },
        headStyles: { 
            fillColor: [37, 99, 235],
            fontSize: 11,
            fontStyle: 'bold',
            halign: 'center',
            font: 'helvetica'
        },
        bodyStyles: { 
            fontSize: 10,
            halign: 'left',
            font: 'helvetica'
        },
        columnStyles: {
            0: { cellWidth: 100, fontStyle: 'bold' },
            1: { cellWidth: 75, halign: 'right' }
        },
        margin: { left: margin, right: margin }
    });
    
    yPos = pdf.lastAutoTable.finalY + 15;
    
    // Top Products (Actual) - concise highlights
    try {
        checkPageBreak(60);
        pdf.setFontSize(14);
        pdf.setFont(undefined, 'bold');
        pdf.text("Top Products - <?= $pieMonthSelected ?> <?= $pieYearSelected ?>", margin, yPos);
        yPos += 8;
        const tpResponse = await fetch("backend/fetch_product_sales.php?piemonth=<?= $pieMonthSelected ?>&pieyear=<?= $pieYearSelected ?>");
        const tpData = await tpResponse.json();
        const topBody = (tpData || []).slice(0, 10).map((d, i) => [ (i+1).toString(), d.product, (d.quantity||0).toString() ]);
        pdf.autoTable({
            startY: yPos,
            head: [['Rank','Product','Units Sold']],
            body: topBody.length ? topBody : [["-","No data","-"]],
            theme: 'striped',
            styles: { font: 'helvetica' },
            headStyles: { 
                fillColor: [37,99,235], 
                fontSize: 10, 
                fontStyle: 'bold', 
                font: 'helvetica' 
            },
            bodyStyles: { 
                fontSize: 9, 
                font: 'helvetica' 
            },
            columnStyles: { 
                0:{cellWidth:20,halign:'center'}, 
                1:{cellWidth:110}, 
                2:{cellWidth:40,halign:'center'} 
            },
            margin: { left: margin, right: margin }
        });
        yPos = pdf.lastAutoTable.finalY + 12;
    } catch (error) {
        console.error('Error fetching top products:', error);
    }
    
    // Fetch and add Forecasted Sales Data
    try {
        const forecastResponse = await fetch("backend/fetch_chart_data.php?forecast=1&contextmonth=<?= $monthSelected ?>&contextyear=<?= $yearSelected ?>");
        const forecastData = await forecastResponse.json();
        
        if (forecastData && forecastData.length > 0) {
            checkPageBreak(60);
            
            pdf.setFontSize(14);
            pdf.setFont(undefined, 'bold');
            pdf.text("Forecasted Sales (Next 3 Months)", margin, yPos);
            yPos += 8;
            
            const avgConfidence = forecastData.reduce((sum, d) => sum + d.confidence, 0) / forecastData.length;
            pdf.setFontSize(10);
            pdf.setFont(undefined, 'normal');
            pdf.text(`Average Confidence: ${avgConfidence.toFixed(1)}%`, margin, yPos);
            yPos += 7;
            
            const forecastTableData = forecastData.map(d => [
                d.month,
                `P${formatNumber(d.predicted)}`,
                `P${formatNumber(d.lower)}`,
                `P${formatNumber(d.upper)}`,
                `${d.confidence.toFixed(1)}%`
            ]);
            
            pdf.autoTable({
                startY: yPos,
                head: [['Month', 'Predicted Sales', 'Lower Bound', 'Upper Bound', 'Confidence']],
                body: forecastTableData,
                theme: 'striped',
                styles: { font: 'helvetica' },
                headStyles: { 
                    fillColor: [253, 126, 20],
                    fontSize: 9,
                    fontStyle: 'bold',
                    font: 'helvetica'
                },
                bodyStyles: { fontSize: 9, font: 'helvetica' },
                columnStyles: {
                    0: { cellWidth: 35 },
                    1: { cellWidth: 40, halign: 'right' },
                    2: { cellWidth: 35, halign: 'right' },
                    3: { cellWidth: 35, halign: 'right' },
                    4: { cellWidth: 25, halign: 'center' }
                },
                margin: { left: margin, right: margin }
            });
            
            yPos = pdf.lastAutoTable.finalY + 15;
        }
    } catch (error) {
        console.error('Error fetching forecast data:', error);
    }
    
    // Fetch and add Top Products Data
    try {
        const productResponse = await fetch("backend/fetch_product_sales.php?piemonth=<?= $pieMonthSelected ?>&pieyear=<?= $pieYearSelected ?>");
        const productData = await productResponse.json();
        
        if (productData && productData.length > 0) {
            checkPageBreak(60);
            
            pdf.setFontSize(14);
            pdf.setFont(undefined, 'bold');
            pdf.text("Top-Selling Products - <?= $pieMonthSelected ?> <?= $pieYearSelected ?>", margin, yPos);
            yPos += 10;
            
            const productTableData = productData.map((d, index) => [
                (index + 1).toString(),
                d.product,
                d.quantity.toString(),
                `P${formatNumber(d.quantity * 100)}`
            ]);
            
            pdf.autoTable({
                startY: yPos,
                head: [['Rank', 'Product Name', 'Units Sold', 'Est. Revenue']],
                body: productTableData,
                theme: 'striped',
                styles: { font: 'helvetica' },
                headStyles: { 
                    fillColor: [40, 167, 69],
                    fontSize: 10,
                    fontStyle: 'bold',
                    font: 'helvetica'
                },
                bodyStyles: { fontSize: 9, font: 'helvetica' },
                columnStyles: {
                    0: { cellWidth: 20, halign: 'center' },
                    1: { cellWidth: 80 },
                    2: { cellWidth: 30, halign: 'center' },
                    3: { cellWidth: 40, halign: 'right' }
                },
                margin: { left: margin, right: margin }
            });
            
            yPos = pdf.lastAutoTable.finalY + 15;
        }
    } catch (error) {
        console.error('Error fetching product data:', error);
    }
    
    // Fetch and add Forecasted Products Data
    try {
        const forecastProductResponse = await fetch("backend/fetch_product_sales.php?forecast=1&forecastmonth=<?= $forecastMonthSelected ?>&forecastyear=<?= $forecastYearSelected ?>");
        const forecastProductData = await forecastProductResponse.json();
        
        if (forecastProductData && forecastProductData.length > 0) {
            checkPageBreak(60);
            
            pdf.setFontSize(14);
            pdf.setFont(undefined, 'bold');
            pdf.text("Forecasted Product Sales - <?= $forecastMonthSelected ?> <?= $forecastYearSelected ?>", margin, yPos);
            yPos += 10;
            
            const forecastProductTableData = forecastProductData.map((d, index) => [
                (index + 1).toString(),
                d.product,
                d.predicted.toString()
            ]);
            
            pdf.autoTable({
                startY: yPos,
                head: [['Rank', 'Product Name', 'Predicted Units']],
                body: forecastProductTableData,
                theme: 'striped',
                styles: { font: 'helvetica' },
                headStyles: { 
                    fillColor: [111, 66, 193],
                    fontSize: 10,
                    fontStyle: 'bold',
                    font: 'helvetica'
                },
                bodyStyles: { fontSize: 9, font: 'helvetica' },
                columnStyles: {
                    0: { cellWidth: 20, halign: 'center' },
                    1: { cellWidth: 120 },
                    2: { cellWidth: 40, halign: 'center' }
                },
                margin: { left: margin, right: margin }
            });
        }
    } catch (error) {
        console.error('Error fetching forecast product data:', error);
    }
    
    // Footer on last page
    const totalPages = pdf.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        pdf.setPage(i);
        pdf.setFontSize(8);
        pdf.setTextColor(128, 128, 128);
        pdf.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
        pdf.text("SAPIN - Confidential", margin, pageHeight - 10);
    }
    
    // Enable auto-print
    pdf.autoPrint();
    
    // Return the PDF blob URL
    return pdf.output('bloburl');
}
</script>
</body>
</html>





