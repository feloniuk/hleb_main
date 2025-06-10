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
            <a class="nav-link" href="videos.php">
                <i class="fas fa-video"></i> Відео
            </a>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-barcode me-2"></i> Сканер штрих-коду
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-4 text-center">
                    <div class="scanner-animation mb-3">
                        <i class="fas fa-barcode fa-5x text-primary"></i>
                        <div class="scanner-line"></div>
                    </div>
                    <p class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Підключіть сканер штрих-коду або введіть код вручну
                    </p>
                </div>
                
                <div class="form-group mb-3">
                    <label for="barcode-input" class="form-label fw-bold">Штрих-код (8 цифр):</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                        <input type="text" 
                               id="barcode-input" 
                               class="form-control" 
                               placeholder="Введіть або відскануйте 8-значний код" 
                               pattern="\d{8}" 
                               maxlength="8"
                               autocomplete="off"
                               autofocus>
                        <button type="button" id="scan-button" class="btn btn-primary">
                            <i class="fas fa-search"></i> Пошук
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        <i class="fas fa-lightbulb me-1"></i>
                        Формат: 10000001, 10000002, 10000003...
                    </small>
                </div>
                
                <!-- Швидкі кнопки для тестування -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <small class="text-muted me-2">Тестові коди:</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-code" data-code="10000001">10000001</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-code" data-code="10000002">10000002</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary quick-code" data-code="10000003">10000003</button>
                </div>
                
                <div id="scan-status" class="alert d-none" role="alert">
                    <!-- Статус сканування -->
                </div>
            </div>
        </div>
        
        <!-- Інформація про останнє сканування -->
        <div class="card" id="last-scan-info" style="display: none;">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-history me-2"></i> Останнє сканування
                </h6>
            </div>
            <div class="card-body">
                <div id="last-scan-details">
                    <!-- Деталі останнього сканування -->
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i> Відскановані продукти
                    </h5>
                    <span class="badge bg-white text-success" id="items-count">0</span>
                </div>
            </div>
            <div class="card-body">
                <div id="empty-list-message" class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Список порожній. Відскануйте продукти для додавання.</p>
                </div>
                
                <div class="table-responsive" id="products-table-wrapper" style="display: none;">
                    <table class="table table-striped table-hover" id="scanned-products-table">
                        <thead class="table-light">
                            <tr>
                                <th width="50">ID</th>
                                <th>Назва</th>
                                <th width="100">К-сть</th>
                                <th width="100">Ціна</th>
                                <th width="100">Сума</th>
                                <th width="50">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Записи будуть додані через JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Підсумок -->
                <div class="card mt-3" id="summary-card" style="display: none;">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Всього позицій:</small>
                                <h5 id="total-items">0</h5>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted">Загальна сума:</small>
                                <h5 class="text-success" id="total-price">0.00 грн</h5>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-3" id="action-buttons" style="display: none;">
                    <button type="button" id="clear-all-button" class="btn btn-warning">
                        <i class="fas fa-trash me-1"></i> Очистити все
                    </button>
                    <button type="button" id="save-button" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Зберегти замовлення
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальне вікно інформації про продукт -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="productModalLabel">
                    <i class="fas fa-info-circle me-2"></i> Інформація про продукт
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="product-image" src="" alt="Зображення продукту" class="img-fluid rounded shadow" style="max-height: 200px;">
                </div>
                <table class="table table-borderless">
                    <tr>
                        <td class="fw-bold text-muted" width="40%">ID продукту:</td>
                        <td id="product-id"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Штрих-код:</td>
                        <td><code id="product-barcode" class="fs-5"></code></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Назва:</td>
                        <td id="product-name" class="fw-bold"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Вага:</td>
                        <td id="product-weight"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Строк зберігання:</td>
                        <td id="product-expiry"></td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Ціна:</td>
                        <td class="fs-5 text-success fw-bold" id="product-price"></td>
                    </tr>
                </table>
                <div class="mt-3">
                    <label for="product-quantity" class="form-label">Кількість:</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-outline-secondary" id="quantity-minus">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="form-control text-center" id="product-quantity" value="1" min="1" max="999">
                        <button type="button" class="btn btn-outline-secondary" id="quantity-plus">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Скасувати
                </button>
                <button type="button" class="btn btn-primary" id="add-to-list-button">
                    <i class="fas fa-plus-circle me-1"></i> Додати до списку
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.scanner-animation {
    position: relative;
    display: inline-block;
}

.scanner-line {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, #0d6efd, transparent);
    animation: scan 2s infinite;
}

@keyframes scan {
    0% { transform: translateY(0); opacity: 0; }
    50% { opacity: 1; }
    100% { transform: translateY(-80px); opacity: 0; }
}

.quick-code:hover {
    transform: scale(1.05);
}

#scanned-products-table tbody tr {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
// Глобальні змінні
let currentProduct = null;
let scannedProducts = [];
let audioContext = null;

// Ініціалізація при завантаженні сторінки
document.addEventListener('DOMContentLoaded', function() {
    // Ініціалізація аудіо контексту
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Фокус на полі введення
    document.getElementById('barcode-input').focus();
    
    // Обробники подій
    initializeEventHandlers();
});

// Ініціалізація обробників подій
function initializeEventHandlers() {
    // Кнопка сканування
    document.getElementById('scan-button').addEventListener('click', scanProduct);
    
    // Поле введення штрих-коду
    const barcodeInput = document.getElementById('barcode-input');
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            scanProduct();
        }
    });
    
    // Автоматичне форматування вводу
    barcodeInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 8) {
            value = value.substr(0, 8);
        }
        e.target.value = value;
        
        // Автоматичний пошук при введенні 8 цифр
        if (value.length === 8) {
            scanProduct();
        }
    });
    
    // Швидкі кнопки тестових кодів
    document.querySelectorAll('.quick-code').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('barcode-input').value = this.dataset.code;
            scanProduct();
        });
    });
    
    // Кнопки кількості в модальному вікні
    document.getElementById('quantity-minus').addEventListener('click', function() {
        const input = document.getElementById('product-quantity');
        if (input.value > 1) {
            input.value = parseInt(input.value) - 1;
        }
    });
    
    document.getElementById('quantity-plus').addEventListener('click', function() {
        const input = document.getElementById('product-quantity');
        input.value = parseInt(input.value) + 1;
    });
    
    // Кнопка додавання до списку
    document.getElementById('add-to-list-button').addEventListener('click', function() {
        if (currentProduct) {
            const quantity = parseInt(document.getElementById('product-quantity').value);
            addProductToTable(currentProduct, quantity);
            
            // Закриття модального вікна
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
            
            // Очищення поля та фокус
            document.getElementById('barcode-input').value = '';
            document.getElementById('barcode-input').focus();
            
            // Скидання кількості
            document.getElementById('product-quantity').value = 1;
        }
    });
    
    // Кнопка очищення списку
    document.getElementById('clear-all-button').addEventListener('click', function() {
        if (confirm('Ви впевнені, що хочете очистити весь список?')) {
            clearProductsList();
        }
    });
    
    // Кнопка збереження
    document.getElementById('save-button').addEventListener('click', saveScannedProducts);
    
    // Делегування подій для видалення продуктів
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-product')) {
            const row = e.target.closest('tr');
            const productId = row.dataset.productId;
            removeProductFromList(productId);
        }
        
        // Зміна кількості
        if (e.target.closest('.quantity-change')) {
            const row = e.target.closest('tr');
            const productId = row.dataset.productId;
            const change = e.target.closest('.quantity-change').dataset.change;
            changeProductQuantity(productId, parseInt(change));
        }
    });
}

// Функція сканування продукту
function scanProduct() {
    const barcode = document.getElementById('barcode-input').value.trim();
    
    if (!barcode) {
        showStatus('warning', 'Введіть або відскануйте штрих-код');
        return;
    }
    
    if (!/^\d{8}$/.test(barcode)) {
        showStatus('danger', 'Штрих-код має бути 8-значним числом');
        playBeep('error');
        return;
    }
    
    // Показуємо статус завантаження
    showStatus('info', '<i class="fas fa-spinner fa-spin me-2"></i>Пошук продукту...');
    document.getElementById('scan-button').disabled = true;
    
    // AJAX запит
    fetch('../../api/get_product.php?barcode=' + encodeURIComponent(barcode))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentProduct = data.product;
                showProductInfo(data.product);
                showStatus('success', 'Продукт знайдено: ' + data.product.nazvanie);
                playBeep('success');
                
                // Показати останнє сканування
                showLastScan(data.product);
            } else {
                showStatus('danger', data.message);
                playBeep('error');
            }
        })
        .catch(error => {
            showStatus('danger', 'Помилка при отриманні даних');
            console.error('Error:', error);
            playBeep('error');
        })
        .finally(() => {
            document.getElementById('scan-button').disabled = false;
        });
}

// Показати інформацію про продукт
function showProductInfo(product) {
    // Заповнення модального вікна
    document.getElementById('product-id').textContent = product.id;
    document.getElementById('product-barcode').textContent = formatBarcode(product.barcode);
    document.getElementById('product-name').textContent = product.nazvanie;
    document.getElementById('product-weight').textContent = product.ves + ' кг';
    document.getElementById('product-expiry').textContent = product.srok + ' годин';
    document.getElementById('product-price').textContent = product.zena + ' грн';
    
    // Зображення
    const imagePath = product.image_url || '../../assets/img/product-placeholder.jpg';
    document.getElementById('product-image').src = imagePath;
    
    // Показати модальне вікно
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

// Показати останнє сканування
function showLastScan(product) {
    const lastScanInfo = document.getElementById('last-scan-info');
    const lastScanDetails = document.getElementById('last-scan-details');
    
    lastScanDetails.innerHTML = `
        <div class="d-flex align-items-center">
            <img src="${product.image_url || '../../assets/img/product-placeholder.jpg'}" 
                 class="rounded me-3" 
                 style="width: 60px; height: 60px; object-fit: cover;">
            <div>
                <h6 class="mb-1">${product.nazvanie}</h6>
                <small class="text-muted">Код: ${formatBarcode(product.barcode)} | Ціна: ${product.zena} грн</small>
            </div>
        </div>
    `;
    
    lastScanInfo.style.display = 'block';
}

// Додати продукт до таблиці
function addProductToTable(product, quantity = 1) {
    // Перевірка чи продукт вже є в списку
    const existingProduct = scannedProducts.find(p => p.id === product.id);
    
    if (existingProduct) {
        // Збільшити кількість
        existingProduct.quantity += quantity;
        updateProductRow(product.id);
    } else {
        // Додати новий продукт
        scannedProducts.push({
            ...product,
            quantity: quantity
        });
        addProductRow(product, quantity);
    }
    
    updateUI();
    showStatus('success', `Додано: ${product.nazvanie} (${quantity} шт.)`);
}

// Додати рядок продукту
function addProductRow(product, quantity) {
    const tbody = document.querySelector('#scanned-products-table tbody');
    const row = document.createElement('tr');
    row.dataset.productId = product.id;
    
    const total = (parseFloat(product.zena) * quantity).toFixed(2);
    
    row.innerHTML = `
        <td>${product.id}</td>
        <td>${product.nazvanie}</td>
        <td>
            <div class="input-group input-group-sm">
                <button class="btn btn-outline-secondary quantity-change" data-change="-1">-</button>
                <input type="text" class="form-control text-center quantity-input" value="${quantity}" readonly>
                <button class="btn btn-outline-secondary quantity-change" data-change="1">+</button>
            </div>
        </td>
        <td>${product.zena} грн</td>
        <td class="product-total">${total} грн</td>
        <td>
            <button class="btn btn-sm btn-danger remove-product">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(row);
}

// Оновити рядок продукту
function updateProductRow(productId) {
    const product = scannedProducts.find(p => p.id === productId);
    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
    
    if (product && row) {
        row.querySelector('.quantity-input').value = product.quantity;
        const total = (parseFloat(product.zena) * product.quantity).toFixed(2);
        row.querySelector('.product-total').textContent = total + ' грн';
    }
}

// Змінити кількість продукту
function changeProductQuantity(productId, change) {
    const product = scannedProducts.find(p => p.id === productId);
    
    if (product) {
        product.quantity += change;
        
        if (product.quantity <= 0) {
            removeProductFromList(productId);
        } else {
            updateProductRow(productId);
            updateUI();
        }
    }
}

// Видалити продукт зі списку
function removeProductFromList(productId) {
    const index = scannedProducts.findIndex(p => p.id === productId);
    
    if (index > -1) {
        const product = scannedProducts[index];
        scannedProducts.splice(index, 1);
        
        const row = document.querySelector(`tr[data-product-id="${productId}"]`);
        if (row) {
            row.remove();
        }
        
        updateUI();
        showStatus('warning', `Видалено: ${product.nazvanie}`);
    }
}

// Очистити список продуктів
function clearProductsList() {
    scannedProducts = [];
    document.querySelector('#scanned-products-table tbody').innerHTML = '';
    updateUI();
    showStatus('info', 'Список очищено');
}

// Оновити інтерфейс
function updateUI() {
    const hasProducts = scannedProducts.length > 0;
    
    // Показати/сховати елементи
    document.getElementById('empty-list-message').style.display = hasProducts ? 'none' : 'block';
    document.getElementById('products-table-wrapper').style.display = hasProducts ? 'block' : 'none';
    document.getElementById('summary-card').style.display = hasProducts ? 'block' : 'none';
    document.getElementById('action-buttons').style.display = hasProducts ? 'flex' : 'none';
    
    // Оновити лічильники
    let totalItems = 0;
    let totalPrice = 0;
    
    scannedProducts.forEach(product => {
        totalItems += product.quantity;
        totalPrice += parseFloat(product.zena) * product.quantity;
    });
    
    document.getElementById('items-count').textContent = scannedProducts.length;
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-price').textContent = totalPrice.toFixed(2) + ' грн';
}

// Зберегти відскановані продукти
function saveScannedProducts() {
    if (scannedProducts.length === 0) {
        showStatus('warning', 'Список продуктів порожній');
        return;
    }
    
    // Підготовка даних
    const products = scannedProducts.map(p => ({
        id: p.id,
        quantity: p.quantity
    }));
    
    // Показати статус збереження
    showStatus('info', '<i class="fas fa-spinner fa-spin me-2"></i>Збереження...');
    document.getElementById('save-button').disabled = true;
    
    // AJAX запит
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
            showStatus('success', data.message);
            clearProductsList();
            playBeep('success');
        } else {
            showStatus('danger', 'Помилка: ' + data.message);
            playBeep('error');
        }
    })
    .catch(error => {
        showStatus('danger', 'Помилка при збереженні');
        console.error('Error:', error);
        playBeep('error');
    })
    .finally(() => {
        document.getElementById('save-button').disabled = false;
    });
}

// Показати статус
function showStatus(type, message) {
    const statusDiv = document.getElementById('scan-status');
    statusDiv.className = `alert alert-${type} d-block`;
    statusDiv.innerHTML = message;
    
    // Автоматично приховати через 5 секунд
    setTimeout(() => {
        statusDiv.classList.add('d-none');
    }, 5000);
}

// Форматування штрих-коду
function formatBarcode(barcode) {
    if (barcode && barcode.length === 8) {
        return barcode.substr(0, 4) + '-' + barcode.substr(4, 4);
    }
    return barcode;
}

// Звуковий сигнал
function playBeep(type) {
    if (!audioContext) return;
    
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    if (type === 'success') {
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.3;
    } else {
        oscillator.frequency.value = 300;
        gainNode.gain.value = 0.3;
    }
    
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.1);
}

// Обробка сканера як клавіатури
let scannerBuffer = '';
let scannerTimeout;

document.addEventListener('keypress', function(e) {
    // Якщо фокус на полі введення і це не Enter
    if (document.activeElement === document.getElementById('barcode-input') && e.key !== 'Enter') {
        return;
    }
    
    clearTimeout(scannerTimeout);
    
    if (e.key >= '0' && e.key <= '9') {
        scannerBuffer += e.key;
        
        // Якщо набрано 8 цифр
        if (scannerBuffer.length === 8) {
            document.getElementById('barcode-input').value = scannerBuffer;
            scanProduct();
            scannerBuffer = '';
        }
    } else if (e.key === 'Enter' && scannerBuffer.length > 0) {
        document.getElementById('barcode-input').value = scannerBuffer;
        scanProduct();
        scannerBuffer = '';
    }
    
    // Очистити буфер через 200мс неактивності
    scannerTimeout = setTimeout(() => {
        scannerBuffer = '';
    }, 200);
});
</script>

<?php include_once '../../includes/footer.php'; ?>