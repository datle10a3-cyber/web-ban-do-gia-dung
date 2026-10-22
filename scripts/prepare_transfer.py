"""Create a private complete ZIP and a separate publishable source checkout."""
from pathlib import Path
import os,sys,re,json,subprocess,shutil,zipfile,hashlib,datetime
sys.stdout.reconfigure(encoding='utf-8')
ROOT=Path(__file__).resolve().parents[1]
PHP=Path(os.environ.get('PHP_BINARY',str(ROOT.parents[1]/'php/php.exe')))
target=Path(sys.argv[1]).resolve()
if target.is_relative_to(ROOT.parent):raise SystemExit('Output must stay outside htdocs.')
target.mkdir(parents=True,exist_ok=False)
snapshot=target/'private-data';snapshot.mkdir()
subprocess.run([str(PHP),str(ROOT/'scripts/export_transfer.php'),str(snapshot)],cwd=ROOT,check=True)
files=[p for p in ROOT.rglob('*') if p.is_file() and '.git' not in p.relative_to(ROOT).parts and '__pycache__' not in p.parts and p.suffix!='.pyc']
archive=target/('GiaDungShop-FULL-PRIVATE-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S')+'.zip')
manifest={}
with zipfile.ZipFile(archive,'x',compression=zipfile.ZIP_DEFLATED,compresslevel=6) as z:
    for p in files:
        rel=p.relative_to(ROOT).as_posix();data=p.read_bytes()
        z.writestr('web-ban-do-gia-dung/'+rel,data);manifest[rel]=hashlib.sha256(data).hexdigest()
    for p in snapshot.iterdir():z.write(p,'web-ban-do-gia-dung/database.sql/'+p.name)
    localapp=Path(os.environ.get('LOCALAPPDATA',''))
    extras=[(localapp/'GiaDungSandbox/bin/ngrok.exe','transfer-private/ngrok.exe'),(localapp/'ngrok/ngrok.yml','transfer-private/ngrok.yml')]
    for p,rel in extras:
        if p.is_file():z.write(p,'web-ban-do-gia-dung/'+rel)
    z.writestr('web-ban-do-gia-dung/database.sql/transfer-files.json',json.dumps(manifest,indent=2))
with zipfile.ZipFile(archive) as z:
    if z.testzip():raise RuntimeError('Private archive failed CRC verification.')
public=target/'public-repository';public.mkdir()
skip_roots={'web-ban-do-gia-dung','artifacts','uploads','transfer-private'}
def allowed(rel):
    parts=rel.parts;s=rel.as_posix()
    if parts[0] in skip_roots:return False
    if s in ['config/local.php','config/mail.php']:return False
    if s.startswith(('assets/uploads/','assets/images/reviews/')):return False
    if s.startswith('assets/images/avatars/') and s!='assets/images/avatars/default_avatar.jpg':return False
    if '.env'==rel.name or rel.name.startswith('.env.') or rel.suffix in ['.zip','.log','.backup']:return False
    if s.startswith('database.sql/transfer-'):return False
    return True
for p in files:
    rel=p.relative_to(ROOT)
    if allowed(rel):
        dst=public/rel;dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dst)
mail="""<?php
require_once __DIR__.'/app.php';
foreach (['MAIL_HOST'=>'','MAIL_PORT'=>587,'MAIL_USERNAME'=>'','MAIL_PASSWORD'=>'','MAIL_FROM'=>'','MAIL_FROM_NAME'=>'Gia Dụng Shop'] as $key=>$default) {
    if (!defined($key)) { define($key,app_env($key,$default)); }
}
"""
(public/'config/mail.php').write_text(mail,encoding='utf-8')
# Only synthetic seed accounts/orders are published. The original source SQL is
# untouched in the working shop and is included verbatim in the private archive.
seed=public/'database.sql/web_gia_dung.sql';sql=seed.read_text(encoding='utf-8')
def scrub_seed(match):
    block=match[0]
    block=re.sub(r'[A-Za-z0-9_.+%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}',lambda m:'demo-'+hashlib.sha256(m[0].encode()).hexdigest()[:8]+'@example.invalid',block)
    block=re.sub(r"'\+?[0-9]{9,12}'","'0900000000'",block)
    if re.match('INSERT INTO orders',block,re.I):
        block=re.sub(r"'[^']*'",lambda m:m[0] if m[0] in ["'pending'","'cod'"] or re.fullmatch(r"'GD[0-9]+'",m[0]) else "'Du lieu mau'",block)
    return block
sql=re.sub(r'INSERT INTO (?:users|orders)\b[^;]*;',scrub_seed,sql,flags=re.I|re.S)
seed.write_text(sql,encoding='utf-8')
# Capture secrets only in memory to detect exact leaked values; never log them.
private_code="require 'config/app.php';require 'config/mail.php';$s=[];foreach($appSettings as $k=>$v){if(preg_match('/SECRET|PASSWORD|TOKEN|API_KEY/',$k)&&is_string($v)&&strlen($v)>7){$s[]=$v;}}if(defined('MAIL_PASSWORD')&&strlen(MAIL_PASSWORD)>7){$s[]=MAIL_PASSWORD;}echo json_encode($s);"
values=json.loads(subprocess.check_output([str(PHP),'-r',private_code],cwd=ROOT))
patterns=[rb'gsk_[A-Za-z0-9_-]{20,}',rb'GOCSPX-[A-Za-z0-9_-]{15,}',rb'xsmtpsib-[A-Za-z0-9_-]{15,}',rb'AIza[A-Za-z0-9_-]{25,}',rb'gh[pousr]_[A-Za-z0-9]{20,}',rb'-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----']
findings=[]
for p in public.rglob('*'):
    if not p.is_file():continue
    data=p.read_bytes()
    if any(v.encode() in data for v in values) or any(re.search(pattern,data) for pattern in patterns):findings.append(p.relative_to(public).as_posix())
if findings:raise RuntimeError('Do not publish: secret matches in '+', '.join(findings))
summary={'archive':str(archive),'archive_bytes':archive.stat().st_size,'archive_sha256':hashlib.sha256(archive.read_bytes()).hexdigest(),'private_source_files':len(manifest),'public_repository':str(public),'public_files':sum(p.is_file() for p in public.rglob('*')),'secret_scan':'passed','source_sql_unchanged':hashlib.sha256((ROOT/'database.sql/web_gia_dung.sql').read_bytes()).hexdigest()=='9f7158ac8fff4654e7fea5d1b2a7658fb3efe519c1f9c3f1e8a3be7f8566fd3f'}
(target/'release-summary.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2),encoding='utf-8')
print(json.dumps(summary,ensure_ascii=False,indent=2))
