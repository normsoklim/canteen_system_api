<!DOCTYPE html>
<html>
<head>
    <title>Canteen API - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            padding: 40px 0; 
            background-color: #f8f9fa; 
        }
        .card { 
            max-width: 600px; 
            margin: 0 auto; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-5 text-center">
                    <h1 class="mb-4">Canteen API Dashboard</h1>
                    <p class="lead">Welcome to the Canteen Management System API</p>
                    
                    <div class="alert alert-info mt-3">
                        <strong>Server Status:</strong> Running
                        <br>
                        <small>API endpoint: /api</small>
                    </div>
                    
                    <div class="d-grid gap-3 mt-4">
                        <a href="/auth-helper" class="btn btn-warning btn-lg">
                            <i class="fas fa-user-lock me-2"></i>Login/Register First
                        </a>
                        <a href="/menu-items" class="btn btn-success btn-lg">
                            <i class="fas fa-utensils me-2"></i>View Menu Items
                        </a>
                        <a href="/bakong-payment" class="btn btn-primary btn-lg">
                            <i class="fas fa-qrcode me-2"></i>Bakong Payment UI
                        </a>
                        <a href="/test-bakong-qr" class="btn btn-info btn-lg">
                            <i class="fas fa-mobile-alt me-2"></i>Test Bakong QR
                        </a>
                        <a href="/bakong-test-guide" class="btn btn-secondary btn-lg">
                            <i class="fas fa-book me-2"></i>Test Guide
                        </a>
                        <a href="/api" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-code me-2"></i>API Documentation
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Quick Start</h5>
                        <p class="text-muted">Use the links above to access different parts of the Bakong payment system</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>