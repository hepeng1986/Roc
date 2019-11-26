<?php
/*
 * 自动加载函数
 */
function frameAutoload ($sClassName)
{
    // echo $sClassName."\n";
    $sFile = implode(DS, explode('_', $sClassName)) . '.php';
    foreach ([APP_PATH, LIB_PATH] as $sPath) {
        $sFileName = $sPath . $sFile;
        if (file_exists($sFileName)) {
            require_once $sFileName;
            return true;
        }
    }
    return false;
}
spl_autoload_register('frameAutoload');
require_once VERDOR_PATH . 'autoload.php';