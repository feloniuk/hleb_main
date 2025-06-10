<?php
/**
 * Друк штрих-коду продукту
 * 
 * Файл: modules/admin/print_barcode.php
 */

// Отримання параметрів
$barcode = isset($_GET['code']) ? $_GET['code'] : '';
$productName = isset($_GET['name']) ? urldecode($_GET['name']) : '';

// Валідація штрих-коду
if (!preg_match('/^\d{8}$/', $barcode)) {
    die('Невірний формат штрих-коду');
}

// Форматування штрих-коду для відображення
$formattedBarcode = substr($barcode, 0, 4) . '-' . substr($barcode, 4, 4);

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Друк штрих-коду - <?php echo htmlspecialchars($productName); ?></title>
    <style>
        @page {
            size: 80mm 50mm;
            margin: 0;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f0f0f0;
        }
        
        .label-container {
            width: 80mm;
            height: 50mm;
            background: white;
            padding: 5mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .product-name {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .barcode-container {
            margin: 10px 0;
            text-align: center;
        }
        
        .barcode {
            font-family: 'Libre Barcode 128', monospace;
            font-size: 48px;
            line-height: 1;
            letter-spacing: 2px;
        }
        
        .barcode-text {
            font-family: monospace;
            font-size: 16px;
            margin-top: 5px;
            letter-spacing: 3px;
        }
        
        .barcode-visual {
            display: inline-block;
            height: 40px;
            background: linear-gradient(
                to right,
                #000 0%, #000 2%, #fff 2%, #fff 4%,
                #000 4%, #000 8%, #fff 8%, #fff 10%,
                #000 10%, #000 12%, #fff 12%, #fff 16%,
                #000 16%, #000 18%, #fff 18%, #fff 20%,
                #000 20%, #000 24%, #fff 24%, #fff 26%,
                #000 26%, #000 28%, #fff 28%, #fff 32%,
                #000 32%, #000 34%, #fff 34%, #fff 36%,
                #000 36%, #000 40%, #fff 40%, #fff 42%,
                #000 42%, #000 44%, #fff 44%, #fff 48%,
                #000 48%, #000 50%, #fff 50%, #fff 52%,
                #000 52%, #000 56%, #fff 56%, #fff 58%,
                #000 58%, #000 60%, #fff 60%, #fff 64%,
                #000 64%, #000 66%, #fff 66%, #fff 68%,
                #000 68%, #000 72%, #fff 72%, #fff 74%,
                #000 74%, #000 76%, #fff 76%, #fff 80%,
                #000 80%, #000 82%, #fff 82%, #fff 84%,
                #000 84%, #000 88%, #fff 88%, #fff 90%,
                #000 90%, #000 92%, #fff 92%, #fff 96%,
                #000 96%, #000 98%, #fff 98%, #fff 100%
            );
            width: 200px;
            margin: 10px 0;
        }
        
        .company-name {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background: #0d6efd;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0b5ed7;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5c636a;
        }
        
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            
            .label-container {
                box-shadow: none;
                width: 80mm;
                height: 50mm;
                margin: 0;
                padding: 5mm;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        /* Canvas для справжнього штрих-коду */
        #barcodeCanvas {
            margin: 10px 0;
        }
    </style>
    
    <!-- Підключення бібліотеки JsBarcode -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Друкувати
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Закрити
        </button>
    </div>
    
    <div class="label-container">
        <div class="product-name"><?php echo htmlspecialchars($productName); ?></div>
        
        <div class="barcode-container">
            <!-- Canvas для штрих-коду -->
            <canvas id="barcodeCanvas"></canvas>
        </div>
        
        <div class="barcode-text"><?php echo $formattedBarcode; ?></div>
        
        <div class="company-name">ТОВ "Одеський Коровай"</div>
    </div>
    
    <script>
        // Генерація штрих-коду
        JsBarcode("#barcodeCanvas", "<?php echo $barcode; ?>", {
            format: "CODE128",
            width: 2,
            height: 50,
            displayValue: false,
            margin: 0
        });
        
        // Автоматичний друк (опціонально)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 500);
        // };
    </script>
    
    <!-- Font Awesome для іконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>