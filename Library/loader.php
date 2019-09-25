<?php

function yafAutoload ($sClassName)
{
    // echo $sClassName."\n";
    $sFile = join(DIRECTORY_SEPARATOR, explode('_', $sClassName)) . '.php';
    foreach (array(
        APP_PATH,
        APP_EXTEND,
        LIB_PATH,
        APP_PATH . '/..'
    ) as $sPath) {
        // echo $sPath . '/' . $sFile . "\n";
        if (file_exists($sPath . '/' . $sFile)) {
            require_once $sPath . '/' . $sFile;
            return true;
        }
    }
    #throw new Exception('类【' . $sClassName . '】没有加载到！');
    return false;
}
spl_autoload_register('yafAutoload');

require_once LIB_PATH . '/../vendor/autoload.php';