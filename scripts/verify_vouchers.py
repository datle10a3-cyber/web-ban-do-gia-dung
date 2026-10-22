"""Account voucher quotas on a disposable database; never places live shop orders."""
from pathlib import Path
import os, sys, subprocess, secrets, tempfile, time, re, json, urllib.request, urllib.parse, urllib.error, http.cookiejar
sys.stdout.reconfigure(encoding='utf-8')
ROOT=Path(__file__).resolve().parents[1]; PHP='E:/xampp/php/php.exe'
DB='giadung_verify_'+secrets.token_hex(5); BASE='http://127.0.0.1:8101/web-ban-do-gia-dung'
ENV=os.environ.copy(); ENV.update(DB_NAME=DB,APP_URL=BASE,OAUTH_LOCAL_URL=BASE,GOOGLE_CLIENT_ID='',GOOGLE_CLIENT_SECRET='',FB_APP_ID='',FB_APP_SECRET='',CHAT_API_KEY='',CHAT_FALLBACK_API_KEY='',MAIL_DISABLED='1')
checks=[]; server=None
def sql(query,args=[]):
    code="require 'config/config.php';$q=$pdo->prepare($argv[1]);$q->execute(json_decode($argv[2],true));echo json_encode($q->columnCount()?$q->fetchAll():['id'=>$pdo->lastInsertId()]);"
    return json.loads(subprocess.check_output([PHP,'-r',code,query,json.dumps(args)],cwd=ROOT,env=ENV))
def check(value,label):
    assert value,label
    checks.append(label); print('PASS '+label,flush=True)
def browser(): return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def req(c,path,data=None,js=None):
    headers={}
    if js is not None: data=json.dumps(js).encode(); headers['Content-Type']='application/json'
    elif data is not None: data=urllib.parse.urlencode(data).encode()
    try: r=c.open(urllib.request.Request(BASE+path,data=data,headers=headers),timeout=20)
    except urllib.error.HTTPError as e:r=e
    body=r.read().decode('utf-8'); assert not re.search(r'Fatal error|Warning:|Parse error',body),path
    return r.status,body
def csrf(c): return re.search(r'data-csrf="([a-f0-9]+)"',req(c,'/')[1])[1]
def login(name):
    c=browser(); token=csrf(c); status,body=req(c,'/auth/login.php',{'csrf':token,'username':name,'password':'password'})
    assert status==200 and 'auth/login.php?error' not in body
    return c,csrf(c)
def start(c,t):
    status,body=req(c,'/cart/buynow.php',js={'csrf':t,'product_id':1,'variant_id':1,'qty':1})
    assert status==200 and json.loads(body)['success'],body
    status,html=req(c,'/checkout.php'); assert status==200
    return re.search(r'name="request_key" value="([a-f0-9]+)"',html)[1],html
def apply(c,t,code,**extra):
    status,body=req(c,'/cart/apply_voucher.php',{'csrf':t,'code':code,**extra}); return status,json.loads(body)
def order(c,t,key,code):
    return req(c,'/cart/place_order.php',{'csrf':t,'request_key':key,'fullname':'Voucher Test Customer','phone':'0909123456','address':'123 Voucher Test Street','payment_method':'cod','voucher_code':code,'discount_amount':999999999,'user_id':1})
def setup(): subprocess.run([PHP,'scripts/setup.php'],cwd=ROOT,env=ENV,check=True,stdout=subprocess.DEVNULL)
with tempfile.TemporaryFile() as log:
  try:
    setup()
    policies=sql('SELECT code,per_user_limit,new_customer_only FROM vouchers')
    check(any(v['code']=='GIAM10' and v['per_user_limit']==2 for v in policies),'10-percent voucher has two uses per account')
    check(any(v['code']=='WELCOME100' and v['per_user_limit']==1 and v['new_customer_only']==1 for v in policies),'welcome voucher is first-order-only with one use')
    # Existing order use must survive replacing the old one-use unique index.
    sql("INSERT INTO voucher_usage(voucher_id,user_id,order_id) VALUES ((SELECT id FROM vouchers WHERE code='GIAM10'),2,NULL)")
    sql("UPDATE vouchers SET used_count=1 WHERE code='GIAM10'")
    setup()
    check(sql("SELECT COUNT(*) n FROM voucher_usage")[0]['n']==1,'re-running setup preserves prior voucher history')
    sql("DELETE FROM voucher_usage");sql("UPDATE vouchers SET used_count=0")
    # Fresh fixtures only; do not copy or modify real customers.
    for name in ['voucher_a','voucher_b','voucher_c']:
        sql("INSERT INTO users(username,email,password,role,display_name) SELECT ?,?,password,'user','Voucher Customer' FROM users WHERE username='user'",[name,name+'@example.test'])
    ids={r['username']:r['id'] for r in sql("SELECT id,username FROM users WHERE username LIKE 'voucher_%'")}
    server=subprocess.Popen([PHP,'-S','127.0.0.1:8101','-t',str(ROOT.parent)],cwd=ROOT,env=ENV,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    for _ in range(30):
        try:req(browser(),'/');break
        except OSError:time.sleep(.1)
    a,ta=login('voucher_a'); b,tb=login('voucher_b'); c,tc=login('voucher_c'); admin,td=login('admin')
    k,html=start(a,ta)
    check('data-user-remaining="2"' in html and 'data-user-limit="2"' in html,'checkout renders personal quota')
    check('value="WELCOME100"' in html,'fresh account receives welcome voucher automatically')
    for _ in range(3):
        status,result=apply(a,ta,'GIAM10',user_id=ids['voucher_b'],subtotal=1)
        assert status==200 and result['user_remaining']==2
    check(sql('SELECT COUNT(*) n FROM voucher_usage')[0]['n']==0,'preview and spoofed account/price do not consume allowances')
    status,body=order(a,ta,k,'GIAM10'); first=json.loads(body)
    check(status==200 and first['success'],'first use of two-use voucher succeeds')
    paid=sql('SELECT subtotal,discount_amount FROM orders WHERE id=?',[first['order_id']])[0]
    check(round(float(paid['subtotal'])*.1)==float(paid['discount_amount']),'10-percent discount is calculated from authoritative prices')
    status,body=order(a,ta,k,'GIAM10')
    check(json.loads(body)['order_id']==first['order_id'] and sql('SELECT COUNT(*) n FROM voucher_usage')[0]['n']==1,'duplicate submit consumes only one use')
    k,html=start(a,ta)
    check('value="WELCOME100"' not in html,'welcome code disappears after any first order')
    status,result=apply(a,ta,'GIAM10');check(result.get('user_remaining')==1,'personal remaining drops from two to one')
    status,result=apply(a,ta,'WELCOME100');check(status==422 and 'đầu tiên' in result['message'],'API rejects newcomer voucher for an existing customer')
    status,body=order(a,ta,k,'WELCOME100');check(status==422 and 'đầu tiên' in json.loads(body)['message'],'order creation also rejects newcomer voucher misuse')
    status,body=order(a,ta,k,'GIAM10');check(status==200 and json.loads(body)['success'],'second use succeeds on a separate order')
    k,html=start(a,ta)
    check('value="GIAM10"' not in html,'exhausted voucher disappears from checkout')
    status,result=apply(a,ta,'GIAM10',user_id=ids['voucher_b']);check(status==422 and 'hết 2 lượt' in result['message'],'API uses authenticated account quota, not posted user ID')
    status,body=order(a,ta,k,'GIAM10');check(status==422 and not json.loads(body)['success'],'third order cannot exceed two uses')
    check(sql('SELECT COUNT(*) n FROM orders WHERE user_id=?',[ids['voucher_a']])[0]['n']==2,'rejected orders do not create rows')
    k,html=start(b,tb)
    check('value="GIAM10"' in html and 'data-user-remaining="2"' in html,'another account has its own full allowance')
    status,body=order(b,tb,k,'WELCOME100');check(status==200 and json.loads(body)['success'],'new customer can use welcome voucher on first order')
    k,_=start(b,tb);status,body=order(b,tb,k,'WELCOME100');check(status==422,'welcome voucher cannot be reused')
    # One-use normal offer remains separate from welcome restrictions.
    k,_=start(a,ta);status,body=order(a,ta,k,'GIADUNG50');check(status==200 and json.loads(body)['success'],'ordinary one-use code can be used after welcome eligibility ends')
    k,_=start(a,ta);status,result=apply(a,ta,'GIADUNG50');check(status==422 and 'hết 1 lượt' in result['message'],'one-use code is enforced per account')
    # Quotas are not silently refunded by cancelling and do not reset on login.
    sql("UPDATE orders SET status='cancelled' WHERE id=?",[first['order_id']])
    aa,taa=login('voucher_a');start(aa,taa);status,result=apply(aa,taa,'GIAM10');check(status==422,'cancel and re-login do not reset usage history')
    # Last global campaign slot is distinct from personal allowances.
    sql("UPDATE vouchers SET max_usage=used_count+1 WHERE code='GIAM10'")
    k,_=start(b,tb);status,body=order(b,tb,k,'GIAM10');check(status==200 and json.loads(body)['success'],'last global campaign slot succeeds within personal quota')
    k,_=start(c,tc);status,result=apply(c,tc,'GIAM10');check(status==422,'campaign exhaustion blocks a fresh account too')
    # Admin policies and repeated migrations preserve deliberate custom changes.
    admin_html=req(admin,'/admin/manage_vouchers.php')[1]; form_csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',admin_html)[1]
    data={'csrf':form_csrf,'code':'CUSTOM10','discount_type':'percent','discount_value':10,'min_order':1000,'max_usage':50,'per_user_limit':2,'is_active':1,'expires_at':''}
    status,body=req(admin,'/admin/manage_vouchers.php',data)
    check(sql("SELECT per_user_limit FROM vouchers WHERE code='CUSTOM10'")[0]['per_user_limit']==2,'admin can configure two uses for a custom code')
    bad=data|{'code':'TOOMANY','per_user_limit':3};req(admin,'/admin/manage_vouchers.php',bad)
    check(not sql("SELECT id FROM vouchers WHERE code='TOOMANY'"),'admin rejects quotas above two')
    bad=data|{'code':'BADWELCOME','new_customer_only':1};req(admin,'/admin/manage_vouchers.php',bad)
    check(not sql("SELECT id FROM vouchers WHERE code='BADWELCOME'"),'admin rejects conflicting first-order/two-use policy')
    req(a,'/admin/manage_vouchers.php',data|{'code':'FORGED'})
    check(not sql("SELECT id FROM vouchers WHERE code='FORGED'"),'customers cannot create vouchers')
    sql("UPDATE vouchers SET per_user_limit=1,new_customer_only=1 WHERE code='GIAM10'")
    setup();setup()
    check(sql("SELECT per_user_limit,new_customer_only FROM vouchers WHERE code='GIAM10'")[0]=={'per_user_limit':1,'new_customer_only':1},'setup does not reset admin policy changes')
    check(sql('SELECT COUNT(*) n FROM voucher_usage')[0]['n']==5,'all successful uses survive repeated migrations')
    # Expired/inactive/minimum order rules remain authoritative.
    sql("UPDATE vouchers SET expires_at=DATE_SUB(NOW(),INTERVAL 1 DAY) WHERE code='CUSTOM10'")
    status,result=apply(c,tc,'CUSTOM10');check(status==422,'expired voucher rejected')
    sql("UPDATE vouchers SET expires_at=NULL,is_active=0 WHERE code='CUSTOM10'")
    status,result=apply(c,tc,'CUSTOM10');check(status==422,'inactive voucher rejected')
    sql("UPDATE vouchers SET is_active=1,min_order=999999999 WHERE code='CUSTOM10'")
    status,result=apply(c,tc,'CUSTOM10');check(status==422,'minimum order cannot be bypassed')
    _,profile=req(c,'/user/profile.php?tab=vouchers');check('Còn 1/1 lượt' in profile and 'WELCOME100' in profile,'account page shows available personal welcome allowance')
    status,body=req(c,'/ajax/chat.php',js={'csrf':tc,'message':'Mã giảm giá còn bao nhiêu lượt?'})
    reply=json.loads(body)['reply'];check('lần/tài khoản' not in reply and 'bạn còn' in reply and 'COD' not in reply,'logged-in chatbot describes personal remaining uses')
    artifact=ROOT/'artifacts/voucher-account-verification.json';artifact.write_text(json.dumps({'checks':checks,'passed':len(checks),'live_database_modified':False},ensure_ascii=False,indent=2),encoding='utf-8')
    print(f'All {len(checks)} voucher checks passed.',flush=True)
  finally:
    if server: server.terminate();server.wait(timeout=10)
    if re.fullmatch(r'giadung_verify_[a-f0-9]{10}',DB):
        cleanup='require "config/app.php";$p=new PDO("mysql:host=".app_env("DB_HOST","127.0.0.1").";port=".app_env("DB_PORT",3306),app_env("DB_USER","root"),app_env("DB_PASSWORD"));$p->exec("DROP DATABASE `".$argv[1]."`");'
        subprocess.run([PHP,'-r',cleanup,DB],cwd=ROOT,env=ENV,check=True)
