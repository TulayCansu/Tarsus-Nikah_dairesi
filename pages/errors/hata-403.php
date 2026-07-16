<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yetkisiz Erişim - 403</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .error-card {
            background: white;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        h1 {
            color: #dc3545;
            font-size: 72px;
            margin: 0 0 10px 0;
        }
        h3 {
            color: #333;
            margin: 0 0 15px 0;
        }
        p {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

    <div class="error-card">
        <h1>403</h1>
        <h3>Erişim Yetkiniz Yok!</h3>
        <p>Bu sayfayı görüntülemek için gerekli yetkilere sahip değilsiniz. Bir hata olduğunu düşünüyorsanız lütfen sistem yöneticinizle iletişime geçin.</p>
        <a href="../dashboard/dashboard.php" class="btn">Yönetim Paneline Dön</a>
    </div>

</body>
</html>