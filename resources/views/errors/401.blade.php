<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 32px;
            color: #333;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">🔒</div>
        <div class="error-code">401</div>
        <h1 class="error-title">Tidak Dibenarkan / Unauthorized</h1>
        <p class="error-message">
            @if(isset($message))
                {{ $message }}
            @else
                Anda tidak mempunyai akses ke halaman ini. Sila log masuk terlebih dahulu.<br>
                You don't have access to this page. Please login first.
            @endif
        </p>
        <a href="{{ url('/') }}" class="btn">Kembali ke Halaman Utama / Back to Home</a>
    </div>
</body>
</html>
