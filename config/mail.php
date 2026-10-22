<?php
require_once __DIR__.'/app.php';
foreach (['MAIL_HOST'=>'','MAIL_PORT'=>587,'MAIL_USERNAME'=>'','MAIL_PASSWORD'=>'','MAIL_FROM'=>'','MAIL_FROM_NAME'=>'Gia Dụng Shop'] as $key=>$default) {
    if (!defined($key)) { define($key,app_env($key,$default)); }
}
