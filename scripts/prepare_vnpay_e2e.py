"""Prepare one real provider SANDBOX payment against an isolated copy of supplied SQL."""
from pathlib import Path
import os,json,secrets,subprocess,urllib.request,urllib.parse,http.cookiejar,re,time

ROOT=Path(__file__).resolve().parents[1];PHP='E:/xampp/php/php.exe'
PRIVATE=Path(os.environ['LOCALAPPDATA'])/'GiaDungSandbox'
runtime_path=PRIVATE/'runtime.json';runtime=json.loads(runtime_path.read_text(encoding='utf-8-sig'))
token=secrets.token_hex(5);database='giadung_e2e_'+token;prefix='SIT'+token
env=os.environ.copy();env.update(DB_NAME=database,APP_URL='http://127.0.0.1:8098/web-ban-do-gia-dung',VNPAY_TXN_PREFIX=prefix,CHAT_API_KEY='',CHAT_FALLBACK_API_KEY='',GOOGLE_CLIENT_ID='',GOOGLE_CLIENT_SECRET='',FB_APP_ID='',FB_APP_SECRET='')
subprocess.run([PHP,'scripts/setup.php'],cwd=ROOT,env=env,check=True,stdout=subprocess.DEVNULL)
log=(PRIVATE/'e2e-server.log').open('ab')
server=subprocess.Popen([PHP,'-S','127.0.0.1:8098','-t',str(ROOT.parent)],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
state={'database':database,'prefix':prefix,'server_pid':server.pid,'local_url':env['APP_URL']}
runtime.update(test_db=database,test_txn_prefix=prefix)
runtime_path.write_text(json.dumps(runtime,indent=2),encoding='utf-8')
opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def request(path,data=None):
    body=urllib.parse.urlencode(data).encode() if data else None
    with opener.open(env['APP_URL']+path,data=body,timeout=20) as response:return response.read().decode()
for attempt in range(30):
    try:html=request('/auth/login.php');break
    except OSError:time.sleep(.2)
csrf=re.search(r'name="csrf" value="([a-f0-9]+)"',html)[1]
html=request('/auth/login.php',{'username':'user','password':'password','csrf':csrf})
csrf=re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
result=json.loads(request('/cart/add.php',{'product_id':1,'variant_id':1,'quantity':1,'csrf':csrf,'ajax':1}))
if not result.get('success'):raise RuntimeError('Could not add sandbox fixture')
html=request('/checkout.php?items=1_1');key=re.search(r'name="request_key" value="([a-f0-9]+)"',html)[1]
order=json.loads(request('/cart/place_order.php',{'csrf':csrf,'request_key':key,'fullname':'VNPAY SANDBOX TEST','phone':'0909123456','address':'123 Sandbox Test Street, Test City','payment_method':'vnpay','voucher_code':'','order_note':'Isolated provider sandbox verification; no real sale.'}))
if not order.get('success'):raise RuntimeError('Could not create sandbox order')
url=order['redirect_url']
if urllib.parse.urlsplit(url).hostname!='sandbox.vnpayment.vn':raise RuntimeError('Refusing a non-sandbox payment URL')
state.update(order_id=order['order_id'],order_code=order['order_code'],payment_url=url)
(PRIVATE/'e2e-payment.json').write_text(json.dumps(state,indent=2),encoding='utf-8')
print(json.dumps({'sandbox_fixture_prepared':True,'order_id':order['order_id'],'isolated_database':database,'local_url':env['APP_URL'],'provider':'VNPAY sandbox','payment_link_stored_privately':True}))
