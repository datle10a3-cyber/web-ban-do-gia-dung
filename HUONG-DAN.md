# Gia Dụng Shop — chạy với XAMPP

Mã hoạt động ở thư mục ngoài: `E:\xampp\htdocs\web-ban-do-gia-dung`.
Mở **http://localhost/web-ban-do-gia-dung/** sau khi bật Apache và MySQL.
Không mở file PHP trực tiếp bằng `file://`.

## Database có sẵn

Giữ nguyên `database.sql/web_gia_dung.sql`: 32 sản phẩm, danh mục, thương hiệu, biến thể và tài khoản mẫu của dự án.
MySQL của XAMPP hiện chạy ở cổng **3306**. Trước đây mã nguồn ghi 3309 nên không kết nối được.

Nếu cài ở máy mới, chạy trong PowerShell tại thư mục dự án:

```powershell
& E:/xampp/php/php.exe scripts/setup.php
```

Script chỉ nhập SQL gốc khi database chưa có bảng. Với database đã có dữ liệu, script giữ nguyên các bản ghi và chỉ chạy migration bổ sung `database.sql/20261022_integrations.sql`:

- `oauth_accounts`: liên kết tài khoản mạng xã hội với người dùng, không cần thay đổi cột mật khẩu gốc.
- `checkout_requests`: chống tạo đơn trùng.
- Thêm `vnpay` vào các phương thức thanh toán của `orders`.

Không nhập lại toàn bộ SQL gốc lên database đã sử dụng vì SQL gốc có lệnh DROP một số bảng. Dùng script trên để bổ sung cấu trúc.

`config/local.php` chứa cấu hình riêng. `config/local.example.php` là bản mẫu.
Không đưa secret lên Git hoặc JavaScript. Apache chặn truy cập trực tiếp thư mục config, SQL, scripts và vendor. Thư mục lồng `web-ban-do-gia-dung/` vẫn được giữ nguyên để đối chiếu; truy cập đường dẫn lồng được chuyển về website chính.

## Facebook và Google

Hướng dẫn tạo ứng dụng từng bước: [HUONG-DAN-DANG-NHAP.md](HUONG-DAN-DANG-NHAP.md). Giao diện đăng nhập/đăng ký đã thu gọn; nút mạng xã hội tự bật khi có cấu hình.

Mã nguồn ban đầu dùng App ID/secret giả và callback lấy provider từ query dù bên cung cấp không gửi giá trị đó. Luồng mới lưu provider, state và thời hạn trong session, kiểm tra callback rồi lưu liên kết vào `oauth_accounts`. Tài khoản xã hội mới có mật khẩu ngẫu nhiên được băm để tương thích cột `users.password NOT NULL`. Không tự liên kết với tài khoản mật khẩu chỉ dựa trên email.

Điền `FB_APP_ID`, `FB_APP_SECRET` trong `config/local.php` bằng thông tin ứng dụng Meta của bạn. Chọn `FB_GRAPH_VERSION` tương thích ứng dụng. Đăng ký callback chính xác:

```text
http://localhost/web-ban-do-gia-dung/auth/oauth_callback.php
```

Khi dùng tên miền hoặc HTTPS, sửa `APP_URL` và callback trong trang quản trị ứng dụng cho khớp. Ứng dụng cần bật Facebook Login, có quyền đăng nhập/email phù hợp và cho phép tài khoản thử nghiệm; chế độ công khai tùy điều kiện xét duyệt của Meta. Chưa có thông tin ứng dụng thật nên chưa thể kiểm thử đăng nhập Facebook từ đầu đến cuối. Khi chưa cấu hình, website trả thông báo rõ ràng và cho phép dùng mật khẩu.

Google dùng `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` và cùng callback.

## Thanh toán

Cập nhật 22/10/2026: VNPAY sandbox đã được cấu hình và thử giao dịch bên ngoài thành công, có IPN HTTPS hợp lệ xác nhận thanh toán. Kết quả và phạm vi công khai nằm trong [báo cáo triển khai](artifacts/sandbox-result.md). Các bước đăng ký phía dưới được giữ làm tài liệu tham khảo.

- COD hoạt động ngay, tạo đơn với `payment_status=pending`.
- Giá, biến thể, số lượng, tồn kho và mã giảm giá được kiểm tra lại từ database. Số tiền gửi từ trình duyệt không quyết định giá trị đơn.
- Mã giảm giá được kiểm tra thời hạn, số lượt, mức mua tối thiểu và tài khoản đã sử dụng.
- Đặt hàng và trừ kho nằm trong một transaction; gửi lại cùng yêu cầu không tạo thêm đơn.
- Mua ngay giữ giỏ hàng thông thường và không mất sản phẩm khi tải lại checkout.
- Trang xác nhận chỉ hiển thị đơn của tài khoản sở hữu.
- MoMo/ZaloPay đã hiện lại trong checkout với trạng thái chưa kết nối, chưa cho chọn khi chưa có tích hợp merchant/API/IPN thật. Hướng dẫn đăng ký ở [HUONG-DAN-THANH-TOAN-SANDBOX.md](HUONG-DAN-THANH-TOAN-SANDBOX.md).

VNPAY đã có luồng tạo thanh toán, chữ ký HMAC-SHA512, Return URL, IPN và thanh toán lại đơn chưa trả tiền. Điền thông tin merchant sandbox do VNPAY cấp:

```php
'VNPAY_TMN_CODE' => '...',
'VNPAY_HASH_SECRET' => '...',
'VNPAY_URL' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
```

Đăng ký:

- Return URL: `{APP_URL}/cart/vnpay_return.php`
- IPN URL: `{APP_URL}/cart/vnpay_ipn.php`

**IPN cần địa chỉ HTTPS công khai mà VNPAY truy cập được. localhost chỉ dùng kiểm thử nội bộ, không nhận được IPN thật từ máy chủ VNPAY.** Return URL chỉ hiển thị kết quả; chỉ IPN hợp lệ, đúng merchant, mã giao dịch và số tiền mới cập nhật đã thanh toán. Đã kiểm tra nội bộ chữ ký giả, sai số tiền, callback lặp, thành công và chống tạo đơn trùng; chưa thực hiện giao dịch thật hoặc giao dịch sandbox bên ngoài khi chưa có merchant.

Nếu muốn chuyển khoản ngân hàng trực tiếp, điền `BANK_BIN`, `BANK_ACCOUNT`, `BANK_ACCOUNT_NAME`. QR được tạo sau khi có mã đơn thật; trạng thái vẫn chờ xác nhận cho đến khi shop đối soát. Không dùng số tài khoản mẫu trong mã nguồn cũ để nhận tiền.

Tài liệu tích hợp: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html

## Chatbox và API sau này

Chatbox dùng chung trên các trang có footer, hỗ trợ gửi bằng Enter, câu hỏi gợi ý, lịch sử trong phiên trình duyệt, xóa hội thoại, liên kết sản phẩm, gọi điện và Zalo. Không tự bật làm gián đoạn mua sắm.

Chưa cần API key: trợ lý tìm sản phẩm từ database theo danh mục/ngân sách, hướng dẫn thanh toán và xem trạng thái đơn của chính người đăng nhập. Phần này dùng quy tắc và dữ liệu shop, chưa phải hội thoại AI tổng quát.

Khi có key, cấu hình ba trường phía máy chủ:

```php
'CHAT_API_KEY' => '...',
'CHAT_API_URL' => 'https://.../chat/completions',
'CHAT_MODEL' => '...',
```

Adapter hiện hỗ trợ API tương thích chat/completions. Nếu key thuộc nhà cung cấp có giao thức khác, cần chỉnh adapter theo tài liệu nhà cung cấp khi bạn gửi thông tin. Giá/tồn kho được cung cấp từ danh mục; tra cứu đơn xử lý trong shop. Key không xuất hiện trong HTML hoặc JavaScript. API chưa được kiểm thử khi chưa có key.

## Kiểm tra

```powershell
python scripts/integration_test.py
```

Kiểm tra tích hợp dùng database tạm có tên `giadung_verify_...`, nhập cùng SQL gốc rồi dọn đúng database tạm khi kết thúc. Không thay đổi database `web_gia_dung`. Test server chỉ nghe `127.0.0.1:8089` và được dừng sau khi chạy. Không gửi yêu cầu thanh toán ra VNPAY.

Nếu đường dẫn PHP khác, đặt biến môi trường `PHP_BINARY` trước khi chạy. Python 3 và PHP có PDO MySQL là đủ.

## Những thay đổi giao diện

Trang chủ mới, banner không chồng lớp, bố cục thích ứng màn hình nhỏ, header/footer đồng bộ, danh mục 2 cột trên điện thoại, checkout rõ ràng và chatbox chung. Giữ ảnh sản phẩm có sẵn. CSS mới nằm ở `assets/css/shop.css` và `assets/css/chatbox.css`.

## Bản lưu và giới hạn khôi phục

Bản sao lưu đầu tiên trong `E:\temp\giadung-before-20261022-090935.zip` bị rỗng do lỗi bộ lọc tên thư mục. Không sử dụng bản đó để khôi phục.

Các bản lưu đã kiểm tra có nội dung:

- `E:\temp\giadung-original-nested-copy-20261022-092412.zip`: bản mã nguồn lồng sẵn có được giữ nguyên để đối chiếu. Không chứa mọi file của thư mục ngoài.
- `E:\temp\giadung-current-20261022-092412.zip`: snapshot sau các chỉnh sửa chính, không phải bản trước khi sửa.

SQL gốc chưa bị sửa, SHA-256: `9f7158ac8fff4654e7fea5d1b2a7658fb3efe519c1f9c3f1e8a3be7f8566fd3f`.

## Cập nhật theo yêu cầu khôi phục

Trang chủ đã khôi phục ba nhóm sản phẩm từ mã nguồn cũ và hiển thị đủ 32 sản phẩm hiện có (không còn giới hạn 8 sản phẩm). Đã trả lại footer đầy đủ, nội dung quyền lợi mua hàng, nút yêu thích và thêm giỏ hàng ở trang chủ. Chỉ phần chữ trên banner giữ gọn. SQL gốc không đổi. Bảng `newsletter_subscribers` được bổ sung riêng bằng `20261022_newsletter.sql` để form đăng ký ưu đãi trong footer ghi nhận email thực tế.

Chatbot dùng Groq chính và Gemini dự phòng qua API tương thích chat/completions. Cả hai khóa được lưu riêng trong `config/local.php`; không có giá trị khóa trong JavaScript, file mẫu hay tài liệu này. Các câu hỏi sản phẩm, thanh toán và tra cứu đơn cơ bản vẫn xử lý trực tiếp từ dữ liệu shop; các câu hỏi mở dùng AI. Mã model/endpoint dự phòng cấu hình bằng `CHAT_FALLBACK_MODEL` và `CHAT_FALLBACK_API_URL`.

Nguồn API: [Groq](https://console.groq.com/docs/api-reference), [Gemini](https://ai.google.dev/gemini-api/docs/openai/).

Đã thử phản hồi AI qua website bằng Groq thành công, và thử riêng đường dự phòng Gemini 3.1 Flash-Lite thành công. Các khóa chatbot này không thay thế Google OAuth Client ID/Secret hay Facebook App ID/Secret dùng cho đăng nhập.
