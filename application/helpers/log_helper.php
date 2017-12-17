<?php
function runLog($operation,$data="无"){
    $logTpl="[%s ]: 执行 < %s >：操作数据[ %s ]\r\n";
    $content=sprintf($logTpl,date("Y-m-d H:i:s", time()),$operation,$data);
    file_put_contents("../application/logs/run.log", $content,FILE_APPEND);
}