"""Account/admin audit on a disposable copy of the supplied database; no live writes."""
from pathlib import Path
import os,sys,tempfile,subprocess,secrets,time,urllib.request,urllib.parse,urllib.error,http.cookiejar,json,re,hashlib,base64
sys.stdout.reconfigure(encoding='utf-8')
ROOT=Path(__file__).resolve().parents[1];PHP='E:/xampp/php/php.exe';DB='giadung_verify_'+secrets.token_hex(5);BASE='http://127.0.0.1:8100/web-ban-do-gia-dung'
ENV=os.environ.copy();ENV.update(DB_NAME=DB,APP_URL=BASE,OAUTH_LOCAL_URL=BASE,GOOGLE_CLIENT_ID='',GOOGLE_CLIENT_SECRET='',FB_APP_ID='',FB_APP_SECRET='',CHAT_API_KEY='',CHAT_FALLBACK_API_KEY='',MAIL_DISABLED='1',VNPAY_TMN_CODE='TESTMERCHANT',VNPAY_HASH_SECRET='audit-only-not-real')
checks=[];created_files=[]
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,*a,**k):return None

def browser():return urllib.request.build_opener(NoRedirect(),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(c,path,data=None,files=None):
 headers={}
 if files:
  boundary='Audit'+secrets.token_hex(12);chunks=[]
  for k,v in (data or {}).items():chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
  for k,(name,content,mime) in files.items():chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n')
  data=b''.join(chunks)+f'--{boundary}--\r\n'.encode();headers['Content-Type']='multipart/form-data; boundary='+boundary
 elif data is not None:data=urllib.parse.urlencode(data,doseq=True).encode();headers['Content-Type']='application/x-www-form-urlencoded'
 try:r=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=20)
 except urllib.error.HTTPError as e:r=e
 body=r.read().decode('utf-8',errors='replace')
 assert not re.search(r'(?:Fatal error|Warning:|Parse error|Uncaught \w+Error)',body),path+' emitted PHP diagnostics'
 return r.status,body,r.headers

def sql(query,params=[]):
 code="require 'config/config.php';$q=$pdo->prepare($argv[1]);$q->execute(json_decode($argv[2],true));echo json_encode($q->columnCount()?$q->fetchAll():['rows'=>$q->rowCount(),'id'=>$pdo->lastInsertId()]);"
 return json.loads(subprocess.check_output([PHP,'-r',code,query,json.dumps(params)],cwd=ROOT,env=ENV))
def check(value,label):
 assert value,label
 checks.append(label);print('PASS '+label,flush=True)
def token(c,path='/auth/login.php'):
 _,html,_=request(c,path);return re.search(r'name="csrf" value="([a-f0-9]+)"',html)[1]
def api_token(c):
 _,html,_=request(c,'/');return re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
def login(c,name='user',password='password'):
 status,body,h=request(c,'/auth/login.php',{'csrf':token(c),'username':name,'password':password});return status==302

def new_order(uid=2,status='pending',method='cod',paid='pending'):
 value=sql('INSERT INTO orders(user_id,fullname,phone,shipping_address,payment_method,payment_status,status,subtotal,final_amount,total) VALUES (?,?,?,?,?,?,?,?,?,?)',[uid,'Audit Customer','0909123456','123 Audit Test Street',method,paid,status,100000,100000,100000]);oid=int(value['id']);sql('UPDATE orders SET order_code=? WHERE id=?',['AUDIT'+str(oid),oid]);sql('INSERT INTO order_items(order_id,product_id,variant_id,quantity,price) VALUES (?,?,?,?,?)',[oid,1,1,1,100000]);return oid

process=None
with tempfile.TemporaryDirectory(prefix='giadung-audit-') as temp:
 log=open(Path(temp)/'server.log','w')
 try:
  subprocess.run([PHP,'scripts/setup.php'],cwd=ROOT,env=ENV,check=True,stdout=subprocess.DEVNULL)
  process=subprocess.Popen([PHP,'-S','127.0.0.1:8100','-t',str(ROOT.parent)],cwd=ROOT,env=ENV,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
  guest=browser()
  for _ in range(30):
   try:request(guest,'/');break
   except OSError:time.sleep(.1)
  user=browser();admin=browser();check(login(user),'customer password login');check(login(admin,'admin'),'admin password login')
  for path in ['/','/products/','/products/detail.php?id=1','/products/search.php?term=Samsung','/products/quick_view.php?id=1','/products/category.php?cat=1','/auth/forgot.php','/auth/forgot-password.php','/auth/reset.php','/orders/track.php','/orders/history.php','/order-success.php']:
   status,_,_=request(guest,path);check(status<400,'public page renders '+path)
  for path in ['/user/profile.php','/user/profile.php?tab=password','/user/profile.php?tab=addresses','/user/profile.php?tab=activity','/user/wishlist.php','/user/orders.php']:
   status,_,_=request(user,path);check(status==200,'customer page renders '+path)
  pages=['manage_products.php','manage_categories.php','manage_brands.php','manage_users.php','manage_orders.php','manage_reviews.php','manage_vouchers.php','index.php','user_detail.php?id=2','manage_reviews.php?search=Samsung','manage_reviews.php?rating=5&status=approved','manage_users.php?search=user','manage_orders.php?s=user','manage_products.php?search=Samsung']
  for page in pages:
   status,_,_=request(admin,'/admin/'+page);check(status==200,'admin page renders '+page)
   status,body,_=request(user,'/admin/'+page);check(status in [302,403] and (not body.strip() or body.strip()=='Không có quyền truy cập.'),'customer cannot read admin '+page)
  csrf=token(user,'/user/profile.php');admin_csrf=token(admin,'/admin/manage_users.php')
  before=sql('SELECT COUNT(*) AS n FROM categories')[0]['n']
  request(user,'/admin/manage_categories.php',{'csrf':csrf,'name':'SHOULD NOT EXIST'})
  check(sql('SELECT COUNT(*) AS n FROM categories')[0]['n']==before,'valid customer CSRF cannot bypass admin guard')
  request(user,'/user/profile.php',{'csrf':'bad','action':'profile','display_name':'Wrong','email':'user@example.com','phone':''})
  check(sql('SELECT display_name FROM users WHERE id=2')[0]['display_name']=='user','profile rejects CSRF')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'profile','display_name':'Nguyễn Minh An','email':'user@example.com','phone':'0909123456'})
  check(status==303 and sql('SELECT display_name FROM users WHERE id=2')[0]['display_name']=='Nguyễn Minh An','profile saves human name without changing login ID')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'profile','display_name':'Bad Email','email':'admin@example.com','phone':''})
  check(status==422 and sql('SELECT email FROM users WHERE id=2')[0]['email']=='user@example.com','profile rejects another account email')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'profile','display_name':'<script>alert(1)</script>','email':'user@example.com','phone':''})
  check(status==422,'profile rejects HTML as a personal name')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'address_save','address':'12 First Audit Street','is_default':'1'})
  address1=int(sql('SELECT id FROM user_addresses WHERE user_id=2 ORDER BY id DESC LIMIT 1')[0]['id'])
  request(user,'/user/profile.php',{'csrf':csrf,'action':'address_save','address':'34 Second Audit Street'})
  address2=int(sql('SELECT id FROM user_addresses WHERE user_id=2 ORDER BY id DESC LIMIT 1')[0]['id'])
  request(user,'/user/profile.php',{'csrf':csrf,'action':'address_save','addr_id':address2,'address':'34 Second Audit Street','is_default':'1'})
  check(sql('SELECT COUNT(*) AS n FROM user_addresses WHERE user_id=2 AND is_default=1')[0]['n']==1,'editing addresses preserves exactly one default')
  foreign=int(sql('INSERT INTO user_addresses(user_id,address,is_default) VALUES (1,?,1)',['999 Admin Private Street'])['id'])
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'address_delete','addr_id':foreign})
  check(status==422 and sql('SELECT id FROM user_addresses WHERE id=?',[foreign]),'cannot delete another user address')
  request(user,'/user/profile.php',{'csrf':csrf,'action':'address_delete','addr_id':address2})
  check(sql('SELECT is_default FROM user_addresses WHERE id=?',[address1])[0]['is_default']==1,'deleting default promotes a remaining address')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'avatar_upload'},files={'avatar':('fake.php.png',b'<?php echo 1;?>','image/png')})
  check(status==422,'avatar rejects executable content disguised as an image')
  png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'avatar_upload'},files={'avatar':('tiny.png',png,'image/png')})
  image=sql('SELECT avatar FROM users WHERE id=2')[0]['avatar'];created_files.append(ROOT/'assets/images/avatars'/image)
  check(status==303 and image.startswith('avatar_2_'),'valid avatar upload is stored under a generated name')
  request(user,'/user/profile.php',{'csrf':csrf,'action':'avatar_delete'})
  check(sql('SELECT avatar FROM users WHERE id=2')[0]['avatar']=='default_avatar.jpg','avatar delete does not fall through into profile update')
  ac=api_token(user)
  status,_,_=request(user,'/cart/add.php',{'csrf':ac,'product_id':1,'variant_id':1,'quantity':0,'ajax':1});check(status==422,'cart rejects zero quantity')
  status,_,_=request(user,'/cart/add.php',{'csrf':ac,'product_id':1,'variant_id':999999,'quantity':1,'ajax':1});check(status==422,'cart rejects a nonexisting variant')
  status,_,_=request(user,'/cart/add.php',{'csrf':ac,'product_id':1,'variant_id':1,'quantity':1,'ajax':1});check(status==200,'cart adds a verified catalog item')
  status,_,_=request(user,'/cart/update.php?key=1_1&change=1&csrf_token='+csrf);check(status==405,'cart quantity cannot mutate through GET')
  request(user,'/cart/update.php',{'key':'1_1','change':1,'csrf_token':csrf})
  check(sql('SELECT quantity FROM cart WHERE user_id=2 AND product_id=1 AND variant_id=1')[0]['quantity']==2,'cart quantity is synchronized to account storage')
  request(user,'/auth/logout.php');check(login(user),'customer signs in again')
  check('value="2" readonly' in request(user,'/cart/')[1],'saved cart survives logout and login')
  csrf=token(user,'/user/profile.php');ac=api_token(user)
  mine=new_order();foreign_order=new_order(1)
  status,body,_=request(guest,'/user/order-success.php?order='+str(foreign_order));check(status==302 and 'Audit Customer' not in body,'legacy success page cannot disclose private orders to visitors')
  status,body,_=request(user,'/user/order-success.php?order='+str(foreign_order));check(status==302 and 'Audit Customer' not in body,'legacy success page does not fall back to a different customer order')
  status,body,_=request(guest,'/ajax/get_variants.php?id=999999');check(status==404 and not json.loads(body)['success'],'variant API handles a missing product without fabricated stock')
  stock=sql('SELECT stock FROM products WHERE id=1')[0]['stock'];vstock=sql('SELECT stock FROM product_variants WHERE id=1')[0]['stock']
  request(user,'/user/orders.php?id='+str(mine),{'cancel_order':1,'csrf':'bad','cancel_reason':'Không còn nhu cầu'})
  check(sql('SELECT status FROM orders WHERE id=?',[mine])[0]['status']=='pending','cancellation requires CSRF')
  request(user,'/user/orders.php?id='+str(mine),{'cancel_order':1,'csrf':csrf,'cancel_reason':'Không còn nhu cầu'})
  request(user,'/user/orders.php?id='+str(mine),{'cancel_order':1,'csrf':csrf,'cancel_reason':'Không còn nhu cầu'})
  check(sql('SELECT stock FROM products WHERE id=1')[0]['stock']==stock+1 and sql('SELECT stock FROM product_variants WHERE id=1')[0]['stock']==vstock+1,'cancellation restores product and variant stock exactly once')
  status,_,_=request(user,'/cart/reorder.php',{'csrf':csrf,'id':foreign_order});check(status==404,'cannot reorder another customer order')
  status,_,_=request(user,'/cart/reorder.php',{'csrf':csrf,'id':mine});check(status==303,'own reorder uses current catalog and preserves existing cart')
  check(sql('SELECT quantity FROM cart WHERE user_id=2 AND product_id=1 AND variant_id=1')[0]['quantity']==3,'reorder adds to rather than erases existing cart')
  request(admin,'/admin/order_detail.php?id='+str(foreign_order),{'csrf':admin_csrf,'status':'completed'})
  check(sql('SELECT status FROM orders WHERE id=?',[foreign_order])[0]['status']=='pending','admin cannot skip an order directly to completed')
  for state in ['processing','shipping','delivered']:
   request(admin,'/admin/order_detail.php?id='+str(foreign_order),{'csrf':admin_csrf,'status':state})
  request(admin,'/admin/order_detail.php?id='+str(foreign_order),{'csrf':admin_csrf,'status':'completed','confirm_cod':1})
  row=sql('SELECT status,payment_status FROM orders WHERE id=?',[foreign_order])[0]
  check(row=={'status':'completed','payment_status':'paid'},'COD completion requires and records explicit collection confirmation')
  unpaid=new_order(method='vnpay')
  request(admin,'/admin/order_detail.php?id='+str(unpaid),{'csrf':admin_csrf,'status':'processing'})
  check(sql('SELECT status FROM orders WHERE id=?',[unpaid])[0]['status']=='pending','unpaid VNPAY orders cannot enter fulfillment')
  before_variant=sql('SELECT * FROM product_variants WHERE product_id=1 ORDER BY id')
  p=sql('SELECT * FROM products WHERE id=1')[0];data={'csrf':admin_csrf,'name':p['name'],'description':p['description'],'price':p['price'],'discount':p['discount'],'category_id':p['category_id'],'brand_id':p['brand_id'],'images_json':p['images'] or '[]','bestseller':1}
  for i,v in enumerate(before_variant):
   for field,source in [('id','id'),('sku','sku'),('color','color'),('capacity','capacity'),('price_add','price_add'),('stock','stock')]:data[f'v_{field}[{i}]']=v[source]
  status,_,_=request(admin,'/admin/manage_products.php?edit=1',data)
  check(status==303,'admin saves product edit')
  after=sql('SELECT * FROM product_variants WHERE product_id=1 ORDER BY id')
  check([v['id'] for v in before_variant]==[v['id'] for v in after] and [v['image'] for v in before_variant]==[v['image'] for v in after],'product edits preserve historical variant IDs and images')
  check(sql('SELECT bestseller FROM products WHERE id=1')[0]['bestseller']==1,'product bestseller checkbox actually persists')
  request(admin,'/admin/product_delete.php',{'csrf':admin_csrf,'id':1})
  check(bool(sql('SELECT id FROM products WHERE id=1')),'products with order history cannot be deleted')
  status,_,_=request(admin,'/admin/product_delete.php?id=1&csrf='+admin_csrf);check(status==405,'product deletion is POST only')
  chart=request(admin,'/admin/index.php')[1];amounts=json.loads(re.search(r'data:\s*(\[[0-9., ]+\])',chart)[1])
  check(sum(amounts)==100000 and '31970000' not in chart,'dashboard chart uses actual paid fulfilled orders instead of sample revenue')
  old=sql('SELECT max_usage,used_count FROM vouchers WHERE id=1')[0]
  request(admin,'/admin/manage_vouchers.php',{'csrf':admin_csrf,'delete':1})
  check(sql('SELECT is_active FROM vouchers WHERE id=1')[0]['is_active']==0 and sql('SELECT max_usage,used_count FROM vouchers WHERE id=1')[0]==old,'stopping a voucher preserves historical counters')
  sql("UPDATE users SET role='admin' WHERE id=2")
  check(request(user,'/admin/index.php')[0]==200,'new role is recognized on an existing session')
  sql("UPDATE users SET role='customer' WHERE id=2")
  check(request(user,'/admin/index.php')[0] in [302,403],'revoked admin role loses access immediately')
  status,_,_=request(guest,'/products/search.php?term[]=bad');check(status==400,'structured input cannot be passed to a scalar search parameter')
  status,_,_=request(user,'/user/profile.php',{'csrf':csrf,'action':'profile','display_name[x]':'bad'});check(status==400,'nested input cannot bypass scalar profile validation')
  status,_,_=request(user,'/clear_cache.php');check(status==403,'customers cannot reset application cache')
  review_order=new_order(status='delivered')
  status,_,_=request(user,'/ajax/add_review.php',{'csrf':'bad','product_id':1,'rating':5,'review':'Audit review'});check(status==403,'review submission requires CSRF')
  status,body,_=request(user,'/ajax/add_review.php',{'csrf':ac,'product_id':1,'rating':5,'review':'Audit review'});check(status==200 and json.loads(body)['success'],'delivered purchase can be reviewed')
  before=sql('SELECT COUNT(*) AS n FROM product_reviews WHERE user_id=2')[0]['n']
  request(user,'/ajax/submit_review.php',{'csrf':ac,'product_id':1,'rating':5,'comment':'Duplicate audit review'})
  check(sql('SELECT COUNT(*) AS n FROM product_reviews WHERE user_id=2')[0]['n']==before,'legacy review API cannot create duplicates')
  evil=json.dumps([{'type':'image','path':'../../config/local.php'}])
  status,_,_=request(user,'/user/review.php?order_id='+str(review_order),{'csrf':ac,'rating[1]':4,'comment[1]':'Edited audit review','media[1]':evil})
  check(status==422 and sql('SELECT review FROM product_reviews WHERE user_id=2 AND order_id=?',[review_order])[0]['review']=='Audit review','review media cannot reference private files and invalid edits do not partially save')
  status,_,_=request(user,'/user/review.php?order_id='+str(review_order),{'csrf':ac,'rating[1]':4,'comment[1]':'Edited audit review','media[1]':'[]'})
  check(status==200 and sql('SELECT review FROM product_reviews WHERE user_id=2 AND order_id=?',[review_order])[0]['review']=='Edited audit review','review edit updates the owned purchase review')
  rid=int(sql('INSERT INTO product_reviews(user_id,product_id,order_id,rating,review) VALUES (1,1,?,5,?)',[foreign_order,'Admin fixture review'])['id'])
  request(user,'/ajax/delete_review.php',{'csrf':ac,'id':rid})
  check(bool(sql('SELECT id FROM product_reviews WHERE id=?',[rid])),'customer cannot delete another user review')
  late=new_order(method='vnpay')
  sql('INSERT INTO payment_transactions(order_id,order_code,payment_method,amount,status) VALUES (?,?,?,?,?)',[late,'LATEAUDIT'+str(late),'vnpay',100000,'pending'])
  request(user,'/user/orders.php?id='+str(late),{'cancel_order':1,'csrf':csrf,'cancel_reason':'Không còn nhu cầu'})
  before_stock=sql('SELECT stock FROM products WHERE id=1')[0]['stock']
  payload={'vnp_TmnCode':'TESTMERCHANT','vnp_TxnRef':'LATEAUDIT'+str(late),'vnp_Amount':'10000000','vnp_ResponseCode':'00','vnp_TransactionStatus':'00','vnp_TransactionNo':'AUDIT-LATE'}
  code="require 'config/config.php';require 'includes/vnpay.php';$q=json_decode($argv[1],true);$q['vnp_SecureHash']=vnpay_sign($q);echo json_encode(vnpay_settle($pdo,$q));"
  result=json.loads(subprocess.check_output([PHP,'-r',code,json.dumps(payload)],cwd=ROOT,env=ENV))
  row=sql('SELECT status,payment_status FROM orders WHERE id=?',[late])[0]
  check(result['RspCode']=='00' and row=={'status':'cancelled','payment_status':'paid'} and sql('SELECT stock FROM products WHERE id=1')[0]['stock']==before_stock,'verified payment arriving after cancellation is recorded for refund review without reopening order or restoring stock twice')
  request(user,'/user/profile.php',{'csrf':csrf,'action':'password','old_password':'wrong','new_password':'AuditPass123!','confirm_password':'AuditPass123!'})
  check(login(browser()),'incorrect old password cannot change credentials')
  second=browser();check(login(second),'second customer session created')
  request(user,'/user/profile.php',{'csrf':csrf,'action':'password','old_password':'password','new_password':'AuditPass123!','confirm_password':'AuditPass123!'})
  check(request(second,'/user/profile.php')[0]==302,'password change revokes other sessions')
  check(not login(browser()) and login(browser(),password='AuditPass123!'),'password change invalidates the old password and accepts the new one')
  raw=secrets.token_hex(32);sql('UPDATE users SET reset_token=?,reset_expires=DATE_ADD(NOW(),INTERVAL 30 MINUTE) WHERE id=2',[hashlib.sha256(raw.encode()).hexdigest()])
  reset=browser();reset_csrf=token(reset,'/auth/reset.php?token='+raw)
  status,body,_=request(reset,'/auth/reset.php',{'csrf':reset_csrf,'token':raw,'password':'ResetPass123!','confirm_password':'ResetPass123!'})
  check('Đã đặt lại mật khẩu' in body and sql('SELECT reset_token FROM users WHERE id=2')[0]['reset_token'] is None,'password reset consumes a hashed one-time token')
  check('không hợp lệ' in request(reset,'/auth/reset.php?token='+raw)[1],'password reset link cannot be replayed')
  social=browser();check(login(social,password='ResetPass123!'),'reset password can authenticate')
  opaque='facebook_'+'a'*16;placeholder='facebook_'+'b'*64+'@social.invalid'
  sql('UPDATE users SET username=?,email=?,display_name=?,password_enabled=0 WHERE id=2',[opaque,placeholder,'Nguyễn Minh An'])
  html=request(social,'/user/profile.php')[1]
  check('Nguyễn Minh An' in html and opaque not in html and placeholder not in html and 'value="" autocomplete="email"' in html,'social profile shows human name and never exposes internal identifiers or email')
  check('chưa có mật khẩu riêng' in request(social,'/user/profile.php?tab=password')[1],'social accounts do not request an unknown random old password')
  # Invalid reset and no-SMTP paths never disclose recovery links to visitors.
  forgot=browser();status,body,_=request(forgot,'/auth/forgot-password.php',{'csrf':token(forgot,'/auth/forgot.php'),'email':'admin@example.com'})
  check('chưa được cấu hình' in body and 'token=' not in body and 'Link (dev)' not in body,'forgot-password alias does not expose a reset token or pretend to send email')
  print('All '+str(len(checks))+' user/admin audit checks passed.',flush=True)
 finally:
  if process:process.terminate();process.wait(timeout=10)
  log.close()
  for f in created_files:
   if f.name.startswith('avatar_2_') and f.parent==ROOT/'assets/images/avatars' and f.is_file():f.unlink()
  subprocess.run([PHP,'-r',"require 'config/config.php';$n=getenv('DB_NAME');if(preg_match('/^giadung_verify_[a-f0-9]{10}$/D',$n)){$pdo->exec('DROP DATABASE `'.$n.'`');}"],cwd=ROOT,env=ENV,check=True)
(ROOT/'artifacts/user-admin-audit.json').write_text(json.dumps({'passed':len(checks),'checks':checks,'database':'disposable copy','live_orders_modified':False},ensure_ascii=False,indent=2),encoding='utf-8')
