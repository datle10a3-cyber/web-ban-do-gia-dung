"""Verify OAuth boundary handling using fake providers and a disposable database.
No authorization code is fabricated, no provider request is sent, no shop data is changed.
"""
from pathlib import Path
import os, sys, json, tempfile, secrets, subprocess, urllib.request, urllib.error, urllib.parse, http.cookiejar, time, hashlib

sys.stdout.reconfigure(encoding='utf-8')
ROOT = Path(__file__).resolve().parents[1]
PHP = 'E:/xampp/php/php.exe'
DB = 'giadung_verify_' + secrets.token_hex(5)
BASE = 'http://127.0.0.1:8099/web-ban-do-gia-dung'
checks = []

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs): return None

def client():
    return urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def request(browser, path, headers=None):
    try: r = browser.open(urllib.request.Request(BASE + path, headers=headers or {}), timeout=15)
    except urllib.error.HTTPError as e: r = e
    return r.status, r.headers, r.read().decode('utf-8')

def check(label, value):
    if not value: raise AssertionError(label)
    checks.append(label); print('PASS ' + label, flush=True)

with tempfile.TemporaryDirectory(prefix='giadung-oauth-') as temp:
    private = Path(temp)
    origin = 'https://oauth-verification.ngrok-free.app'
    (private/'runtime.json').write_text(json.dumps({'public_origin':origin}))
    env = os.environ.copy()
    env.update(DB_NAME=DB, APP_URL=BASE, OAUTH_LOCAL_URL=BASE, OAUTH_RUNTIME_DIR=temp,
               FB_APP_ID='fixture-app-id', FB_APP_SECRET='fixture-secret', GOOGLE_CLIENT_ID='fixture-google',
               GOOGLE_CLIENT_SECRET='fixture-secret', CHAT_API_KEY='', CHAT_FALLBACK_API_KEY='')
    # Simulate the trusted loopback TLS proxy in a disposable server only.
    router = private/'router.php'
    router.write_text("<?php $p=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); "
        "if(in_array($p,['/web-ban-do-gia-dung/auth/oauth_start.php','/web-ban-do-gia-dung/auth/oauth_callback.php'],true)&&($_SERVER['HTTP_X_FIXTURE_TLS']??'')==='yes'){"
        "$_SERVER['HTTPS']='on';$_SERVER['GD_HTTPS_OAUTH']=true;session_name('GD_OAUTH_HTTPS');ini_set('session.use_strict_mode','1');} return false;")
    process = None
    log = (private/'server.log').open('w')
    try:
        subprocess.run([PHP,'scripts/setup.php'],cwd=ROOT,env=env,check=True,stdout=subprocess.DEVNULL)
        process = subprocess.Popen([PHP,'-S','127.0.0.1:8099','-t',str(ROOT.parent),str(router)],cwd=ROOT,env=env,stdout=log,stderr=log,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
        browser = client()
        for attempt in range(40):
            try: request(browser,'/auth/login.php'); break
            except OSError: time.sleep(.1)
        status, h, _ = request(browser,'/auth/oauth.php?provider=google')
        params = urllib.parse.parse_qs(urllib.parse.urlsplit(h['Location']).query)
        check('Google callback stays on its canonical configured origin',params['redirect_uri']==[BASE+'/auth/oauth_callback.php'])
        status, h, _ = request(browser,'/auth/oauth.php?provider=google',{'Host':'localhost:8099'})
        check('Noncanonical host is redirected before creating OAuth state',status==302 and h['Location']==BASE+'/auth/oauth.php?provider=google')

        def begin():
            status, h, _ = request(browser,'/auth/oauth.php?provider=facebook')
            target = urllib.parse.urlsplit(h['Location'])
            flow = urllib.parse.parse_qs(target.query)['flow'][0]
            return flow, private/'oauth-flows'/f'{flow}.json', target

        flow, file, target = begin()
        check('Facebook begins on HTTPS without moving the shop or Google origin',target.scheme=='https' and target.hostname=='oauth-verification.ngrok-free.app')
        before = len(list(file.parent.glob('*.json')))
        status, _, _ = request(client(),'/auth/oauth_start.php?flow='+'a'*64,{'X-Fixture-TLS':'yes'})
        check('Unknown flow fails without allocating storage',status==400 and len(list(file.parent.glob('*.json')))==before)
        status, _, _ = request(client(),'/auth/oauth_start.php?flow='+flow)
        check('Direct HTTP cannot act as the HTTPS broker',status==400)
        status, h, _ = request(client(),'/auth/oauth_start.php?flow='+flow,{'X-Fixture-TLS':'yes'})
        fb = urllib.parse.parse_qs(urllib.parse.urlsplit(h['Location']).query)
        cookie = h.get('Set-Cookie','')
        check('Facebook redirect_uri uses the HTTPS callback',fb['redirect_uri']==[origin+'/web-ban-do-gia-dung/auth/oauth_callback.php'])
        check('Facebook asks only for its configured public profile scope',fb['scope']==['public_profile'])
        check('HTTPS session is separate, Secure, HttpOnly and SameSite=Lax',all(x in cookie for x in ['GD_OAUTH_HTTPS=','secure','HttpOnly','SameSite=Lax']))
        status, _, _ = request(client(),'/auth/oauth_start.php?flow='+flow,{'X-Fixture-TLS':'yes'})
        check('A flow can start only once',status==400)
        session_cookie = cookie.split(';')[0]
        status, h, _ = request(client(),'/auth/oauth_callback.php?state=forged&code=forged',{'X-Fixture-TLS':'yes','Cookie':session_cookie})
        check('Forged provider state cannot complete login',status==302 and h['Location'].endswith('/auth/login.php') and json.loads(file.read_text())['status']=='started')

        def ready(file, expired=False):
            data=json.loads(file.read_text()); ticket=secrets.token_hex(32)
            data.update(status='ready',ticket=hashlib.sha256(ticket.encode()).hexdigest(),user_id=2,expires=int(time.time())+(-1 if expired else 60))
            file.write_text(json.dumps(data)); return ticket

        ticket=ready(file)
        finish='/auth/oauth_finish.php?flow='+flow+'&ticket='+ticket
        status,h,_=request(client(),finish)
        check('A different browser cannot consume the login grant',status==302 and h['Location'].endswith('/auth/login.php') and json.loads(file.read_text())['status']=='ready')
        status,h,_=request(browser,finish.replace(ticket,'b'*64))
        check('An incorrect handoff ticket cannot consume the login grant',h['Location'].endswith('/auth/login.php') and json.loads(file.read_text())['status']=='ready')
        status,h,_=request(browser,finish)
        check('The initiating browser consumes a valid grant exactly once',h['Location'].endswith('/index.php') and json.loads(file.read_text())['status']=='consumed')
        check('Login handoff responses cannot be cached or leak a referrer',h['Cache-Control']=='no-store' and h['Referrer-Policy']=='no-referrer')
        status,h,_=request(browser,finish)
        check('Replayed handoff tickets are rejected',h['Location'].endswith('/auth/login.php'))
        flow,file,_=begin();ticket=ready(file,True)
        status,h,_=request(browser,'/auth/oauth_finish.php?flow='+flow+'&ticket='+ticket)
        check('Expired login grants cannot establish a session',h['Location'].endswith('/auth/login.php') and json.loads(file.read_text())['status']=='ready')
        check('Temporary OAuth fixtures are isolated from the existing database',DB.startswith('giadung_verify_') and env['DB_NAME']!='web_gia_dung')
    finally:
        if process: process.terminate(); process.wait(timeout=10)
        log.close()
        subprocess.run([PHP,'-r',"require 'config/config.php'; $name=getenv('DB_NAME'); if(preg_match('/^giadung_verify_[a-f0-9]{10}$/D',$name)){$pdo->exec('DROP DATABASE `'.$name.'`');}"],cwd=ROOT,env=env,check=True)

(ROOT/'artifacts/oauth-security-checks.json').write_text(json.dumps({'passed':len(checks),'checks':checks,'real_provider_requests':0,'shop_database_modified':False},ensure_ascii=False,indent=2),encoding='utf-8')
print(f'All {len(checks)} OAuth checks passed.')
