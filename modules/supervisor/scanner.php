<?php
$pageTitle = 'Сканер штрих-кодів';

require_once '../../config/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Перевірка доступу
if (!checkAccess(['brigadir'])) {
    header("Location: ../../index.php");
    exit;
}

$connection = connectDatabase();
$success = '';
$error = '';

include_once '../../includes/header.php';
?>

<!-- Головне меню -->
<div class="row mb-4">
    <div class="col-md-12">
        <nav class="nav main-menu nav-pills nav-fill">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Головна
            </a>
            <a class="nav-link" href="orders.php">
                <i class="fas fa-clipboard-list"></i> Замовлення
            </a>
            
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-calendar-day"></i> Зміни
            </a>
            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                <a class="dropdown-item" href="shifts_day.php">Денна зміна</a>
                <a class="dropdown-item" href="shifts_night.php">Нічна зміна</a>
            </div>
            <a class="nav-link active" href="scanner.php">
                <i class="fas fa-barcode"></i> Сканер
            </a>
            <a class="nav-link" href="products.php">
                <i class="fas fa-bread-slice"></i> Продукція
            </a>
            <a class="nav-link" href="video.php">
                <i class="fas fa-video"></i> Відео
            </a>
        </nav>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-barcode me-2"></i> Сканер штрих-коду
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4 text-center">
                    <img src="../../assets/img/barcode-scan.svg" alt="Сканер" style="width: 150px; height: auto;" class="mb-3">
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Підключіть сканер штрих-коду або введіть штрих-код вручну
                    </p>
                </div>
                
                <div class="form-group mb-3">
                    <label for="barcode-input" class="form-label">Штрих-код:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                        <input type="text" id="barcode-input" class="form-control" placeholder="Введіть або відскануйте штрих-код" autofocus>
                        <button type="button" id="scan-button" class="btn btn-primary">
                            <i class="fas fa-search"></i> Пошук
                        </button>
                    </div>
                </div>
                
                <div id="alerts-container">
                    <!-- Сповіщення додаються динамічно через JavaScript -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list me-2"></i> Відскановані продукти
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="scanned-products-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Назва</th>
                                <th>Кількість</th>
                                <th>Ціна (грн)</th>
                                <th>Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Записи будуть додані через JavaScript -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Загальна сума:</strong></td>
                                <td colspan="2" id="total-price">0.00 грн</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-3">
                    <button type="button" id="clear-all-button" class="btn btn-warning">
                        <i class="fas fa-trash me-1"></i> Очистити все
                    </button>
                    <button type="button" id="save-button" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Зберегти
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Інформація про продукт (модальне вікно) -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Інформація про продукт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="product-image" src="" alt="Зображення продукту" class="img-fluid rounded" style="max-height: 200px;">
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">ID продукту:</div>
                    <div class="col-8" id="product-id"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Назва:</div>
                    <div class="col-8" id="product-name"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Вага:</div>
                    <div class="col-8" id="product-weight"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Строк реалізації:</div>
                    <div class="col-8" id="product-expiry"></div>
                </div>
                <div class="row mb-2">
                    <div class="col-4 fw-bold">Ціна:</div>
                    <div class="col-8" id="product-price"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                <button type="button" class="btn btn-primary" id="add-to-list-button">Додати до списку</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Змінна для зберігання поточного продукту
    let currentProduct = null;
    
    // Обробник події для кнопки сканування
    document.getElementById('scan-button').addEventListener('click', function() {
        scanProduct();
    });
    
    // Обробник події для поля введення штрих-коду (реагування на Enter)
    document.getElementById('barcode-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            scanProduct();
        }
    });
    
    // Обробник для кнопки "Додати до списку"
    document.getElementById('add-to-list-button').addEventListener('click', function() {
        if (currentProduct) {
            addProductToTable(currentProduct);
            var productModal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            productModal.hide();
            
            // Очистити поле вводу і повернути на нього фокус
            document.getElementById('barcode-input').value = '';
            document.getElementById('barcode-input').focus();
        }
    });
    
    // Обробник для кнопки "Очистити все"
    document.getElementById('clear-all-button').addEventListener('click', function() {
        if (confirm('Ви впевнені, що хочете очистити весь список?')) {
            document.getElementById('scanned-products-table').querySelector('tbody').innerHTML = '';
            updateTotalPrice();
            
            // Показати повідомлення
            showAlert('info', 'Список продуктів очищено.');
        }
    });
    
    // Обробник для кнопки "Зберегти"
    document.getElementById('save-button').addEventListener('click', function() {
        saveScannedProducts();
    });
    
    // Обробник для видалення продукту зі списку
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product')) {
            var row = e.target.closest('tr');
            var productName = row.querySelector('td:nth-child(2)').textContent;
            
            if (confirm(`Ви впевнені, що хочете видалити "${productName}" зі списку?`)) {
                row.remove();
                updateTotalPrice();
                
                // Показати повідомлення
                showAlert('warning', `Продукт "${productName}" видалено зі списку.`);
            }
        }
    });
    
    // Функція сканування продукту
    function scanProduct() {
        var barcode = document.getElementById('barcode-input').value.trim();
        
        if (!barcode) {
            showAlert('warning', 'Будь ласка, введіть або відскануйте штрих-код.');
            return;
        }
        
        // AJAX запит для отримання інформації про продукт
        fetch('../../api/get_product.php?barcode=' + encodeURIComponent(barcode))
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Зберегти поточний продукт
                    currentProduct = data.product;
                    
                    // Заповнити модальне вікно даними про продукт
                    document.getElementById('product-id').textContent = data.product.id;
                    document.getElementById('product-name').textContent = data.product.nazvanie;
                    document.getElementById('product-weight').textContent = data.product.ves + ' кг';
                    document.getElementById('product-expiry').textContent = data.product.srok + ' годин';
                    document.getElementById('product-price').textContent = data.product.zena + ' грн';
                    
                    // Встановити зображення продукту
                    var imagePath = data.product.image ? '../../' + data.product.image : '../../assets/img/product-placeholder.jpg';
                    document.getElementById('product-image').src = imagePath;
                    
                    // Показати модальне вікно
                    var productModal = new bootstrap.Modal(document.getElementById('productModal'));
                    productModal.show();
                } else {
                    // Показати повідомлення про помилку
                    showAlert('danger', 'Помилка: ' + data.message);
                }
            })
            .catch(error => {
                showAlert('danger', 'Помилка при отриманні даних про продукт.');
                console.error('Error:', error);
            });
    }
    
    // Функція додавання продукту до таблиці
    function addProductToTable(product) {
        var tbody = document.getElementById('scanned-products-table').querySelector('tbody');
        
        // Перевірити, чи продукт вже є в таблиці
        var existingRow = tbody.querySelector('tr[data-product-id="' + product.id + '"]');
        
        if (existingRow) {
            // Збільшити кількість
            var quantityCell = existingRow.querySelector('.product-quantity');
            var quantity = parseInt(quantityCell.textContent) + 1;
            quantityCell.textContent = quantity;
        } else {
            // Додати новий рядок
            var newRow = document.createElement('tr');
            newRow.setAttribute('data-product-id', product.id);
            
            newRow.innerHTML = `
                <td>${product.id}</td>
                <td>${product.nazvanie}</td>
                <td class="product-quantity">1</td>
                <td>${product.zena} грн</td>
                <td><button type="button" class="btn btn-sm btn-danger remove-product"><i class="fas fa-trash"></i></button></td>
            `;
            
            tbody.appendChild(newRow);
        }
        
        // Оновити загальну суму
        updateTotalPrice();
        
        // Показати повідомлення про успіх
        showAlert('success', 'Продукт "' + product.nazvanie + '" додано до списку.');
    }
    
    // Функція оновлення загальної суми
    function updateTotalPrice() {
        var total = 0;
        var rows = document.getElementById('scanned-products-table').querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            var quantity = parseInt(row.querySelector('.product-quantity').textContent);
            var price = parseFloat(row.querySelector('td:nth-child(4)').textContent.replace(' грн', ''));
            
            total += quantity * price;
        });
        
        document.getElementById('total-price').textContent = total.toFixed(2) + ' грн';
    }
    
    // Функція збереження відсканованих продуктів
    function saveScannedProducts() {
        var rows = document.getElementById('scanned-products-table').querySelectorAll('tbody tr');
        
        if (rows.length === 0) {
            showAlert('warning', 'Список продуктів порожній. Відскануйте продукти перед збереженням.');
            return;
        }
        
        var products = [];
        
        rows.forEach(function(row) {
            products.push({
                id: row.getAttribute('data-product-id'),
                quantity: parseInt(row.querySelector('.product-quantity').textContent)
            });
        });
        
        // AJAX запит для збереження даних
        fetch('../../api/save_scanned_products.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ products: products })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Очистити таблицю
                document.getElementById('scanned-products-table').querySelector('tbody').innerHTML = '';
                updateTotalPrice();
                
                // Показати повідомлення про успіх
                showAlert('success', 'Дані успішно збережено.');
            } else {
                // Показати повідомлення про помилку
                showAlert('danger', 'Помилка при збереженні даних: ' + data.message);
            }
        })
        .catch(error => {
            showAlert('danger', 'Помилка при збереженні даних.');
            console.error('Error:', error);
        });
    }
    
    // Функція для показу повідомлень
    function showAlert(type, message) {
        var alertsContainer = document.getElementById('alerts-container');
        
        // Створити елемент повідомлення
        var alertElement = document.createElement('div');
        alertElement.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alertElement.setAttribute('role', 'alert');
        
        alertElement.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Додати повідомлення на сторінку
        alertsContainer.appendChild(alertElement);
        
        // Автоматично видалити повідомлення через 5 секунд
        setTimeout(function() {
            alertElement.classList.remove('show');
            
            setTimeout(function() {
                alertElement.remove();
            }, 300);
        }, 5000);
    }
    
    // Фокус на полі введення штрих-коду при завантаженні сторінки
    window.addEventListener('load', function() {
        document.getElementById('barcode-input').focus();
    });
</script>

<?php
include_once '../../includes/footer.php';
?>