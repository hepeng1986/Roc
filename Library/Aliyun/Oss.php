<?php
/**
 * 阿里云oss服务
 *  
 *  User: pancke@qq.com
 *  Date: 2015-10-26
 *  Time: 上午11:25:47
 */
require_once './sdk.class.php';
require_once './util/oss_util.class.php';

class Aliyun_Oss
{

    /**
     *简单上传
     *上传指定变量的内存值
     */
    public static function uploadObject($sObject, $sContent)
    {
        //$sObject = "oss-php-sdk-test/upload-test-object-name.txt";
        //$sContent  = 'hello world';
        $aOption = array(
            'content' => $sContent,
            'length' => strlen($sContent),
        );
        
        $sBucket = self::getBucketName();
        $oOss = self::getOssClient();
        $oRes = $oOss->upload_file_by_content($sBucket, $sObject, $aOption);
        
//         $sMsg = "上传字符串到 /" . $sBucket . "/" . $sObject;
//         OSSUtil::print_res($oRes, $sMsg);
        return $oRes->isOK();
    }

    /**
     * 简单上传
     * 上传指定的本地文件内容
     */
    public static function uploadFile($sOssPath, $sLocalFile)
    {
        $aOption = array();
        $sBucket = self::getBucketName();
        $oOss = self::getOssClient();
        $oRes = $oOss->upload_file_by_file($sBucket, $sOssPath, $sLocalFile, $aOption);
//         $sMsg = "上传本地文件 :" . $sLocalFile . " 到 /" . $sBucket . "/" . $sOssPath;
//         OSSUtil::print_res($oRes, $sMsg);

        return $oRes->isOK();
    }

    /**
     * 获取object
     * 将object下载到指定的文件
     */
    public static function downloadFile($sObject, $sSaveFile)
    {
        $aOption = array(
            ALIOSS::OSS_FILE_DOWNLOAD => $sSaveFile,
        );
        $sBucket = self::getBucketName();
        $oOss = self::getOssClient();
        $oRes = $oOss->get_object($sBucket, $sObject, $aOption);
//         $sMsg = "下载 object /" . $sBucket . "/" . $sObject . " 到本地文件:" . $sSaveFile;
//         OSSUtil::print_res($oRes, $sMsg);
        
        return $oRes->body;
    }
    
    /**
     * 删除File
     * @param unknown $sObject
     * @return boolean
     */
    public static function deleteFile($sObject)
    {
        $sBucket = self::getBucketName();
        $oOss = self::getOssClient();
        $oRes = $oOss->delete_object($sBucket, $sObject);
        
        return $oRes->isOK();
    }
    
    /**
     * 创建目录
     * @param unknown $sPath
     */
    public static function creatDir($sPath)
    {
        $sBucket = self::getBucketName();
        $oOss = self::getOssClient();
        $oRes = $oOss->create_object_dir($sBucket, $sPath);
//         $sMsg = "创建模拟文件夹 /" . $sBucket . "/" . $sPath;
//         OSSUtil::print_res($oRes, $sMsg);
        
        return $oRes->isOK();
    }
    
    /**
     * 取得OSSCLIENT实例
     * 
     * @return ALIOSS
     */
    private static function getOssClient ()
    {
        return new ALIOSS(OSS_ACCESS_ID, OSS_ACCESS_KEY, OSS_ENDPOINT);
    }

    /**
     * myecho
     * @param unknown $sMsg
     */
    private static function myecho ($sMsg)
    {
        $new_line = " \n";
        echo $sMsg . $new_line;
    }

    /**
     * 取得BucketName
     * @return string
     */
    private static function getBucketName ()
    {
        return OSS_TEST_BUCKET;
    }

    /**
     * 创建Bucket
     */
    private static function createBucket ()
    {
        $oOss = self::getOssClient();
        $sBucket = self::getBucketName();
        $acl = ALIOSS::OSS_ACL_TYPE_PUBLIC_READ;
        $oRes = $oOss->create_bucket($sBucket, $acl);
//         $sMsg = "创建bucket " . $sBucket;
//         OSSUtil::print_res($oRes, $sMsg);

        return $oRes->isOK();
    }
}