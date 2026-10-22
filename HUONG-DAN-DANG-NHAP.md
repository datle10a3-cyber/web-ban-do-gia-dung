# Đăng nhập Google và Facebook — cấu hình đã kiểm thử

Ngày 22/10/2026, cả Google và Facebook đã được đăng nhập thực tế thành công trên trình duyệt của chủ dự án. Bằng chứng nằm trong `artifacts/google-login-restored.png` và `artifacts/facebook-login-https-success.png`.

## Địa chỉ và callback

- Website chính và `APP_URL`: `http://localhost/web-ban-do-gia-dung`.
- Google: `http://localhost/web-ban-do-gia-dung/auth/oauth_callback.php`.
- Facebook: callback HTTPS do `scripts/check_oauth.php` in ra; được đồng bộ vào **Meta → Trường hợp sử dụng → Đăng nhập bằng Facebook → Cài đặt → URI chuyển hướng OAuth hợp lệ**.
- VNPAY giữ nguyên callback localhost và IPN ngrok đã cấu hình.

Không đổi APP_URL sang cổng kiểm thử hoặc ngrok chỉ để sửa Facebook: Google và VNPAY có callback riêng cần ổn định. Cổng 8098 của bài kiểm thử thanh toán đã chuyển sang điều hướng về website chính để tránh tiếp tục đăng nhập trên địa chỉ sai.

## Luồng Facebook HTTPS

Website localhost tạo phiên giao tiếp có nonce ngẫu nhiên, đi qua ngrok đến `auth/oauth_start.php`, rồi chuyển tới Facebook. Callback HTTPS xác minh state, thời gian, code và tài khoản qua API chính thức. Sau đó, một vé dùng một lần có hạn 60 giây đưa trình duyệt về `auth/oauth_finish.php` trên localhost. Chỉ trình duyệt đã khởi tạo phiên mới sử dụng được vé.

Session HTTPS dùng tên riêng, cookie Secure/HttpOnly/SameSite=Lax. Phiên cấp quyền hết hạn sau 10 phút. Vé và nonce lưu dạng hash trong thư mục riêng ngoài htdocs; không lưu access token. Callback và vé không được cache và không gửi Referer. Giữ bật HTTPS và chế độ URI nghiêm ngặt trong Meta.

Ứng dụng Meta hiện có quyền `public_profile`; luồng chỉ xin tên và ảnh công khai. Không tự thêm quyền email hoặc liên kết tài khoản bằng cách trùng email. Tài khoản Facebook chưa cung cấp email dùng định danh nội bộ riêng.

## Vận hành tunnel

ngrok chính thức đã được cài và tunnel hiện đang chạy. Script `scripts/start_vnpay_sandbox.ps1` khởi động lại máy chủ giới hạn quyền và ngrok, đồng thời đồng bộ origin HTTPS sang thư mục riêng `OAUTH_RUNTIME_DIR`. Nếu ngrok cấp tên miền khác khi khởi động lại, phải cập nhật callback tương ứng trong Meta và IPN trong VNPAY; không tự thay callback Google. Script kiểm tra cấu hình không hiển thị secret.

Tunnel chỉ mở các trang danh mục, IPN xác minh chữ ký và hai endpoint OAuth HTTPS. Không mở admin, cấu hình, database, đặt hàng hoặc API chatbot ra Internet. `OAUTH_RUNTIME_DIR` phải là thư mục riêng ngoài htdocs mà cả Apache và PHP chạy tunnel truy cập được. Các key thật chỉ nằm trong `config/local.php`.

## Kiểm chứng

- `scripts/verify_oauth.py`: kiểm tra callback cố định, cách ly session, giả mạo state, sai trình duyệt, sai vé, hết hạn và chống sử dụng lại; dùng cơ sở dữ liệu tạm, không gọi nhà cung cấp.
- `scripts/verify_sandbox.py`: kiểm tra HTTPS thực, chặn đường dẫn riêng tư, cookie HTTPS, callback giả và IPN thiếu chữ ký.
- `scripts/integration_test.py`: kiểm tra lại sản phẩm, giỏ hàng, voucher và VNPAY trên cơ sở dữ liệu tạm.

Ứng dụng Meta vẫn ở chế độ chưa xuất bản. Chủ ứng dụng hoặc tài khoản có vai trò thử nghiệm có thể đăng nhập. Để mở cho mọi khách hàng, chủ ứng dụng cần hoàn tất các mục xuất bản/xét duyệt do Meta yêu cầu. Việc sửa callback không tự xuất bản ứng dụng.
