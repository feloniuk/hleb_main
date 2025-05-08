-- База даних для системи "Одеський Коровай"

-- Створення бази даних
CREATE DATABASE IF NOT EXISTS `base` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `base`;

-- Таблиця користувачів (менеджер, бригадир, адмін, клієнти)
CREATE TABLE IF NOT EXISTS `polzovateli` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання початкових користувачів (id: 1 - менеджер, 2 - бригадир, 3 - адмін)
INSERT INTO `polzovateli` (`id`, `login`, `password`, `name`) VALUES
(1, 'manager', 'manager123', 'Менеджер'),
(2, 'brigadir', 'brigadir123', 'Бригадир'),
(3, 'admin', 'admin123', 'Адміністратор');

-- Таблиця клієнтів
CREATE TABLE IF NOT EXISTS `klientu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `fio` varchar(100) NOT NULL,
  `dolj` varchar(100) DEFAULT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `mail` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `adres` varchar(255) DEFAULT NULL,
  `rast` float DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових клієнтів
INSERT INTO `klientu` (`id`, `name`, `fio`, `dolj`, `tel`, `mail`, `city`, `adres`, `rast`, `login`, `password`) VALUES
(1, 'ТОВ Смак', 'Іванов Іван Іванович', 'Директор', '+380501234567', 'smak@example.com', 'Одеса', 'вул. Дерибасівська, 1', 2.5, 'client1', 'client123'),
(2, 'ФОП Петренко', 'Петренко Петро Петрович', 'Власник', '+380672345678', 'petrenko@example.com', 'Одеса', 'вул. Преображенська, 24', 3.8, 'client2', 'client123'),
(3, 'Супермаркет "Таврія В"', 'Сидоров Сидір Сидорович', 'Менеджер закупівель', '+380503456789', 'tavria@example.com', 'Одеса', 'вул. Генуезька, 31', 5.2, 'client3', 'client123');

-- Таблиця продукції
CREATE TABLE IF NOT EXISTS `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nazvanie` varchar(100) NOT NULL,
  `ves` float NOT NULL,
  `srok` int(11) NOT NULL,
  `stoimost` float NOT NULL,
  `zena` float NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестової продукції
INSERT INTO `product` (`id`, `nazvanie`, `ves`, `srok`, `stoimost`, `zena`, `image`) VALUES
(1, 'Хліб Обідній', 0.5, 48, 12.50, 18.00, 'assets/img/products/hleb_obidniy.jpg'),
(2, 'Хліб Сімейний', 0.7, 72, 15.20, 22.50, 'assets/img/products/hleb_semeyniy.jpg'),
(3, 'Багет Французький', 0.3, 24, 10.80, 16.00, 'assets/img/products/baget.jpg'),
(4, 'Булочки з маком', 0.1, 36, 4.50, 7.50, 'assets/img/products/bulochki_mak.jpg'),
(5, 'Пиріжки з яблуками', 0.15, 24, 5.80, 9.00, 'assets/img/products/pirozhki_apple.jpg'),
(6, 'Плетінка з кунжутом', 0.4, 48, 12.30, 19.50, 'assets/img/products/pletinka.jpg');

-- Таблиця замовлень
CREATE TABLE IF NOT EXISTS `zayavki` (
  `idd` int(11) NOT NULL AUTO_INCREMENT,
  `idklient` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `kol` int(11) NOT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') NOT NULL,
  PRIMARY KEY (`idd`),
  KEY `idklient` (`idklient`),
  KEY `id` (`id`),
  CONSTRAINT `zayavki_ibfk_1` FOREIGN KEY (`idklient`) REFERENCES `klientu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `zayavki_ibfk_2` FOREIGN KEY (`id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових замовлень
INSERT INTO `zayavki` (`idd`, `idklient`, `id`, `kol`, `data`, `doba`) VALUES
(1, 1, 1, 50, CURDATE(), 'денна'),
(2, 1, 2, 30, CURDATE(), 'денна'),
(3, 2, 3, 45, CURDATE(), 'нічна'),
(4, 3, 4, 100, CURDATE(), 'нічна'),
(5, 2, 5, 60, CURDATE(), 'денна');

-- Таблиця нових замовлень
CREATE TABLE IF NOT EXISTS `newzayavki` (
  `idd` int(11) NOT NULL AUTO_INCREMENT,
  `idklient` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `kol` int(11) NOT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') NOT NULL,
  PRIMARY KEY (`idd`),
  KEY `idklient` (`idklient`),
  KEY `id` (`id`),
  CONSTRAINT `newzayavki_ibfk_1` FOREIGN KEY (`idklient`) REFERENCES `klientu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `newzayavki_ibfk_2` FOREIGN KEY (`id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових нових замовлень
INSERT INTO `newzayavki` (`idd`, `idklient`, `id`, `kol`, `data`, `doba`) VALUES
(1, 1, 6, 40, CURDATE(), 'денна'),
(2, 2, 4, 25, CURDATE(), 'нічна'),
(3, 3, 1, 80, CURDATE(), 'денна');

-- Таблиця заказів бригадира
CREATE TABLE IF NOT EXISTS `zakazu` (
  `idz` int(11) NOT NULL AUTO_INCREMENT,
  `idklient` int(11) DEFAULT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') NOT NULL,
  PRIMARY KEY (`idz`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових заказів бригадира
INSERT INTO `zakazu` (`idz`, `idklient`, `data`, `doba`) VALUES
(1, NULL, CURDATE(), 'денна'),
(2, NULL, CURDATE(), 'нічна');

-- Таблиця для денних відправлень
CREATE TABLE IF NOT EXISTS `newzakaz` (
  `idd` int(11) NOT NULL,
  `idklient` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `kol` int(11) NOT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') NOT NULL,
  PRIMARY KEY (`idd`),
  KEY `idklient` (`idklient`),
  KEY `id` (`id`),
  CONSTRAINT `newzakaz_ibfk_1` FOREIGN KEY (`idklient`) REFERENCES `klientu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `newzakaz_ibfk_2` FOREIGN KEY (`id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблиця для нічних відправлень
CREATE TABLE IF NOT EXISTS `newzakaz2` (
  `idd` int(11) NOT NULL,
  `idklient` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `kol` int(11) NOT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') NOT NULL,
  PRIMARY KEY (`idd`),
  KEY `idklient` (`idklient`),
  KEY `id` (`id`),
  CONSTRAINT `newzakaz2_ibfk_1` FOREIGN KEY (`idklient`) REFERENCES `klientu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `newzakaz2_ibfk_2` FOREIGN KEY (`id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблиця статистики для звітів
CREATE TABLE IF NOT EXISTS `stat` (
  `id` int(11) NOT NULL,
  `kol` int(11) NOT NULL,
  `data` date NOT NULL,
  `doba` enum('денна','нічна') DEFAULT NULL,
  PRIMARY KEY (`id`,`data`),
  CONSTRAINT `stat_ibfk_1` FOREIGN KEY (`id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових даних статистики
INSERT INTO `stat` (`id`, `kol`, `data`, `doba`) VALUES
(1, 120, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'денна'),
(2, 85, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'денна'),
(3, 60, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'нічна'),
(4, 150, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'нічна'),
(5, 75, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'денна');

-- Таблиця для тимчасових ID замовлень
CREATE TABLE IF NOT EXISTS `idd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Таблиця для акцій та оголошень
CREATE TABLE IF NOT EXISTS `imgs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zag` varchar(255) NOT NULL,
  `comment` text NOT NULL,
  `content` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Додавання тестових акцій та оголошень
INSERT INTO `imgs` (`id`, `zag`, `comment`, `content`) VALUES
(1, 'Знижка 15% на весь асортимент', 'Шановні клієнти! З 1 по 10 травня діє знижка 15% на весь асортимент продукції від нашої пекарні.', 'img/reklama/sale15.jpg'),
(2, 'Нова продукція - Хліб зерновий', 'Раді повідомити про випуск нової продукції - Хліб зерновий з додаванням насіння льону, соняшника та гарбуза.', 'img/reklama/new_bread.jpg');