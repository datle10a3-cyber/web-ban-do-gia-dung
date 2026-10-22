<?php
require_once __DIR__.'/../config/app.php';
function chat_ai_reply(array $messages, bool $json = false): ?string {
    $providers=[
        ['key'=>app_env('CHAT_API_KEY'),'url'=>app_env('CHAT_API_URL'),'model'=>app_env('CHAT_MODEL')],
        ['key'=>app_env('CHAT_FALLBACK_API_KEY'),'url'=>app_env('CHAT_FALLBACK_API_URL'),'model'=>app_env('CHAT_FALLBACK_MODEL')]
    ];
    foreach($providers as $provider){
        if(!$provider['key']||!$provider['model']||!filter_var($provider['url'],FILTER_VALIDATE_URL)||parse_url($provider['url'],PHP_URL_SCHEME)!=='https'||!function_exists('curl_init')){continue;}
        $body=['model'=>$provider['model'],'messages'=>$messages,'max_completion_tokens'=>1200];
        if($json && parse_url($provider['url'],PHP_URL_HOST)==='api.groq.com'){$body['response_format']=['type'=>'json_object'];}
        if((str_starts_with($provider['model'],'openai/gpt-oss')||str_starts_with($provider['model'],'gemini-3'))){$body['reasoning_effort']=str_contains($provider['model'],'flash-lite')?'minimal':'low';}
        $ch=curl_init($provider['url']);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>(parse_url($provider['url'],PHP_URL_HOST)==='generativelanguage.googleapis.com'?15:7),CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$provider['key']],CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_UNICODE)]);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$errno=curl_errno($ch);curl_close($ch);
        $data=json_decode($raw?:'',true);$answer=$data['choices'][0]['message']['content']??null;
        if($status===200&&($data['choices'][0]['finish_reason']??'')!=='length'&&is_string($answer)&&trim($answer)!==''){return mb_substr(trim($answer),0,4000);}
        // Never log request headers, the API key or a raw provider error body.
        error_log('CHAT_PROVIDER_ERROR host='.parse_url($provider['url'],PHP_URL_HOST).' http='.$status.' curl='.$errno);
    }
    return null;
}
