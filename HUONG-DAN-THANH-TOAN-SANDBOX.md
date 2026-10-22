# Đăng ký thử thanh toán VNPAY, MoMo, ZaloPay

## Trạng thái của dự án

- COD đặt hàng được ngay.
- VNPAY đã được cấu hình bằng merchant sandbox riêng, lưu IPN HTTPS vào cổng SIT Testing và thử thanh toán NCB thành công ngày 22/10/2026. Website đã nhận IPN hợp lệ và xác nhận đơn thử đã trả tiền. Xem [báo cáo triển khai](artifacts/sandbox-result.md).
- MoMo và ZaloPay đã hiện lại trong checkout, đang khóa với nhãn “Chưa kết nối thanh toán trực tuyến”. Hai ví chưa có adapter API/IPN; sau khi được cấp thông tin merchant cần kết nối và kiểm thử trước khi mở nút.
- Không dùng QR hoặc khóa mẫu trong mã nguồn cũ để nhận tiền. Mở lại lựa chọn không đồng nghĩa đã thu được tiền.

## 1. VNPAY — làm bước này trước

1. Mở [Đăng ký merchant thử nghiệm](https://sandbox.vnpayment.vn/devreg/).
2. Điền thông tin thật của bạn theo biểu mẫu, email nhận thông tin tích hợp và địa chỉ website thử. Bạn tự hoàn tất phần đăng ký/điều khoản nếu được yêu cầu.
3. Kiểm tra email VNPAY gửi, lấy **TmnCode** và **HashSecret**. Nếu không nhận được, kiểm tra thư rác hoặc liên hệ VNPAY qua thông tin trên trang đăng ký.
4. Mở `E:\xampp\htdocs\web-ban-do-gia-dung\config\local.php`, điền vào hai dòng có sẵn, giữ nguyên cấu hình database, chatbot và đăng nhập:

```php
'VNPAY_TMN_CODE' => 'TmnCode được cấp',
'VNPAY_HASH_SECRET' => 'HashSecret được cấp',
'VNPAY_URL' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
```

5. Return URL của bản localhost:

```text
http://localhost/web-ban-do-gia-dung/cart/vnpay_return.php
```

6. Để thử toàn bộ luồng, IPN phải truy cập được từ máy chủ VNPAY. Dùng hosting thử có HTTPS hoặc một địa chỉ HTTPS thử nghiệm đã cấu hình cho dự án, rồi đặt `APP_URL` theo địa chỉ đó. IPN là `{APP_URL}/cart/vnpay_ipn.php`; Return URL là `{APP_URL}/cart/vnpay_return.php`. Không có bước tự động public máy tính trong lần sửa này.
7. Khi đổi `APP_URL`, cập nhật callback Google/Facebook sang `{APP_URL}/auth/oauth_callback.php` trong trang quản trị hai bên. Dùng cùng một host trong suốt lượt mua hàng để giữ phiên đăng nhập.
8. Tải lại checkout: VNPAY tự bật khi đủ hai khóa. Thử đơn sandbox với dữ liệu thử được VNPAY cung cấp, gồm thành công, hủy và thất bại. Return URL không tự đánh dấu đã trả tiền; trạng thái chỉ đổi khi IPN hợp lệ xác nhận đúng đơn và đúng số tiền.

[Tài liệu kết nối chính thức VNPAY](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html).

## 2. MoMo

1. Mở [MoMo for Business](https://business.momo.vn), chọn đăng ký và hoàn thành thông tin hồ sơ được yêu cầu.
2. Vào môi trường Test, lấy **Partner Code**, **Access Key**, **Secret Key** của môi trường thử. Khóa production và test khác nhau.
3. Dùng các khóa được cấp riêng để hoàn thiện API tạo thanh toán và IPN. API thử có máy chủ `https://test-payment.momo.vn`.
4. Đăng ký URL IPN HTTPS mà MoMo gọi được, kiểm thử chữ ký, đúng số tiền, hủy và callback lặp trước khi mở phương thức cho khách.

Hiện dự án chưa có trường cấu hình hoạt động cho MoMo. Không dán khóa vào HTML hay JavaScript; khi được cấp khóa, báo đã có để nối adapter phía PHP.

[Hồ sơ merchant MoMo](https://developers.momo.vn/v3/vi/docs/payment/onboarding/merchant-profile/), [Quy trình và thông tin tích hợp](https://developers.momo.vn/v3/docs/payment/onboarding/integration-process/).

## 3. ZaloPay

1. Theo [Quy trình tích hợp chính thức](https://docs.zalopay.vn/docs/developer-tools/integration-guide/), liên hệ bộ phận hỗ trợ tích hợp/kinh doanh ZaloPay, cung cấp số điện thoại và email để được tạo tài khoản sandbox.
2. Nhận **App ID**, **Key1**, **Key2** của môi trường sandbox; quản lý tài khoản thử tại [Merchant Sandbox](https://sbmc.zalopay.vn).
3. Cần kết nối API tạo đơn, địa chỉ callback HTTPS công khai và kiểm tra HMAC bằng Key2. Khách quay về trang web không đủ để xác nhận thanh toán.
4. Khi đã có App ID/keys, hoàn thiện adapter phía PHP và kiểm thử rồi mới bật ZaloPay trong checkout.

Tài khoản ví cá nhân không thay thế tài khoản merchant. Các cổng sandbox dùng để thử, không phải cấu hình nhận tiền thật.

## Facebook đã cập nhật

Đã lưu App ID/Secret bạn cung cấp vào `config/local.php`. Kiểm tra máy chủ Meta chấp nhận bộ thông tin này và website đã chuyển đến màn hình đăng nhập Facebook. Chưa xác nhận hoàn tất đăng nhập tài khoản cá nhân.

Callback cần khai báo trong Facebook Login là `http://localhost/web-ban-do-gia-dung/auth/oauth_callback.php`. Nếu ứng dụng còn Development, thử bằng tài khoản có vai trò hợp lệ của app. Xem thêm `HUONG-DAN-DANG-NHAP.md`.
