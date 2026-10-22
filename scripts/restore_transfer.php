<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);
// Relative XAMPP paths work on C:, D:, E: and other installation drives.
if(!in_array('--skip-config',$argv,true)){
    $local=$root.'/config/local.php';
    if(!is_file($local)){copy($root.'/config/local.example.php',$local);}
    $settings=require $local;
    $settings['OAUTH_RUNTIME_DIR']=str_replace('\\','/',dirname($root,2)).'/var/gia-dung-oauth';
    if(file_put_contents($local,"<?php\nreturn ".var_export($settings,true).";\n",LOCK_EX)===false){throw new RuntimeException('Cannot write private local configuration.');}
}
require_once $root.'/config/app.php';
$name=(string)app_env('DB_NAME','web_gia_dung');
if(!preg_match('/^[a-zA-Z0-9_]+$/D',$name)){throw new RuntimeException('Invalid database name.');}
$snapshot=getenv('TRANSFER_SQL_PATH')?:$root.'/database.sql/transfer-current.sql';
$manifest=preg_replace('/transfer-current\.sql$/','transfer-manifest.json',$snapshot);
$connection='mysql:host='.app_env('DB_HOST','127.0.0.1').';port='.app_env('DB_PORT',3306).';charset=utf8mb4';
try{$pdo=new PDO($connection,app_env('DB_USER','root'),app_env('DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}
catch(PDOException $e){fwrite(STDERR,"Bat MySQL trong XAMPP. Kiem tra DB_HOST/PORT/USER/PASSWORD trong config/local.php.\n");exit(1);}
if(is_file($snapshot)){
    $meta=is_file($manifest)?json_decode(file_get_contents($manifest),true):null;
    if(!$meta || !hash_equals($meta['sha256']??'',hash_file('sha256',$snapshot))){throw new RuntimeException('Snapshot checksum does not match. Nothing was imported.');}
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$name`");
    $stateFile=getenv('TRANSFER_STATE_FILE')?:$root.'/transfer-private/installed.json';
    $state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true):null;
    $hasTables=(bool)$pdo->query('SHOW TABLES')->fetchColumn();
    $installed=$hasTables && ($state['database']??'')===$name && hash_equals($meta['sha256'],$state['snapshot_sha256']??'');
    if($hasTables && !$installed){
        if(!in_array('--side-by-side',$argv,true)){
            fwrite(STDERR,"Database da co du lieu. Khong ghi de. Ban snapshot duoc giu nguyen.\n");exit(1);
        }
        $baseName=substr($name,0,36).'_transfer_'.substr($meta['sha256'],0,8);
        $name=$baseName;
        // Existing databases, including unfinished older imports, are never overwritten.
        while(true){
            $q=$pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');$q->execute([$name]);
            if(!$q->fetchColumn()){break;}
            $name=$baseName.'_'.bin2hex(random_bytes(3));
        }
        $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$name`");
        echo "Da tao database rieng; du lieu ban public van duoc giu nguyen.\n";
    }
    if(!$installed){
        $pdo->exec(file_get_contents($snapshot));
        foreach($meta['table_counts'] as $table=>$expected){
            if(!preg_match('/^[a-zA-Z0-9_]+$/D',$table)){throw new RuntimeException('Invalid snapshot table name.');}
            if((int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()!==(int)$expected){throw new RuntimeException('Imported row count mismatch.');}
        }
        if(!in_array('--skip-config',$argv,true)){
            $settings['DB_NAME']=$name;
            if(file_put_contents($local,"<?php\nreturn ".var_export($settings,true).";\n",LOCK_EX)===false){throw new RuntimeException('Cannot save restored database configuration.');}
        }
        if(!is_dir(dirname($stateFile))){mkdir(dirname($stateFile),0700,true);}
        if(file_put_contents($stateFile,json_encode(['database'=>$name,'snapshot_sha256'=>$meta['sha256']],JSON_THROW_ON_ERROR),LOCK_EX)===false){throw new RuntimeException('Cannot save installation state.');}
        echo 'Da khoi phuc day du '.count($meta['table_counts']).' bang tu ban chuyen may.'.PHP_EOL;
    }else{echo "Ban day du da duoc cai; giu nguyen du lieu hien tai.\n";}
    putenv('DB_NAME='.$name);
    echo 'Database: '.$name.PHP_EOL;
}
require __DIR__.'/setup.php';
if(!is_file($root.'/vendor/autoload.php')){fwrite(STDERR,"Can cai thu vien: composer install\n");exit(1);}
if(!in_array('--skip-config',$argv,true)){
    $extra=$root.'/transfer-private';$localapp=getenv('LOCALAPPDATA');
    foreach(['ngrok.exe'=>['GiaDungSandbox/bin','ngrok.exe'],'ngrok.yml'=>['ngrok','ngrok.yml']] as $file=>$target){
        if($localapp && is_file($extra.'/'.$file)){
            $dir=$localapp.'/'.$target[0];if(!is_dir($dir)){mkdir($dir,0700,true);}
            if(!is_file($dir.'/'.$target[1])){copy($extra.'/'.$file,$dir.'/'.$target[1]);}
        }
    }
}
echo "Bat Apache va mo http://localhost/web-ban-do-gia-dung/\nGoogle dung callback localhost cu. Facebook va IPN VNPAY can ket noi HTTPS/ngrok tai may moi.\n";
