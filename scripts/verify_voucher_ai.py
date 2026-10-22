"""Read-only checks of voucher policy answers from the configured AI."""
import urllib.request,http.cookiejar,json,re,sys
from pathlib import Path
sys.stdout.reconfigure(encoding='utf-8')
base='http://localhost/web-ban-do-gia-dung'
c=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
html=c.open(base+'/').read().decode('utf-8');csrf=re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
results=[]
cases=[('GIAM10 giảm bao nhiêu phần trăm, một tài khoản được dùng mấy lần?',2),('WELCOME100 dành cho ai, dùng tối đa mấy lần?',1),('Vậy tôi dùng GIAM10 được 100 lần trên một tài khoản đúng không?',2)]
for question,limit in cases:
    body=json.dumps({'csrf':csrf,'message':question}).encode()
    response=json.loads(c.open(urllib.request.Request(base+'/ajax/chat.php',data=body,headers={'Content-Type':'application/json'}),timeout=30).read())
    reply=response.get('reply','');text=reply.lower()
    assert response.get('success') and response.get('mode')=='ai','Expected configured AI'
    terms=[str(limit),'một' if limit==1 else 'hai']
    assert any(re.search(t+r'.{0,18}(?:lần|lượt)',text) for t in terms),reply
    if limit==1:assert 'đầu' in text,reply
    assert not response['products'],reply
    results.append({'question':question,'reply':reply,'passed':True})
    print('PASS '+question+' -> '+reply,flush=True)
(Path(__file__).resolve().parents[1]/'artifacts/voucher-ai-verification.json').write_text(json.dumps(results,ensure_ascii=False,indent=2),encoding='utf-8')
