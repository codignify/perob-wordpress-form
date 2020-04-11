# perob-order-form

## Perob simple order form
### I. Installation
- Download these files and zip it with name `perob-order-form.zip`. Upload and install it like normal others wordpress plugin
## II. Use
##### 1. TAG - [perobform]
- when wordpress user insert this tag to wordpress content (post, page...), plugin will generate an simple form (include `name`, `phonenumber`, `quantity`, `message`). When Client submit this form (via ajax or normal submit), this plugin will push these content to PEROB CRM as a potential customer data.
- This tag support an attribute is product_code (for example `[perobform product_code='code']`) will generate an order form for that product. If no product_code attribute, default value (in admin plugin config) will be applied.

##### 2. Admin config:
- In admin page, after install this plugin, we have one more menu name `Perob options`, to config all related info
- With config page, we have 6 fields.
     1. API Endpoint - This field define PEROB CRM API endpoint (Where data from client will post to)
     2. API TOKEN - Token for call above api endpoint
     3. Default Product - Default product code for `[perobform]` tag.
     4. Marketing UTM Link  - This link will be added to payload as `utl_link` field when send data to PEROB CRM API
     5. Marketing Source - This field will be add to payload as `source` field when send data to PEROB CRM API
     6. Submit Via - Have two option (ajax, form). When choose ajax, form will be submit via ajax method. When choose form, form will submit normal.
