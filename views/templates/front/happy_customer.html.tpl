<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Heureux de Dolzay</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            overflow: hidden;
            height: 100%;
        }
        .dolzay-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        .logo {
            max-width: 250px;
            width: 100%;
            height: auto;
            margin-bottom: 30px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 20px;
            max-width: 600px;
        }
        .gradient-line {
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #6B4BFF 0%, #45E3FF 50%, #FF9B45 100%);
            margin: 0 auto;
        }
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            100% { transform: translateY(-100vh) rotate(360deg); }
        }

        /* Mobile-friendly adjustments */
        @media (max-width: 768px) {
            .logo {
                max-width: 200px;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 28px;
                max-width: 90%;
            }
            .gradient-line {
                width: 60px;
            }
        }

        @media (max-width: 480px) {
            .logo {
                max-width: 150px;
                margin-bottom: 15px;
            }
            h1 {
                font-size: 24px;
            }
            .gradient-line {
                width: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape" style="top: 20%; left: 10%; width: 50px; height: 50px; background-color: #6B4BFF;"></div>
        <div class="shape" style="top: 40%; left: 20%; width: 30px; height: 30px; background-color: #45E3FF;"></div>
        <div class="shape" style="top: 60%; left: 80%; width: 40px; height: 40px; background-color: #FF9B45;"></div>
        <div class="shape" style="top: 80%; left: 40%; width: 60px; height: 60px; background-color: #6B4BFF;"></div>
    </div>

    <div class="dolzay-container">
        <img src="{$module_base_link}/modules/dolzay/uploads/dolzay-logo.png" alt="Logo Dolzay" class="logo">

        <h1>{$domain_name} recommande vivement le plugin Dolzay !</h1>
        <div class="gradient-line"></div>
    </div>
</body>
</html>