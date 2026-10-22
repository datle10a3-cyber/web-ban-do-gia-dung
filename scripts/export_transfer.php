<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/config.php';
$directory=$argv[1]??'';
if(!$directory || !is_dir($directory)){fwrite(STDERR,"Pass an existing private output directory.\n");exit(1);}
$directory=realpath($directory);
$htdocs=realpath(dirname(__DIR__,2));
if(str_starts_with(strtolower(str_replace('\\','/',$directory)).'/',strtolower(str_replace('\\','/',$htdocs)).'/')){throw new RuntimeException('Export must stay outside htdocs.');}
$destination=$directory.'/transfer-current.sql';
if(is_file($destination)){throw new RuntimeException('A snapshot already exists at this destination.');}
$dump=dirname(PHP_BINARY,2).'/mysql/bin/mysqldump.exe';
if(!is_file($dump)){throw new RuntimeException('XAMPP mysqldump is unavailable.');}
$args=[$dump,'--host='.(string)app_env('DB_HOST','127.0.0.1'),'--port='.(int)app_env('DB_PORT',3306),'--user='.(string)app_env('DB_USER','root'),'--single-transaction','--quick','--hex-blob','--default-character-set=utf8mb4','--skip-add-locks',(string)app_env('DB_NAME','web_gia_dung')];
$env=getenv();$env['MYSQL_PWD']=(string)app_env('DB_PASSWORD');
$process=proc_open($args,[0=>['pipe','r'],1=>['file',$destination,'wb'],2=>['pipe','w']],$pipes,null,$env);
if(!is_resource($process)){throw new RuntimeException('Could not start database export.');}
fclose($pipes[0]);$diagnostics=stream_get_contents($pipes[2]);fclose($pipes[2]);
if(proc_close($process)!==0){@unlink($destination);throw new RuntimeException('Database export failed; no snapshot was kept.');}
$counts=[];
foreach($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table){$counts[$table]=(int)$pdo->query('SELECT COUNT(*) FROM `'.str_replace('`','``',$table).'`')->fetchColumn();}
$meta=['exported_at'=>date(DATE_ATOM),'database'=>(string)app_env('DB_NAME','web_gia_dung'),'sha256'=>hash_file('sha256',$destination),'table_counts'=>$counts];
file_put_contents($directory.'/transfer-manifest.json',json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'Exported '.count($counts).' tables, '.array_sum($counts).' records. Private snapshot saved outside htdocs.'.PHP_EOL;
