
CREATE DATABASE web_gia_dung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE web_gia_dung;


CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','seller','customer') DEFAULT 'customer',
    avatar VARCHAR(255) DEFAULT 'default_avatar.jpg',
    reset_token VARCHAR(255) NULL,
    reset_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    description TEXT,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, description, parent_id) VALUES
('Tủ lạnh', 'Tủ lạnh gia đình, inverter, side by side', NULL),
('Máy giặt', 'Máy giặt cửa trước, cửa trên, lồng ngang', NULL),
('Bàn ghế', 'Bàn ghế ăn, sofa, ghế văn phòng', NULL),
('Tivi', 'Smart TV 4K, OLED, QLED', NULL),
('Điều hòa / Máy lạnh', 'Điều hòa inverter 9000-18000 BTU', NULL),
('Máy lọc nước', 'RO, Nano, ion kiềm, nóng lạnh', NULL),
('Quạt điện / Máy làm mát', 'Quạt đứng, quạt hơi nước, máy làm mát', NULL),
('Lò vi sóng / Lò nướng', 'Lò vi sóng có nướng, lò nướng âm tủ', NULL);

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO brands (name, description) VALUES
('Samsung', 'Thương hiệu Hàn Quốc hàng đầu'),
('LG', 'Công nghệ tiên tiến, thiết kế sang trọng'),
('Panasonic', 'Nhật Bản - bền bỉ'),
('Electrolux', 'Thiết kế châu Âu hiện đại'),
('Hitachi', 'Chất lượng Nhật Bản cao cấp'),
('Toshiba', 'Công nghệ ổn định, giá tốt');


CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0.00,
    image VARCHAR(255),
    images JSON NULL,
    category_id INT,
    brand_id INT,
    stock INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    bestseller TINYINT(1) DEFAULT 0, 
    flash_sale_end DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (name, description, price, discount, image, images, category_id, brand_id, stock, featured) VALUES
('Tủ lạnh Samsung Inverter 255L RT25M4033S8/SV', 'Digital Inverter, ngăn đông mềm Optimal Fresh+', 9490000.00, 18.00, 'samsung_rt25.jpg', '["samsung_rt25.jpg","samsung_rt25_2.jpg","samsung_rt25_3.jpg","samsung_rt25_4.jpg","samsung_rt25_5.jpg"]', 1,1,0,1),
('Tủ lạnh LG Inverter 335L GN-B332PGB', 'DoorCooling+, khử mùi Nano Carbon', 11990000.00, 20.00, 'lg_gn332.jpg', '["lg_gn332.jpg","lg_gn332_2.jpg","lg_gn332_3.jpg","lg_gn332_4.jpg","lg_gn332_5.jpg"]', 1,2,0,1),
('Tủ lạnh Panasonic 420L NR-BV468XSVN', 'Econavi, Prime Fresh+, làm đá tự động', 18990000.00, 15.00, 'panasonic_nr468.jpg', '["panasonic_nr468.jpg","panasonic_nr468_2.jpg","panasonic_nr468_3.jpg","panasonic_nr468_4.jpg","panasonic_nr468_5.jpg"]', 1,3,0,1),
('Tủ lạnh Hitachi 569L R-WB640PGV1', 'Side by Side, làm lạnh kép Dual Fan', 25990000.00, 12.00, 'hitachi_wb640.jpg', '["hitachi_wb640.jpg","hitachi_wb640_2.jpg","hitachi_wb640_3.jpg","hitachi_wb640_4.jpg","hitachi_wb640_5.jpg"]', 1,5,0,1),
('Tủ lạnh Toshiba Inverter 180L GR-B22VU', 'Nhỏ gọn cho phòng trọ', 5990000.00, 10.00, 'toshiba_gr22.jpg', '["toshiba_gr22.jpg","toshiba_gr22_2.jpg","toshiba_gr22_3.jpg","toshiba_gr22_4.jpg","toshiba_gr22_5.jpg"]', 1,6,0,0),
('Máy giặt Samsung 10kg WW10T634DLX/SV', 'AddWash, giặt hơi nước', 13490000.00, 22.00, 'samsung_ww10.jpg', '["samsung_ww10.jpg","samsung_ww10_2.jpg","samsung_ww10_3.jpg","samsung_ww10_4.jpg","samsung_ww10_5.jpg"]', 2,1,0,1),
('Máy giặt LG Inverter 11kg FV1411S3B', 'AI DD, TurboWash', 13990000.00, 18.00, 'lg_fv1411.jpg', '["lg_fv1411.jpg","lg_fv1411_2.jpg","lg_fv1411_3.jpg","lg_fv1411_4.jpg","lg_fv1411_5.jpg"]', 2,2,0,1),
('Máy giặt Electrolux 10kg EWF1024P5SB', 'UltimateCare 500', 12990000.00, 15.00, 'electrolux_ewf1024.jpg', '["electrolux_ewf1024.jpg","electrolux_ewf1024_2.jpg","electrolux_ewf1024_3.jpg","electrolux_ewf1024_4.jpg","electrolux_ewf1024_5.jpg"]', 2,4,0,0),
('Máy giặt Panasonic 10.5kg NA-FD10AR1BV', 'Active Foam, Econavi', 11490000.00, 10.00, 'panasonic_nafd10.jpg', '["panasonic_nafd10.jpg","panasonic_nafd10_2.jpg","panasonic_nafd10_3.jpg","panasonic_nafd10_4.jpg","panasonic_nafd10_5.jpg"]', 2,3,0,1),
('Máy giặt Toshiba 9.5kg AW-DM1050EV', 'GreatWaves, Ultra Fine Bubble', 8990000.00, 8.00, 'toshiba_awdm1050.jpg', '["toshiba_awdm1050.jpg","toshiba_awdm1050_2.jpg","toshiba_awdm1050_3.jpg","toshiba_awdm1050_4.jpg","toshiba_awdm1050_5.jpg"]', 2,6,0,0),
('Bộ bàn ăn gỗ óc chó 6 ghế Hitachi', 'Gỗ tự nhiên cao cấp', 22990000.00, 20.00, 'hitachi_banghe.jpg', '["hitachi_banghe.jpg","hitachi_banghe_2.jpg","hitachi_banghe_3.jpg","hitachi_banghe_4.jpg","hitachi_banghe_5.jpg"]', 3,5,0,1),
('Sofa da thật 3 chỗ LG Premium', 'Da bò Ý, đệm cao su non', 28990000.00, 15.00, 'lg_sofa_da.jpg', '["lg_sofa_da.jpg","lg_sofa_da_2.jpg","lg_sofa_da_3.jpg","lg_sofa_da_4.jpg","lg_sofa_da_5.jpg"]', 3,2,0,1),
('Ghế văn phòng công thái học Electrolux', 'Lưới thoáng, điều chỉnh đa chiều', 4490000.00, 12.00, 'electrolux_ghevp.jpg', '["electrolux_ghevp.jpg","electrolux_ghevp_2.jpg","electrolux_ghevp_3.jpg","electrolux_ghevp_4.jpg","electrolux_ghevp_5.jpg"]', 3,4,0,0),
('Sofa góc L Toshiba vải nỉ', 'Thiết kế hiện đại', 17990000.00, 18.00, 'toshiba_sofa.jpg', '["toshiba_sofa.jpg","toshiba_sofa_2.jpg","toshiba_sofa_3.jpg","toshiba_sofa_4.jpg","toshiba_sofa_5.jpg"]', 3,6,0,0),
('Smart Tivi Samsung 65 inch 4K QA65Q60C', 'QLED, Quantum HDR', 22990000.00, 28.00, 'samsung_qa65q60.jpg', '["samsung_qa65q60.jpg","samsung_qa65q60_2.jpg","samsung_qa65q60_3.jpg","samsung_qa65q60_4.jpg","samsung_qa65q60_5.jpg"]', 4,1,0,1),
('Smart Tivi LG 55 inch OLED 55C3PSA', 'OLED evo, α9 Gen6 AI', 38990000.00, 35.00, 'lg_oled55c3.jpg', '["lg_oled55c3.jpg","lg_oled55c3_2.jpg","lg_oled55c3_3.jpg","lg_oled55c3_4.jpg","lg_oled55c3_5.jpg"]', 4,2,0,1),
('Tivi Panasonic 50 inch 4K TH-50MX740V', 'Android TV, HDR10+', 12990000.00, 10.00, 'panasonic_th50mx.jpg', '["panasonic_th50mx.jpg","panasonic_th50mx_2.jpg","panasonic_th50mx_3.jpg","panasonic_th50mx_4.jpg","panasonic_th50mx_5.jpg"]', 4,3,0,0),
('Tivi Toshiba 55 inch 4K 55C350LP', 'REGZA Engine 4K', 10990000.00, 15.00, 'toshiba_55c350.jpg', '["toshiba_55c350.jpg","toshiba_55c350_2.jpg","toshiba_55c350_3.jpg","toshiba_55c350_4.jpg","toshiba_55c350_5.jpg"]', 4,6,0,0),
('Điều hòa Samsung Inverter 12000 BTU AR13TYHQASINSV', 'Wind-Free', 12490000.00, 20.00, 'samsung_ar13.jpg', '["samsung_ar13.jpg","samsung_ar13_2.jpg","samsung_ar13_3.jpg","samsung_ar13_4.jpg","samsung_ar13_5.jpg"]', 5,1,0,1),
('Điều hòa LG Inverter 18000 BTU V18ENF', 'Dual Inverter', 16990000.00, 18.00, 'lg_v18enf.jpg', '["lg_v18enf.jpg","lg_v18enf_2.jpg","lg_v18enf_3.jpg","lg_v18enf_4.jpg","lg_v18enf_5.jpg"]', 5,2,0,1),
('Điều hòa Panasonic 12000 BTU CU/CS-PU12AKH-8', 'Nanoe-X', 13490000.00, 15.00, 'panasonic_cu12.jpg', '["panasonic_cu12.jpg","panasonic_cu12_2.jpg","panasonic_cu12_3.jpg","panasonic_cu12_4.jpg","panasonic_cu12_5.jpg"]', 5,3,0,0),
('Điều hòa Electrolux 9000 BTU ESV09CRO-A1', 'Lọc bụi PM2.5', 9490000.00, 12.00, 'electrolux_esv09.jpg', '["electrolux_esv09.jpg","electrolux_esv09_2.jpg","electrolux_esv09_3.jpg","electrolux_esv09_4.jpg","electrolux_esv09_5.jpg"]', 5,4,0,0),
('Điều hòa Hitachi 18000 BTU RAS-XJ18CKV', 'FrostWash', 18990000.00, 10.00, 'hitachi_ras18.jpg', '["hitachi_ras18.jpg","hitachi_ras18_2.jpg","hitachi_ras18_3.jpg","hitachi_ras18_4.jpg","hitachi_ras18_5.jpg"]', 5,5,0,1),
('Máy lọc nước RO Toshiba TWP-N1686UV', '6 lõi + UV, nóng lạnh', 5990000.00, 25.00, 'toshiba_twp1686.jpg', '["toshiba_twp1686.jpg","toshiba_twp1686_2.jpg","toshiba_twp1686_3.jpg","toshiba_twp1686_4.jpg","toshiba_twp1686_5.jpg"]', 6,6,0,1),
('Máy lọc nước ion kiềm Panasonic TK-AS500', '5 cấp lọc', 16990000.00, 10.00, 'panasonic_tkas500.jpg', '["panasonic_tkas500.jpg","panasonic_tkas500_2.jpg","panasonic_tkas500_3.jpg","panasonic_tkas500_4.jpg","panasonic_tkas500_5.jpg"]', 6,3,0,1),
('Máy lọc nước nóng lạnh LG WD516', 'True RO', 8990000.00, 15.00, 'lg_wd516.jpg', '["lg_wd516.jpg","lg_wd516_2.jpg","lg_wd516_3.jpg","lg_wd516_4.jpg","lg_wd516_5.jpg"]', 6,2,0,0),
('Máy làm mát Electrolux CL07F', 'Bình 45L', 4490000.00, 20.00, 'electrolux_cl07.jpg', '["electrolux_cl07.jpg","electrolux_cl07_2.jpg","electrolux_cl07_3.jpg","electrolux_cl07_4.jpg","electrolux_cl07_5.jpg"]', 7,4,0,1),
('Quạt hơi nước Panasonic F-45KT', 'Điều khiển từ xa', 4990000.00, 15.00, 'panasonic_f45kt.jpg', '["panasonic_f45kt.jpg","panasonic_f45kt_2.jpg","panasonic_f45kt_3.jpg","panasonic_f45kt_4.jpg","panasonic_f45kt_5.jpg"]', 7,3,0,0),
('Quạt đứng Toshiba F-LSA20', '16 cánh', 1890000.00, 10.00, 'toshiba_flsa20.jpg', '["toshiba_flsa20.jpg","toshiba_flsa20_2.jpg","toshiba_flsa20_3.jpg","toshiba_flsa20_4.jpg","toshiba_flsa20_5.jpg"]', 7,6,0,0),
('Lò vi sóng Samsung 32L MC32K7055CK/SV', 'Có nướng đối lưu', 5490000.00, 18.00, 'samsung_mc32.jpg', '["samsung_mc32.jpg","samsung_mc32_2.jpg","samsung_mc32_3.jpg","samsung_mc32_4.jpg","samsung_mc32_5.jpg"]', 8,1,0,1),
('Lò nướng âm Electrolux EOC6851AOX', '78L, pyrolysis', 21990000.00, 12.00, 'electrolux_eoc6851.jpg', '["electrolux_eoc6851.jpg","electrolux_eoc6851_2.jpg","electrolux_eoc6851_3.jpg","electrolux_eoc6851_4.jpg","electrolux_eoc6851_5.jpg"]', 8,4,0,1),
('Lò vi sóng Toshiba 34L ER-SGS34(S1)VN', 'Inverter', 4290000.00, 10.00, 'toshiba_ersgs34.jpg', '["toshiba_ersgs34.jpg","toshiba_ersgs34_2.jpg","toshiba_ersgs34_3.jpg","toshiba_ersgs34_4.jpg","toshiba_ersgs34_5.jpg"]', 8,6,0,0);


CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) UNIQUE,
    color VARCHAR(50),
    capacity VARCHAR(50),
    price_add DECIMAL(12,2) DEFAULT 0.00,
    stock INT DEFAULT 0,
    image VARCHAR(255) NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_variants (product_id, sku, color, capacity, price_add, stock, image) VALUES
(1,'SAM-RT25-W','Trắng','255L',0,22,'samsung_rt25_w.jpg'),
(1,'SAM-RT25-S','Bạc','255L',600000,15,'samsung_rt25_s.jpg'),
(1,'SAM-RT25-B','Đen','255L',900000,8,'samsung_rt25_b.jpg'),
(2,'LG-GN332-S','Bạc','335L',0,18,'lg_gn332_s.jpg'),
(2,'LG-GN332-B','Đen','335L',1200000,10,'lg_gn332_b.jpg'),
(3,'PAN-NR468-S','Bạc','420L',0,12,'panasonic_nr468_s.jpg'),
(3,'PAN-NR468-B','Đen','420L',1500000,7,'panasonic_nr468_b.jpg'),
(4,'HIT-WB640-S','Bạc','569L',0,9,'hitachi_wb640_s.jpg'),
(4,'HIT-WB640-B','Đen','569L',2000000,5,'hitachi_wb640_b.jpg'),
(5,'TOS-GR22-W','Trắng','180L',0,25,'toshiba_gr22_w.jpg'),
(5,'TOS-GR22-S','Bạc','180L',400000,18,'toshiba_gr22_s.jpg'),
(6,'SAM-WW10-W','Trắng','10kg',0,20,'samsung_ww10_w.jpg'),
(6,'SAM-WW10-B','Đen','10kg',1000000,12,'samsung_ww10_b.jpg'),
(6,'SAM-WW10-S','Bạc','10kg',1300000,8,'samsung_ww10_s.jpg'),
(7,'LG-FV1411-W','Trắng','11kg',0,15,'lg_fv1411_w.jpg'),
(7,'LG-FV1411-B','Đen','11kg',1100000,9,'lg_fv1411_b.jpg'),
(8,'ELE-EWF1024-W','Trắng','10kg',0,22,'electrolux_ewf1024_w.jpg'),
(8,'ELE-EWF1024-S','Bạc','10kg',800000,14,'electrolux_ewf1024_s.jpg'),
(9,'PAN-NAFD10-W','Trắng','10.5kg',0,18,'panasonic_nafd10_w.jpg'),
(9,'PAN-NAFD10-S','Bạc','10.5kg',700000,11,'panasonic_nafd10_s.jpg'),
(10,'TOS-AW1050-W','Trắng','9.5kg',0,25,'toshiba_awdm1050_w.jpg'),
(10,'TOS-AW1050-S','Bạc','9.5kg',500000,16,'toshiba_awdm1050_s.jpg'),
(11,'HIT-BANGHE-WALNUT','Óc chó','6 ghế',0,10,'hitachi_banghe_walnut.jpg'),
(11,'HIT-BANGHE-OAK','Sồi','6 ghế',1500000,6,'hitachi_banghe_oak.jpg'),
(12,'LG-SOFA-WHITE','Trắng kem','3 chỗ',0,8,'lg_sofa_da_white.jpg'),
(12,'LG-SOFA-BROWN','Nâu','3 chỗ',2000000,5,'lg_sofa_da_brown.jpg'),
(13,'ELE-GHEVP-BLACK','Đen','1 ghế',0,30,'electrolux_ghevp_black.jpg'),
(13,'ELE-GHEVP-GRAY','Xám','1 ghế',300000,20,'electrolux_ghevp_gray.jpg'),
(14,'TOS-SOFA-GRAY','Xám','Góc L',0,12,'toshiba_sofa_gray.jpg'),
(14,'TOS-SOFA-BEIGE','Be','Góc L',800000,8,'toshiba_sofa_beige.jpg'),
(15,'SAM-QA65-BLACK','Đen','65 inch',0,10,'samsung_qa65q60_black.jpg'),
(15,'SAM-QA65-SILVER','Bạc','65 inch',2500000,6,'samsung_qa65q60_silver.jpg'),
(16,'LG-OLED55','Đen','55 inch',0,7,'lg_oled55c3.jpg'),
(16,'LG-OLED65','Đen','65 inch',18000000,4,'lg_oled6590c3.jpg'),
(16,'LG-OLED77','Đen','77 inch',45000000,2,'lg_oled77c3.jpg'),
(17,'PAN-TH50','Đen','50 inch',0,15,'panasonic_th50mx.jpg'),
(17,'PAN-TH55','Đen','55 inch',3000000,9,'panasonic_th55mx.jpg'),
(18,'TOS-55C350','Đen','55 inch',0,20,'toshiba_55c350.jpg'),
(18,'TOS-65C350','Đen','65 inch',5000000,12,'toshiba_65c350.jpg'),
(19,'SAM-AR13-W','Trắng','12000 BTU',0,25,'samsung_ar13_w.jpg'),
(19,'SAM-AR13-WF','WindFree','12000 BTU',2000000,10,'samsung_ar13_wf.jpg'),
(20,'LG-V18-W','Trắng','18000 BTU',0,18,'lg_v18enf_w.jpg'),
(20,'LG-V18-S','Bạc','18000 BTU',1500000,11,'lg_v18enf_s.jpg'),
(21,'PAN-CU12-W','Trắng','12000 BTU',0,20,'panasonic_cu12_w.jpg'),
(21,'PAN-CU12-S','Bạc','12000 BTU',800000,13,'panasonic_cu12_s.jpg'),
(22,'ELE-ESV09-W','Trắng','9000 BTU',0,28,'electrolux_esv09_w.jpg'),
(22,'ELE-ESV09-S','Bạc','9000 BTU',600000,17,'electrolux_esv09_s.jpg'),
(23,'HIT-RAS18-W','Trắng','18000 BTU',0,15,'hitachi_ras18_w.jpg'),
(23,'HIT-RAS18-S','Bạc','18000 BTU',1200000,9,'hitachi_ras18_s.jpg'),
(24,'TOS-TWP-W','Trắng','RO UV',0,30,'toshiba_twp1686_w.jpg'),
(24,'TOS-TWP-B','Đen','RO UV',700000,20,'toshiba_twp1686_b.jpg'),
(25,'PAN-TKAS500','Trắng','Ion kiềm',0,12,'panasonic_tkas500.jpg'),
(25,'PAN-TKAS500-S','Bạc','Ion kiềm',1000000,8,'panasonic_tkas500_s.jpg'),
(26,'LG-WD516-W','Trắng','Nóng lạnh',0,18,'lg_wd516_w.jpg'),
(26,'LG-WD516-B','Đen','Nóng lạnh',800000,11,'lg_wd516_b.jpg'),
(27,'ELE-CL07-W','Trắng','45L',0,22,'electrolux_cl07_w.jpg'),
(27,'ELE-CL07-G','Xám','45L',500000,14,'electrolux_cl07_g.jpg'),
(28,'PAN-F45KT-W','Trắng','Hơi nước',0,20,'panasonic_f45kt_w.jpg'),
(28,'PAN-F45KT-S','Bạc','Hơi nước',400000,12,'panasonic_f45kt_s.jpg'),
(29,'TOS-FLSA20-W','Trắng','Đứng',0,35,'toshiba_flsa20_w.jpg'),
(29,'TOS-FLSA20-B','Đen','Đứng',200000,25,'toshiba_flsa20_b.jpg'),
(30,'SAM-MC32-W','Trắng','32L',0,25,'samsung_mc32_w.jpg'),
(30,'SAM-MC32-B','Đen','32L',600000,15,'samsung_mc32_b.jpg'),
(31,'ELE-EOC6851-S','Inox','78L',0,10,'electrolux_eoc6851_s.jpg'),
(31,'ELE-EOC6851-B','Đen','78L',1500000,6,'electrolux_eoc6851_b.jpg'),
(32,'TOS-ERSGS34-W','Trắng','34L',0,20,'toshiba_ersgs34_w.jpg'),
(32,'TOS-ERSGS34-B','Đen','34L',500000,12,'toshiba_ersgs34_b.jpg'),
(32,'TOS-ERSGS34-S','Bạc','34L',800000,8,'toshiba_ersgs34_s.jpg');


CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(50) UNIQUE,
    user_id INT,
    fullname VARCHAR(100) NOT NULL DEFAULT '',
    phone VARCHAR(20) NOT NULL DEFAULT '',
    email VARCHAR(100) NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) DEFAULT 0.00,
    shipping_fee DECIMAL(12,2) DEFAULT 0.00,
    final_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','processing','shipping','delivered','cancelled','completed') DEFAULT 'pending',
    cancel_reason TEXT NULL,
    cancelled_at DATETIME NULL,
    payment_method ENUM('cod','transfer','card') DEFAULT 'cod',
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    voucher_code VARCHAR(30) NULL,
    note TEXT NULL,
    shipping_address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    variant_id INT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    variant_id INT NULL,
    quantity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    UNIQUE KEY unique_cart (user_id, product_id, variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address TEXT NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM(
        'profile_update',      -- Cập nhật hồ sơ
        'avatar_update',       -- Đổi avatar
        'avatar_delete',       -- Xóa avatar
        'address_add',         -- Thêm địa chỉ
        'address_update',      -- Sửa địa chỉ
        'address_delete',      -- Xóa địa chỉ
        'order_placed',        -- Đặt hàng thành công
        'wishlist_add',        -- Thêm yêu thích
        'wishlist_remove',     -- Xóa yêu thích
        'password_change',     -- Đổi mật khẩu
        'login',               -- Đăng nhập
        'logout'               -- Đăng xuất
    ) NOT NULL,
    description VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_approved TINYINT DEFAULT 1,
    UNIQUE KEY unique_review (user_id, order_id, product_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE product_reviews
ADD COLUMN variant_id INT NULL AFTER product_id,
ADD COLUMN images JSON NULL AFTER review,
ADD COLUMN video VARCHAR(255) NULL AFTER images,
ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0 AFTER video;

INSERT INTO users (username,email,phone,password,role) VALUES 
('admin','demo-258d8dc9@example.invalid','0900000000','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin'),
('user','demo-02ee7bdc@example.invalid','0900000000','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','customer');


UPDATE products p
SET p.stock = (
    SELECT COALESCE(SUM(pv.stock), 0)
    FROM product_variants pv
    WHERE pv.product_id = p.id
);
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS order_code VARCHAR(50) UNIQUE AFTER id,
ADD COLUMN IF NOT EXISTS fullname VARCHAR(100) NOT NULL DEFAULT '' AFTER user_id,
ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NOT NULL DEFAULT '' AFTER fullname,
ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER phone,
ADD COLUMN IF NOT EXISTS voucher_code VARCHAR(30) NULL AFTER payment_method,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,2) DEFAULT 0.00 AFTER voucher_code,
ADD COLUMN IF NOT EXISTS shipping_fee DECIMAL(12,2) DEFAULT 0.00 AFTER discount_amount,
ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER shipping_fee,
ADD COLUMN IF NOT EXISTS final_amount DECIMAL(12,2) NOT NULL AFTER subtotal,
ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER shipping_address,
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' AFTER payment_method;

UPDATE orders SET 
    order_code = CONCAT('GD', LPAD(id, 8, '0')),
    subtotal = total,
    final_amount = total,
    shipping_fee = 0,
    payment_status = CASE WHEN payment_method IN ('transfer','card') THEN 'paid' ELSE 'pending' END
WHERE order_code IS NULL OR order_code = '';


DROP TABLE IF EXISTS vouchers;
CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) UNIQUE NOT NULL,
    discount_type ENUM('fixed','percent') DEFAULT 'fixed',
    discount_value DECIMAL(12,2) NOT NULL,
    min_order DECIMAL(12,2) DEFAULT 0,
    max_usage INT DEFAULT 999999,
    used_count INT DEFAULT 0,
    expires_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO vouchers (code, discount_type, discount_value, min_order, expires_at, is_active) VALUES
('GIADUNG20', 'fixed', 20000.00, 0, '2027-12-31', 1),
('GIADUNG50', 'fixed', 50000.00, 500000, '2027-12-31', 1),
('GIAM10', 'percent', 10.00, 1000000, '2027-12-31', 1),
('WELCOME100', 'fixed', 100000.00, 2000000, '2026-12-31', 1);

DROP TABLE IF EXISTS voucher_usage;
CREATE TABLE voucher_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_voucher (voucher_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS payment_transactions;
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    order_code VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NULL,
    payment_method VARCHAR(30) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','success','failed','refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('fixed', 'percent') DEFAULT 'fixed',
    discount_value DECIMAL(12,2) NOT NULL,
    min_order DECIMAL(12,2) DEFAULT 0,
    max_usage INT DEFAULT 100,
    used_count INT DEFAULT 0,
    expires_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS voucher_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_voucher_user_order (voucher_id, user_id, order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE product_reviews 
ADD COLUMN IF NOT EXISTS admin_reply TEXT NULL,
ADD COLUMN IF NOT EXISTS admin_reply_at DATETIME NULL,
ADD COLUMN IF NOT EXISTS is_approved TINYINT(1) DEFAULT 1;

ALTER TABLE orders 
MODIFY COLUMN payment_method 
    ENUM('cod','transfer','card','bank_transfer','momo','zalopay') 
    DEFAULT 'cod' 
    AFTER payment_status;

ALTER TABLE orders 
MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending' 
    AFTER final_amount;

ALTER TABLE orders 
MODIFY COLUMN payment_method 
    ENUM('cod','transfer','card','bank_transfer','momo','zalopay') 
    DEFAULT 'cod' 
    AFTER payment_status;

INSERT INTO orders (user_id, fullname, phone, shipping_address, total, subtotal, final_amount, status, payment_method, order_code) 
VALUES (2, 'Du lieu mau', 'Du lieu mau', 'Du lieu mau', 10990000, 10990000, 10990000, 'pending', 'cod', 'Du lieu mau');

SET @order_id = LAST_INSERT_ID();

INSERT INTO order_items (order_id, product_id, variant_id, quantity, price) 
VALUES (@order_id, 1, 2, 1, 10990000);