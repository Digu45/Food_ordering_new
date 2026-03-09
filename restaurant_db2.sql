-- ============================================================
-- Digus Restaurant — Full DB Schema
-- ============================================================
CREATE DATABASE IF NOT EXISTS `restaurant_db2` DEFAULT CHARACTER SET utf8mb4;
USE `restaurant_db2`;

-- Admin
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `admin` (`username`,`password`) VALUES ('digu','1441');

-- Item groups
CREATE TABLE IF NOT EXISTS `item_group_master` (
  `ItemGroupId` int(11) NOT NULL AUTO_INCREMENT,
  `ItemGroupName` varchar(100) NOT NULL,
  `status_id` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`ItemGroupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO `item_group_master` VALUES (141,'Food',1),(145,'Bar',1);

-- Menu
CREATE TABLE IF NOT EXISTS `menu_master` (
  `MenuId` int(11) NOT NULL AUTO_INCREMENT,
  `MenuSubCategoryName` varchar(100) DEFAULT NULL,
  `MenuName` varchar(150) NOT NULL,
  `MenuImageUrl` varchar(500) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Rate` decimal(10,2) NOT NULL DEFAULT 0,
  `MenuTypeId` tinyint(1) DEFAULT 1 COMMENT '1=Veg 2=NonVeg',
  `Discount` decimal(5,2) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`MenuId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart
CREATE TABLE IF NOT EXISTS `menu_items` (
  `sr_no` int(11) NOT NULL AUTO_INCREMENT,
  `MenuID` int(11) NOT NULL,
  `MenuName` varchar(150) DEFAULT NULL,
  `MenuImageUrl` varchar(500) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Rate` decimal(10,2) DEFAULT 0,
  `Quantity` int(11) DEFAULT 1,
  `Amount` decimal(10,2) DEFAULT 0,
  `MenuTypeId` tinyint(1) DEFAULT 1,
  `MobileNo` varchar(15) DEFAULT NULL,
  `DeviceID` varchar(64) DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  PRIMARY KEY (`sr_no`),
  INDEX idx_device (`DeviceID`),
  INDEX idx_menu_device (`MenuID`,`DeviceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders — added order_type, area, address, landmark
CREATE TABLE IF NOT EXISTS `placeorder` (
  `OrderId` int(11) NOT NULL AUTO_INCREMENT,
  `mobile_no` varchar(15) NOT NULL DEFAULT '',
  `customer_name` varchar(100) NOT NULL DEFAULT '',
  `product_id` varchar(50) NOT NULL,
  `qty` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `sub_total` decimal(10,2) NOT NULL,
  `total_tax_amt` decimal(10,2) NOT NULL DEFAULT 0,
  `rounded_amt` decimal(10,2) NOT NULL DEFAULT 0,
  `grand_amt` decimal(10,2) NOT NULL,
  `order_type` enum('Dine-in','Takeaway') NOT NULL DEFAULT 'Dine-in',
  `table_id` varchar(50) DEFAULT '',
  `area` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `landmark` varchar(200) DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Preparing','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(20) DEFAULT 'COD',
  `payment_status` varchar(20) DEFAULT 'Pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `order_group_id` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`OrderId`),
  INDEX idx_mobile (`mobile_no`),
  INDEX idx_group (`order_group_id`),
  INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample menu items
INSERT IGNORE INTO `menu_master` (`MenuSubCategoryName`,`MenuName`,`Description`,`Rate`,`MenuTypeId`) VALUES
('Starters','Veg Spring Roll','Crispy fried rolls with mixed vegetables',120,1),
('Starters','Chicken Tikka','Juicy chicken marinated in spices',220,2),
('Starters','Paneer Tikka','Soft paneer cubes grilled with spices',180,1),
('Main Course','Dal Makhani','Creamy slow-cooked black lentils',160,1),
('Main Course','Butter Chicken','Tender chicken in rich tomato gravy',280,2),
('Main Course','Paneer Butter Masala','Paneer in creamy butter sauce',220,1),
('Biryani','Veg Biryani','Fragrant basmati rice with vegetables',180,1),
('Biryani','Chicken Biryani','Aromatic rice with spiced chicken',260,2),
('Biryani','Mutton Biryani','Slow-cooked mutton with basmati rice',320,2),
('Breads','Butter Naan','Soft naan brushed with butter',40,1),
('Breads','Garlic Roti','Whole wheat roti with garlic',35,1),
('Desserts','Gulab Jamun','Soft khoya balls in sugar syrup',80,1),
('Desserts','Ice Cream','Vanilla / Chocolate / Strawberry',60,1),
('Beverages','Masala Chai','Spiced Indian tea',30,1),
('Beverages','Cold Coffee','Chilled coffee with ice cream',80,1),
('Beverages','Fresh Lime Soda','Sweet or salted lime soda',50,1);

COMMIT;
