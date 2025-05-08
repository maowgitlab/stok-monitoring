<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi PIN - {{ config('app.name', 'Monitoring Stok') }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #2c3e50;
        }

        .pin-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
        }

        .pin-container h2 {
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            color: #fff;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
            margin: -2rem -2rem 2rem;
            text-align: center;
        }

        .form-control {
            border-radius: 10px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: #1abc9c;
            border: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: #16a085;
            transform: translateY(-2px);
        }

        .alert {
            font-size: 0.9rem;
            padding: 0.5rem;
            border-radius: 10px;
        }

        @media (max-width: 576px) {
            .pin-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            .pin-container h2 {
                margin: -1.5rem -1.5rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="pin-container">
        <h2>Verifikasi PIN</h2>
        @if(session('error'))
            <div class="alert alert-danger" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <form method="POST" action="{{ route('pin.verify') }}">
            @csrf
            <div class="mb-3">
                <label for="pin" class="form-label">PIN (4 digit)</label>
                <input type="password" name="pin" id="pin" class="form-control @error('pin') is-invalid @enderror" 
                       maxlength="4" pattern="\d{4}" required autocomplete="off">
                @error('pin')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Verifikasi</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>