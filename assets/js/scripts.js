/**
 * Загальні скрипти для системи управління "Одеський Коровай"
 */

$(document).ready(function() {
    
    // Ініціалізація Bootstrap компонентів
    initBootstrapComponents();
    
    // Ініціалізація таблиць з сортуванням
    initSortableTables();
    
    // Ініціалізація форм
    initForms();
    
    // Ініціалізація штрих-коду сканера (якщо є)
    initBarcodeScanner();
});

/**
 * Ініціалізація компонентів Bootstrap
 */
function initBootstrapComponents() {
    // Активація тултіпів
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Активація поповерів
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Ініціалізація сортування таблиць
 */
function initSortableTables() {
    // Додавання функціоналу сортування для таблиць з класом 'table-sortable'
    $('.table-sortable').each(function() {
        var $table = $(this);
        
        $table.find('th').each(function() {
            if (!$(this).hasClass('no-sort')) {
                $(this).append(' <i class="fas fa-sort"></i>');
                
                $(this).click(function() {
                    var $th = $(this);
                    var index = $th.index();
                    var isAsc = $th.hasClass('sort-asc');
                    
                    // Видалити класи сортування з усіх заголовків
                    $table.find('th').removeClass('sort-asc sort-desc');
                    $table.find('th i').attr('class', 'fas fa-sort');
                    
                    // Додати відповідний клас до поточного заголовка
                    if (isAsc) {
                        $th.addClass('sort-desc');
                        $th.find('i').attr('class', 'fas fa-sort-down');
                    } else {
                        $th.addClass('sort-asc');
                        $th.find('i').attr('class', 'fas fa-sort-up');
                    }
                    
                    // Сортування рядків таблиці
                    var rows = $table.find('tbody > tr').get();
                    rows.sort(function(a, b) {
                        var x = $(a).children('td').eq(index).text();
                        var y = $(b).children('td').eq(index).text();
                        
                        // Спочатку спробуємо сортувати як числа
                        var xNum = parseFloat(x);
                        var yNum = parseFloat(y);
                        
                        if (!isNaN(xNum) && !isNaN(yNum)) {
                            return isAsc ? xNum - yNum : yNum - xNum;
                        }
                        
                        // Інакше сортуємо як текст
                        return isAsc ? x.localeCompare(y) : y.localeCompare(x);
                    });
                    
                    // Вставити відсортовані рядки назад у таблицю
                    $.each(rows, function(index, row) {
                        $table.children('tbody').append(row);
                    });
                });
            }
        });
    });
}

/**
 * Ініціалізація форм
 */
function initForms() {
    // Перевірка форм перед відправкою
    $('form').submit(function(event) {
        var form = $(this);
        
        if (form.hasClass('needs-validation')) {
            if (!form[0].checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.addClass('was-validated');
        }
    });
    
    // Маска для телефонного номера
    if ($.fn.mask) {
        $('input[type="tel"]').mask('+38 (999) 999-99-99');
    }
    
    // Активація підтвердження видалення для кнопок з класом 'btn-delete'
    $('.btn-delete').click(function(e) {
        if (!confirm('Ви впевнені, що хочете видалити цей запис?')) {
            e.preventDefault();
        }
    });
}

/**
 * Ініціалізація сканера штрих-кодів
 */
function initBarcodeScanner() {
    var barcodeInput = $('#barcode-input');
    
    if (barcodeInput.length) {
        // Фокус на полі введення штрих-коду
        barcodeInput.focus();
        
        // Обробка натискання Enter для сканера штрих-кодів
        barcodeInput.keypress(function(e) {
            if (e.which === 13) { // Код клавіші Enter
                e.preventDefault();
                
                var barcode = $(this).val().trim();
                if (barcode) {
                    processBarcodeScanned(barcode);
                    $(this).val('').focus();
                }
            }
        });
    }
}

/**
 * Обробка відсканованого штрих-коду
 */
function processBarcodeScanned(barcode) {
    // AJAX запит для отримання інформації про продукт за штрих-кодом
    $.ajax({
        url: 'modules/barcode_processor.php',
        type: 'POST',
        data: { barcode: barcode },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Додати продукт до таблиці
                addScannedProductToTable(response.product);
            } else {
                // Показати повідомлення про помилку
                showAlert('danger', 'Помилка: ' + response.message);
            }
        },
        error: function() {
            showAlert('danger', 'Сталася помилка при обробці штрих-коду.');
        }
    });
}

/**
 * Додати відсканований продукт до таблиці
 */
function addScannedProductToTable(product) {
    var scannedTable = $('#scanned-products-table tbody');
    
    // Перевірити, чи продукт вже є в таблиці
    var existingRow = scannedTable.find('tr[data-product-id="' + product.id + '"]');
    
    if (existingRow.length) {
        // Збільшити кількість
        var quantityCell = existingRow.find('.product-quantity');
        var quantity = parseInt(quantityCell.text()) + 1;
        quantityCell.text(quantity);
    } else {
        // Додати новий рядок
        var newRow = '<tr data-product-id="' + product.id + '">' +
            '<td>' + product.id + '</td>' +
            '<td>' + product.name + '</td>' +
            '<td class="product-quantity">1</td>' +
            '<td>' + product.price + ' грн</td>' +
            '<td><button type="button" class="btn btn-sm btn-danger remove-product"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
        
        scannedTable.append(newRow);
    }
    
    // Оновити загальну суму
    updateTotalPrice();
    
    // Показати повідомлення про успіх
    showAlert('success', 'Продукт "' + product.name + '" додано.');
}

/**
 * Оновити загальну суму
 */
function updateTotalPrice() {
    var total = 0;
    
    $('#scanned-products-table tbody tr').each(function() {
        var quantity = parseInt($(this).find('.product-quantity').text());
        var price = parseFloat($(this).find('td:eq(3)').text().replace(' грн', ''));
        
        total += quantity * price;
    });
    
    $('#total-price').text(total.toFixed(2) + ' грн');
}

/**
 * Показати повідомлення
 */
function showAlert(type, message) {
    var alertBox = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
        message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
        '</div>';
    
    $('#alerts-container').append(alertBox);
    
    // Автоматично приховати повідомлення через 5 секунд
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}