# Gia Dụng Shop

**Tải gói chuyển máy FULL:** [GitHub Releases](https://github.com/datle10a3-cyber/web-ban-do-gia-dung/releases/tag/chuyen-may-20261022). Gói FULL có dữ liệu hiện tại và cấu hình thật, được chủ dự án yêu cầu đưa lên để chuyển máy. Khi repository ở chế độ Public, gói tải cũng công khai.

Website bán đồ gia dụng bằng PHP 8.2 và MySQL/MariaDB, chạy trên XAMPP. Có quản trị, tài khoản, danh mục, biến thể sản phẩm, giỏ hàng, voucher theo tài khoản, chatbot, Google/Facebook OAuth và VNPAY sandbox.

## Chạy bản public

1. Cài XAMPP có PHP 8.2+, bật **Apache** và **MySQL**.
2. Clone hoặc giải nén vào `XAMPP/htdocs/web-ban-do-gia-dung` (đổi tên thư mục tải từ GitHub nếu có hậu tố `-main`).
3. Chạy `CAI-DAT-MAY-MOI.cmd`. Script tạo cấu hình riêng và nhập database mẫu cùng các migration. Thư viện `vendor/` đã có sẵn.
4. Mở <http://localhost/web-ban-do-gia-dung/>.

Tài khoản mẫu: `admin` / `password` và `user` / `password`. Chỉ dùng trên localhost; đổi mật khẩu trước khi triển khai công khai.

Repo public chứa mã nguồn và dữ liệu mẫu. Khóa API, SMTP, secret OAuth/thanh toán, tài khoản thật, lịch sử đơn thực tế và ảnh tải lên của khách được giữ trong bản chuyển máy riêng.

## Chuyển nguyên trạng sang máy khác

Dùng file **GiaDungShop-FULL-PRIVATE-…zip** do chủ dự án lưu riêng. Gói này có toàn bộ thư mục gốc, thư viện, ảnh/video, `config/local.php`, cấu hình SMTP và snapshot database hiện tại.

1. Giải nén thư mục `web-ban-do-gia-dung` vào `XAMPP/htdocs` trên máy mới.
2. Bật MySQL trong XAMPP, chạy `CAI-DAT-MAY-MOI.cmd` một lần để khôi phục database. Script tự nhận ổ cài XAMPP, kiểm tra checksum và số bản ghi. Nếu đã có database bản public thì tự tạo database khác rồi cập nhật cấu hình kết nối; database cũ được giữ nguyên. Chạy lại bộ cài không nhập đè lên dữ liệu mới phát sinh.
3. Bật Apache và mở địa chỉ localhost ở trên. Cấu hình kết nối mặc định: MySQL `127.0.0.1:3306`, database `web_gia_dung`, user `root`; nếu máy mới dùng thiết lập khác thì sửa `config/local.php`.

Facebook và IPN VNPAY cần ngrok/HTTPS hoạt động trên **máy mới**, không chỉ Apache/MySQL. Bản đầy đủ riêng kèm ngrok và cấu hình đã có; bước cài đặt chỉ chép vào máy mới khi chưa có, không ghi đè cấu hình ngrok sẵn có. Chạy `BAT-HTTPS-SANDBOX.cmd` để bật kết nối. Nếu URL ngrok thay đổi, cập nhật callback Meta và IPN VNPAY cho khớp. Chỉ chạy tunnel này trên một máy tại một thời điểm. Google dùng callback localhost cũ khi giữ nguyên tên thư mục và cổng Apache 80. Phiên đăng nhập trình duyệt và tiến trình đang chạy không được chuyển qua bằng ZIP/Git.

## Cấu hình dịch vụ

`config/local.example.php` là mẫu; điền giá trị thật trong `config/local.php`. SMTP dùng `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM`. Không commit file cấu hình thật hoặc snapshot riêng lên repo public.

Migration nằm trong `database.sql/20261022_*.sql`; `scripts/setup.php` nhập dữ liệu mẫu chỉ khi database trống và chạy bổ sung cấu trúc. Voucher GIAM10 dùng tối đa 2 lần/tài khoản; WELCOME100 chỉ đơn đầu tiên, 1 lần/tài khoản; các mã cố định còn lại 1 lần/tài khoản.

Tài liệu thêm: [Đăng nhập](HUONG-DAN-DANG-NHAP.md), [Thanh toán sandbox](HUONG-DAN-THANH-TOAN-SANDBOX.md).
