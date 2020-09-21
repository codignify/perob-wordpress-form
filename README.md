# perob-order-form

## Perob simple order form
### I. Installation
- Download these files and zip it with name `perob-order-form.zip`. Upload and install it like normal others wordpress plugin

### II. Use
##### 1. Admin config:
- In admin page, after install this plugin, we have one more menu name `Perob options`, to config all related info
- With config page, we have 6 fields.
     1. API Endpoint - This field define PEROB CRM API endpoint (Where data from client will post to)
     2. API TOKEN - Token for call above api endpoint
     3. Default Product - Default product code for `[perobform]` tag.
     4. Marketing UTM Link  - This link will be added to payload as `utl_link` field when send data to PEROB CRM API
     5. Marketing Source - This field will be add to payload as `source` field when send data to PEROB CRM API
     6. Submit Via - Have two option (ajax, form). When choose ajax, form will be submit via ajax method. When choose form, form will submit normal.

##### 2. TAG - [perobform]
- when wordpress user insert this tag to wordpress content (post, page...), plugin will generate an simple form (include `name`, `phonenumber`, `quantity`, `message`). When Client submit this form (via ajax or normal submit), this plugin will push these content to PEROB CRM as a potential customer data.
- This tag support an attribute is product_code (for example `[perobform product_code='code']`) will generate an order form for that product. If no product_code attribute, default value (in admin plugin config) will be applied.


## Hướng dẫn sử dụng perob-order-form

### I. Cài đặt
- Tải và cài đặt file wordpress plugin `perob-order-form.zip`.

### II. Sử dụng

##### 1. Cấu hình
- Trong trang admin của wordpress (/wp-admin). Vào trang cấu hình `Perob Options` (Trong menu Settings).
- Các cấu hình trong có các thông số sau
    1. API Endpoint - API để thêm data vào PEROB CRM. Được cung cấp bởi PEROB
    2. API TOKEN - Token để gọi API của PEROB. Được cung cấp bở PEROB
    3. Default Product - Mã sản phẩm mặc định cho tag `[perobform]`. (Chi tiết nói ở phần hướng dẫn sử dụng tag)
    4. Marketing UTM Link - UTM Link mặc định dùng để gọi API. Khi khách hàng vào thẳng trang web không có UTM. Giá trị trường này sẽ được sử dụng trong post_data gọi đến CRM API.
    5. Marketing Source - Trường này được sử dụng để add vào post_data `source` khi gọi API
    6. Submit Via - Có 2 lựa chọn (ajax, form).

##### 2. TAG - [perobform]
- Thêm tag `[perobform]` vào nội dung bài viết (nơi muốn xuất hiện order form). Form sẽ nhận 4 trường là `Tên`, `Số điện thoại`, `Số lượng sản phẩm`, `Nội dung`. Khi người dùng điền vào submit form. Thông tin sẽ tự động được gửi đến CRM thông qua API (được cấu hình trong admin)
- Tag `[perobform]` sẽ generate form với product_code là default trong config admin.
- Sử dụng `[perobform product_code='code']` Nếu muốn gen form với product_code là code
- Sử dụng `[perobform product_code='code' form_id="top"]` Nếu muốn gen form với product_code là `code` và form_id là `top`
