<?php
// The isolated payment test is finished. Old browser tabs must not start OAuth on port 8098.
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
header('Cache-Control: no-store');
header('Location: http://localhost/web-ban-do-gia-dung/', true, 303);
