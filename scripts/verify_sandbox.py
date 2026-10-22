"""Read-only checks for the managed HTTPS preview; never sends a payment request."""
from pathlib import Path
import json, os, urllib.request, urllib.error, urllib.parse

runtime_path=Path(os.environ['LOCALAPPDATA'])/'GiaDungSandbox/runtime.json'
runtime=json.loads(runtime_path.read_text(encoding='utf-8-sig'))
origin=runtime['public_origin'];prefix='/web-ban-do-gia-dung'
if not origin.startswith('https://') or urllib.parse.urlsplit(origin).hostname is None:
    raise RuntimeError('An HTTPS origin is required')

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
opener=urllib.request.build_opener(NoRedirect())
results=[]
def request(path,method='GET'):
    req=urllib.request.Request(origin+path,method=method,headers={'ngrok-skip-browser-warning':'1'})
    try:
        response=opener.open(req,timeout=20)
        return response.status,response.read().decode('utf-8',errors='replace'),response.headers
    except urllib.error.HTTPError as error:
        return error.code,error.read().decode('utf-8',errors='replace'),error.headers
def check(label,condition,status):
    results.append({'check':label,'passed':bool(condition),'http_status':status})
    if not condition:raise AssertionError(label)
    print('PASS '+label)

status,body,headers=request(prefix+'/')
check('HTTPS storefront renders the professional footer',status==200 and 'class="store-footer"' in body and 'Fatal error' not in body,status)
check('Preview is marked noindex',headers.get('X-Robots-Tag')=='noindex, nofollow',status)
check('Preview does not reuse localhost login', 'google_e2cd789414e5679b' not in body and 'auth/login.php' in body,status)
for path in ['/phpmyadmin/','/dashboard/',prefix+'/config/local.php',prefix+'/database.sql/web_gia_dung.sql',prefix+'/scripts/sandbox_router.php',prefix+'/admin/',prefix+'/includes/vnpay.php',prefix+'/vendor/autoload.php',prefix+'/web-ban-do-gia-dung/config.php',prefix+'/assets/../config/local.php',prefix+'/assets/%252e%252e/config/local.php']:
    status,_,_=request(path)
    check('Private path blocked '+path,status==404,status)
status,body,_=request(prefix+'/cart/vnpay_ipn.php')
check('Public IPN rejects unsigned input',status==200 and json.loads(body)['RspCode']=='97',status)
status,_,_=request(prefix+'/cart/place_order.php','POST')
check('Public preview cannot place orders',status==405,status)
status,_,_=request(prefix+'/ajax/chat.php','POST')
check('Public preview cannot consume private chatbot keys',status==405,status)
for provider in ['google','facebook']:
    status,_,headers=request(prefix+'/auth/oauth.php?provider='+provider)
    check('OAuth keeps the original localhost callback '+provider,status==302 and headers.get('Location')=='http://localhost'+prefix+'/auth/oauth.php?provider='+provider,status)

status,body,headers=request(prefix+'/auth/oauth_start.php?flow='+'a'*64)
check('HTTPS OAuth rejects unknown login flows',status==400,status)
cookie=headers.get('Set-Cookie','')
check('HTTPS broker uses a separate Secure HttpOnly session',all(part in cookie for part in ['GD_OAUTH_HTTPS=','secure','HttpOnly','SameSite=Lax']),status)
status,_,headers=request(prefix+'/auth/oauth_callback.php?state=forged&code=forged')
check('HTTPS callback rejects forged state without contacting provider',status==302 and headers.get('Location')==prefix+'/auth/login.php',status)
check('OAuth callback prevents caching, referrer leaks and framing',headers.get('Cache-Control')=='no-store' and headers.get('Referrer-Policy')=='no-referrer' and headers.get('X-Frame-Options')=='DENY',status)

report={'https_origin':origin,'checks':results,'scope':'restricted storefront, signed IPN and isolated HTTPS OAuth broker; no payment performed'}
(Path(__file__).resolve().parents[1]/'artifacts/https-security-checks.json').write_text(json.dumps(report,ensure_ascii=False,indent=2),encoding='utf-8')
print('All HTTPS checks passed.')
