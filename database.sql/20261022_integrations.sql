CREATE TABLE IF NOT EXISTS oauth_accounts (
 id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 provider VARCHAR(20) NOT NULL,
 provider_id VARCHAR(191) NOT NULL,
 UNIQUE KEY provider_identity (provider, provider_id),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE orders MODIFY payment_method ENUM('cod','transfer','card','bank_transfer','momo','zalopay','vnpay') DEFAULT 'cod';
CREATE TABLE IF NOT EXISTS checkout_requests (
 user_id INT NOT NULL,
 request_key VARCHAR(64) NOT NULL,
 order_id INT NOT NULL,
 PRIMARY KEY (user_id, request_key),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
