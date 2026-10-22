"""Exercise the configured AI with synthetic shopping questions; never places an order."""
import urllib.request, http.cookiejar, json, re, sys
from pathlib import Path
sys.stdout.reconfigure(encoding='utf-8')
BASE='http://localhost/web-ban-do-gia-dung'
browser=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
html=browser.open(BASE+'/').read().decode('utf-8')
csrf=re.search(r'data-csrf="([a-f0-9]+)"',html)[1]
history=[]
for question in ['Tư vấn máy giặt dưới 15 triệu cho nhà 3 người nhé','Mẫu thứ hai phù hợp không, bạn giải thích ngắn thôi','Rẻ hơn chút, dưới 10 triệu nhé','Code giảm giá còn bao nhiêu lượt?']:
    body=json.dumps({'csrf':csrf,'message':question}).encode()
    req=urllib.request.Request(BASE+'/ajax/chat.php',data=body,headers={'Content-Type':'application/json'})
    result=json.loads(browser.open(req,timeout=28).read())
    assert result.get('success') and result.get('mode')=='ai', 'Expected a genuine AI answer'
    assert '**' not in result['reply'] and not result['reply'].lstrip().startswith('{')
    if 'dưới 10 triệu' in question:
        assert all(p['price']<=10_000_000 and 'Máy giặt' in p['name'] for p in result['products'])
    if question.startswith('Code'):
        assert not result['products'] and '100' in result['reply'] and 'COD' not in result['reply']
    history.append({'question':question,**result})
    print(json.dumps(history[-1],ensure_ascii=False),flush=True)
(Path(__file__).resolve().parents[1]/'artifacts/chat-live-verification.json').write_text(json.dumps(history,ensure_ascii=False,indent=2),encoding='utf-8')
print('Real AI conversation verified; no orders or user records changed.')
