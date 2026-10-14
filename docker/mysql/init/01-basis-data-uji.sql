-- Dijalankan sekali saat volume MySQL pertama kali dibuat.
-- Basis data terpisah untuk `php artisan test` di dalam container.
CREATE DATABASE IF NOT EXISTS studio_musik_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON studio_musik_test.* TO 'studio'@'%';
FLUSH PRIVILEGES;
