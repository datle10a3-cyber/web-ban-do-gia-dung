"""Run against an isolated temporary database; never changes the shop database."""
from pathlib import Path
import os, subprocess, time, urllib.request, urllib.parse, urllib.error, http.cookiejar, re, json, secrets, hashlib, hmac, tempfile
ROOT=Path(__file__).resolve().parents[1]
PHP=Path(os.environ.get('PHP_BINARY','E:/xampp/php/php.exe'))
DB='giadung_verify_'+secrets.token_hex(5)
ENV=os.environ.copy();ENV.update(GOOGLE_CLIENT_ID='',GOOGLE_CLIENT_SECRET='',FB_APP_ID='',FB_APP_SECRET='',CHAT_API_KEY='',CHAT_FALLBACK_API_KEY='',DB_NAME=DB,VNPAY_TMN_CODE='TESTMERCHANT',VNPAY_HASH_SECRET='local-verification-only',APP_URL='http://127.0.0.1:8089/web-ban-do-gia-dung')
ENV['VNPAY_AUDIT_LOG']=str(Path(tempfile.gettempdir())/(DB+'-audit.log'))
BASE=ENV['APP_URL']
ENV['OAUTH_LOCAL_URL']=BASE
server=None

def php(code):
    return subprocess.check_output([str(PHP),'-r',code],cwd=ROOT,env=ENV).decode('utf-8')
def db(sql):
    # Pass SQL as a separate argument instead of interpolating it into PHP source.
    return subprocess.check_output([str(PHP),'-r',"require 'config/config.php'; $q=$pdo->query($argv[1]); echo json_encode($q->fetchAll());",sql],cwd=ROOT,env=ENV).decode('utf-8')
def request(opener,path,data=None,json_data=None):
    headers={}
    if json_data is not None:data=json.dumps(json_data).encode();headers['Content-Type']='application/json'
    elif data is not None:data=urllib.parse.urlencode(data).encode();headers['Content-Type']='application/x-www-form-urlencoded'
    try:
        r=opener.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=20)
        return r.status,r.read().decode('utf-8'),r.geturl()
    except urllib.error.HTTPError as e:return e.code,e.read().decode('utf-8'),e.url

def check(condition,label):
    if not condition:raise AssertionError(label)
    print('PASS '+label,flush=True)
try:
    subprocess.run([str(PHP),'scripts/setup.php'],cwd=ROOT,env=ENV,check=True,stdout=subprocess.DEVNULL)
    log=tempfile.TemporaryFile()
    server=subprocess.Popen([str(PHP),'-S','127.0.0.1:8089','-t',str(ROOT.parent)],cwd=ROOT,env=ENV,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    for _ in range(30):
        try:status,html,_=request(opener,'/');break
        except OSError:time.sleep(.2)
    check(status==200 and 'Fatal error' not in html,'home renders using supplied database')
    check(html.count('class="card product-card')==32,'homepage restores every original product')
    check(html.count('class="appliance-icon"')==8 and 'aria-label="Danh mục sản phẩm"' in html,'all eight homepage appliance category icons restored')
    check(all(label in html for label in ['Sản phẩm nổi bật','Sản phẩm bán chạy nhất','Sản phẩm mới nhất','Chính sách','Đăng ký nhận ưu đãi','Nhóm 11 Pro']),'original product sections and full footer restored')
    check('class="store-footer"' in html and 'btn-outline-warning btn-floating' not in html,'professional footer replaces old oversized decorations and placeholder social buttons')
    csrf=re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
    for banner_path in ['/','/products/','/pages/about.php','/pages/stores.php','/pages/support.php','/pages/tintuc.php','/cart/']:
        _,banner_html,_=request(opener,banner_path)
        check(len(re.findall(r'class="shop-banner(?: shop-banner-home)?"',banner_html))==1,'shared banner '+banner_path)
    def catalog_ids(query):
        _,body,_=request(opener,'/products/?'+urllib.parse.urlencode(query))
        check('Fatal error' not in body and 'Warning:' not in body,'catalog query renders '+json.dumps(query,ensure_ascii=True))
        return [int(x) for x in re.findall(r'data-product-id="(\d+)"',body)]
    first=catalog_ids({'page':1});second=catalog_ids({'page':2});third=catalog_ids({'page':3})
    check(len(first)==15 and not(set(first)&set(second)) and len(set(first+second+third))==32,'stable pagination exposes all 32 products without duplicates')
    filtered=catalog_ids({'search':'Tủ lạnh','brand':'Samsung','max_price':8,'sort':'price_asc'})
    check(filtered==[1],'combined search brand and discounted-price filter')
    sorted_ids=catalog_ids({'sort':'price_asc'})
    expected=[int(x['id']) for x in json.loads(db('SELECT id FROM products ORDER BY ROUND(price*(100-discount)/100) ASC,id ASC LIMIT 15'))]
    check(sorted_ids==expected,'price sorting matches displayed sale price')
    check(catalog_ids({'search':'no-product-zzx-123'})==[],'empty search has no unrelated products')

    for path in ['/products/','/products/detail.php?id=1','/cart/','/auth/login.php','/pages/support.php']:
        status,html,_=request(opener,path);check(status==200 and 'Fatal error' not in html and 'Warning:' not in html,'page '+path)
    status,body,_=request(opener,'/ajax/chat.php',json_data={'message':'Tủ lạnh dưới 10 triệu','csrf':csrf})
    reply=json.loads(body);check(status==200 and reply['products'] and all(float(p['price'])<=10_000_000 for p in reply['products']),'chat recommendations use actual prices and budget')
    previous_min=min(float(p['price']) for p in reply['products'])
    status,body,_=request(opener,'/ajax/chat.php',json_data={'message':'Rẻ hơn chút nhé','csrf':csrf})
    reply=json.loads(body);check(status==200 and all(float(p['price'])<previous_min and 'Tủ lạnh' in p['name'] for p in reply['products']),'chat cheaper follow-up preserves product category and lowers budget')
    status,body,_=request(opener,'/ajax/chat.php',json_data={'message':'Máy giặt từ 8 đến 12 triệu','csrf':csrf})
    reply=json.loads(body);check(reply['products'] and all(8_000_000<=float(p['price'])<=12_000_000 and 'Máy giặt' in p['name'] for p in reply['products']),'chat understands a price range and a changed category')
    status,body,_=request(opener,'/ajax/chat.php',json_data={'message':'Code giảm giá còn bao nhiêu lượt?','csrf':csrf})
    reply=json.loads(body);check('100' in reply['reply'] and 'COD' not in reply['reply'] and not reply['products'],'voucher question is not misclassified as COD payment')
    status,_,_=request(opener,'/cart/place_order.php',data={});check(status==401,'checkout requires authentication')
    status,html,url=request(opener,'/auth/login.php',data={'username':'user','password':'password','csrf':csrf})
    check('/auth/login.php' not in url,'password login')
    csrf=re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
    status,body,_=request(opener,'/cart/add.php',data={'product_id':1,'variant_id':1,'quantity':2,'csrf':csrf,'ajax':1})
    check(status==200 and json.loads(body)['success'],'add selected variant to cart')
    status,cart_html,_=request(opener,'/cart/')
    check(status==200 and 'class="cart-layout"' in cart_html and 'class="cart-summary"' in cart_html and 'max-height: calc' not in cart_html,'compact cart renders complete summary without clipped sticky panel')
    check(re.search(r'id="selectAll" checked',cart_html) is not None,'new item selected after previously empty cart')
    status,html,_=request(opener,'/checkout.php?items=1_1')
    check(status==200 and 'Warning:' not in html,'checkout renders selected items')
    available=json.loads(db("SELECT max_usage-used_count AS remaining FROM vouchers WHERE code='GIADUNG50'"))[0]['remaining']
    check(re.search(r'<option value="GIADUNG50"[^>]*data-remaining="'+str(available)+'"[^>]*>[^<]*còn',html) is not None,'voucher options show remaining uses from existing database')
    check(all(re.search('value="'+method+'" disabled',html) for method in ['momo','zalopay']),'MoMo and ZaloPay remain visible with honest unconnected status')
    key=re.search(r'name="request_key" value="([a-f0-9]+)"',html)[1]
    data={'csrf':csrf,'request_key':key,'fullname':'Local Test Customer','phone':'0909123456','address':'123 Test Street, Test City','payment_method':'cod','discount_amount':999999999,'total_amount':1,'voucher_code':'GIADUNG50'}
    bad=data.copy();bad['csrf']='invalid';status,_,_=request(opener,'/cart/place_order.php',data=bad);check(status==403,'CSRF rejected')
    bad=data.copy();bad['payment_method']='momo';status,_,_=request(opener,'/cart/place_order.php',data=bad);check(status==422,'unconfigured payment method rejected')
    status,body,_=request(opener,'/cart/place_order.php',data=data);order=json.loads(body);check(status==200 and order['success'],'COD order created')
    oid=int(order['order_id']);row=json.loads(db(f'SELECT * FROM orders WHERE id={oid}'))[0]
    check(row['payment_status']=='pending' and float(row['final_amount'])==15_513_600,'server ignores posted price and discount and validates voucher')
    remaining=json.loads(db("SELECT max_usage-used_count AS remaining FROM vouchers WHERE code='GIADUNG50'"))[0]['remaining']
    check(int(remaining)==int(available)-1,'placing order consumes exactly one voucher use')
    status,body,_=request(opener,'/cart/place_order.php',data=data);check(json.loads(body)['order_id']==order['order_id'],'duplicate request reuses same order')
    stock=json.loads(db('SELECT stock FROM product_variants WHERE id=1'))[0]['stock'];check(int(stock)==20,'stock decremented once')
    status,body,_=request(opener,'/checkout/thankyou.php?order_id='+str(oid)+'&code='+order['order_code']);check(status==200 and 'Local Test Customer' in body,'owner can view confirmation')
    stranger=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    _,stranger_html,_=request(stranger,'/auth/login.php')
    stranger_csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',stranger_html)[1]
    request(stranger,'/auth/login.php',data={'username':'admin','password':'password','csrf':stranger_csrf})
    status,body,_=request(stranger,'/checkout/thankyou.php?order_id='+str(oid)+'&code='+order['order_code']);check(status==404 and 'Local Test Customer' not in body,'confirmation does not expose another user order')
    status,body,_=request(opener,'/cart/buynow.php',json_data={'csrf':csrf,'product_id':2,'variant_id':4,'qty':1,'price':1,'name':'fake'})
    check(status==200 and json.loads(body)['success'],'direct buy validates catalog')
    status,html,_=request(opener,'/checkout.php');k=re.search(r'name="request_key" value="([a-f0-9]+)"',html)[1]
    _,refresh,_=request(opener,'/checkout.php');check('name="request_key" value="'+k+'"' in refresh,'direct checkout survives reload')
    vdata=data.copy();vdata.update(request_key=k,payment_method='vnpay',voucher_code='')
    status,body,_=request(opener,'/cart/place_order.php',data=vdata);vorder=json.loads(body);check(status==200 and vorder['success'],'VNPAY order creates signed redirect')
    query=dict(urllib.parse.parse_qsl(urllib.parse.urlsplit(vorder['redirect_url']).query));check(query['vnp_Amount']=='959200000','VNPAY amount comes from order')
    query={k:v for k,v in query.items() if k!='vnp_SecureHash'}
    expected=hmac.new(ENV['VNPAY_HASH_SECRET'].encode(),urllib.parse.urlencode(sorted(query.items())).encode(),hashlib.sha512).hexdigest()
    original=dict(urllib.parse.parse_qsl(urllib.parse.urlsplit(vorder['redirect_url']).query));check(expected==original['vnp_SecureHash'],'outgoing VNPAY checksum')
    callback={'vnp_TmnCode':ENV['VNPAY_TMN_CODE'],'vnp_TxnRef':query['vnp_TxnRef'],'vnp_Amount':query['vnp_Amount'],'vnp_ResponseCode':'00','vnp_TransactionStatus':'00','vnp_TransactionNo':'12345678'}
    def signed(d):
        d=d.copy();d['vnp_SecureHash']=hmac.new(ENV['VNPAY_HASH_SECRET'].encode(),urllib.parse.urlencode(sorted(d.items())).encode(),hashlib.sha512).hexdigest();return d
    bad=callback.copy();bad['vnp_SecureHash']='0'*128
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(bad));check(json.loads(body)['RspCode']=='97','forged callback rejected')
    bad=callback.copy();bad['vnp_Amount']='100'
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(bad)));check(json.loads(body)['RspCode']=='04','signed callback with wrong amount rejected')
    bad=callback.copy();bad['vnp_TmnCode']='OTHER-MERCHANT'
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(bad)));check(json.loads(body)['RspCode']=='97','signed callback for wrong merchant rejected')
    bad=callback.copy();bad['vnp_TxnRef']='unknown-transaction'
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(bad)));check(json.loads(body)['RspCode']=='01','unknown transaction reference rejected')
    bad=callback.copy();bad.pop('vnp_TransactionStatus')
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(bad)));check(json.loads(body)['RspCode']=='99','incomplete signed result cannot change payment status')
    status,body,_=request(opener,'/cart/vnpay_return.php?'+urllib.parse.urlencode(signed(callback)))
    vo=int(vorder['order_id']);row=json.loads(db(f'SELECT payment_status FROM orders WHERE id={vo}'))[0];check(row['payment_status']=='pending','browser return cannot mark order paid')
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(callback)));check(json.loads(body)['RspCode']=='00','valid IPN accepted')
    row=json.loads(db(f'SELECT payment_status FROM orders WHERE id={vo}'))[0];check(row['payment_status']=='paid','verified IPN marks order paid')
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(callback)));check(json.loads(body)['RspCode']=='02','repeated IPN has no duplicate side effects')
    bad=callback.copy();bad.update(vnp_ResponseCode='24',vnp_TransactionStatus='02')
    status,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(bad)))
    row=json.loads(db(f'SELECT payment_status FROM orders WHERE id={vo}'))[0]
    check(json.loads(body)['RspCode']=='02' and row['payment_status']=='paid','late cancellation cannot downgrade a paid order')
    for response_code in ['24','51']:
        request(opener,'/cart/buynow.php',json_data={'csrf':csrf,'product_id':2,'variant_id':4,'qty':1})
        _,body,_=request(opener,'/checkout.php');retry_key=re.search(r'name="request_key" value="([a-f0-9]+)"',body)[1]
        retry_data=data.copy();retry_data.update(request_key=retry_key,payment_method='vnpay',voucher_code='')
        _,body,_=request(opener,'/cart/place_order.php',data=retry_data);pending_order=json.loads(body);pending_id=int(pending_order['order_id'])
        attempt=dict(urllib.parse.parse_qsl(urllib.parse.urlsplit(pending_order['redirect_url']).query))
        failure=callback.copy();failure.update(vnp_TxnRef=attempt['vnp_TxnRef'],vnp_Amount=attempt['vnp_Amount'],vnp_ResponseCode=response_code,vnp_TransactionStatus='02')
        _,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(failure)))
        payment=json.loads(db(f'SELECT payment_status,status FROM orders WHERE id={pending_id}'))[0]
        check(json.loads(body)['RspCode']=='00' and payment['payment_status']=='failed' and payment['status']=='pending','failed or cancelled attempt keeps order available for retry '+response_code)
        _,body,_=request(opener,'/cart/create_payment.php',data={'csrf':csrf,'order_id':pending_id});new_attempt=json.loads(body)
        q=dict(urllib.parse.parse_qsl(urllib.parse.urlsplit(new_attempt['payment_url']).query))
        check(new_attempt['success'] and q['vnp_TxnRef']!=attempt['vnp_TxnRef'],'payment retry creates a fresh transaction '+response_code)
        success=callback.copy();success.update(vnp_TxnRef=q['vnp_TxnRef'],vnp_Amount=q['vnp_Amount'])
        _,body,_=request(opener,'/cart/vnpay_ipn.php?'+urllib.parse.urlencode(signed(success)))
        check(json.loads(body)['RspCode']=='00' and json.loads(db(f'SELECT payment_status FROM orders WHERE id={pending_id}'))[0]['payment_status']=='paid','successful retry settles the existing order '+response_code)
    _,history,_=request(opener,'/user/orders.php')
    _,admin_orders,_=request(stranger,'/admin/manage_orders.php')
    check('VNPAY' in history and 'Đã thanh toán' in history and 'VNPAY' in admin_orders and 'Đã thanh toán' in admin_orders,'customer history and admin reflect verified payment status')
    audit=Path(ENV['VNPAY_AUDIT_LOG']).read_text(encoding='utf-8')
    check('payment_request_created' in audit and 'ipn_97' in audit and ENV['VNPAY_HASH_SECRET'] not in audit and 'vnp_SecureHash' not in audit,'private payment audit contains events without secrets or signatures')
    anonymous=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    status,html,url=request(anonymous,'/auth/oauth.php?provider=facebook');check(status==200 and '/auth/login.php' in url and ('chưa được cấu hình' in html or 'chưa kết nối' in html),'missing OAuth credentials handled without fatal error')
    status,html,url=request(anonymous,'/auth/oauth_callback.php?state=forged&code=fake');check('/auth/login.php' in url and 'không hợp lệ' in html,'forged OAuth state rejected')
    status,register_html,_=request(anonymous,'/auth/register.php')
    check(status==200 and 'onclick="alert' not in register_html,'registration uses real social login routes instead of coming-soon alerts')
    reg_csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',register_html)[1]
    status,body,url=request(anonymous,'/auth/register.php',data={'csrf':reg_csrf,'username':'verification_user','email':'verify@example.test','password':'test-pass-123','confirm_password':'test-pass-123'})
    check(status==200 and '/auth/login.php' in url,'registration redirects to compact login')
    login_csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',body)[1]
    status,body,url=request(anonymous,'/auth/login.php',data={'csrf':login_csrf,'username':'verification_user','password':'test-pass-123'})
    check('/auth/login.php' not in url,'new account can sign in')
    status,benefits,_=request(anonymous,'/user/profile.php?tab=vouchers')
    check(status==200 and 'GIAM10' in benefits and 'Còn 2/2 lượt' in benefits and 'WELCOME100' in benefits,'newly registered account automatically receives personal voucher allowances')
    status,body,_=request(opener,'/ajax/subscribe.php',data={'csrf':csrf,'email':'newsletter@example.test'})
    check(status==200 and json.loads(body)['success'],'restored footer newsletter saves subscriptions')
    check(len(json.loads(db("SELECT email FROM newsletter_subscribers WHERE email='newsletter@example.test'")))==1,'newsletter subscription persisted in isolated test database')
    print('All integration checks passed.',flush=True)
finally:
    if server:server.terminate();server.wait(timeout=10)
    Path(ENV['VNPAY_AUDIT_LOG']).unlink(missing_ok=True)
    # Only the generated test database is removed; this name is never read from user configuration.
    if re.fullmatch(r'giadung_verify_[a-f0-9]{10}',DB):
        php("require 'config/app.php'; $p=new PDO('mysql:host='.app_env('DB_HOST','127.0.0.1').';port='.app_env('DB_PORT',3306),app_env('DB_USER','root'),app_env('DB_PASSWORD')); $p->exec('DROP DATABASE `"+DB+"`');")
