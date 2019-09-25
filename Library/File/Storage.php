<?php

/**
 * Created by PhpStorm.
 * User: yaobiqing
 * Date: 14/12/24
 * Time: 下午1:48
 */
class File_Storage
{

    const FILE_NOT_EXISTS = 101;

    const FILE_IMAGE_DIMENSION_NOT_ALLOWED = 102;

    const FILE_MIMETYPE_VIEW_NOT_ALLOWED = 103;

    const FILE_EXT_INVAILD = 104;

    const FILE_MIMETYPE_DOWNLOAD_NOT_ALLOWED = 105;

    const FILE_MIMETYPE_NOT_ALLOWED = 106;

    const FILE_SAVE_SUCCESS = 0; // 保存文件成功
    const FILE_UPLOAD_ERROR = 1; // 文件上传错误
    const FILE_NOT_POST_UPLOAD_FILE = 2; // 不是通过POST上传文件
    const FILE_SIZE_LIMITED_ERROR = 3; // 上传文件大小超出限制范围
    const FILE_MIME_TYPE_ERROR = 4; // 文件上传类型超出限制
    const FILE_MOVE_TMP_FILE_ERROR = 5; // 移动上传临时文件出错
    const FILE_SAVE_INDEX_ERROR = 6; // 保存文件索引出错

    /**
     * 保存上传的文件
     *
     * @param string $p_sName
     * @param string $p_sTmpName
     * @param int $p_iError
     * @param int $p_iSize
     * @param string $p_sIP
     * @param int $p_iTime
     * @param array $o_aFileInfo
     * @return array
     */
    public function saveLocalFile ($p_sPath, $p_sFile, $p_sIP = '127.0.0.1', $i_bID = File_Model_FileMeta::BID_DEFAULT)
    {
        $iError = self::FILE_SAVE_SUCCESS;
        $sFile = $p_sPath.$p_sFile;
        do {

            $sMimeType = Util_File::getMimeType($sFile);
            if (empty($sMimeType) || $sMimeType == 'application/zip') {
                $sMimeType = Util_File::getMimeTypeByExtension($p_sFile);
            }
            if (empty($sMimeType)) {
                $sMimeType = 'image/jpeg';
            }
            $sExtension = Util_File::getExtension($sMimeType);
            $iSize = filesize($sFile);
            $sFileKey = sha1_file($sFile);
            $aFileInfo = File_Model_File::getFileByKey($sFileKey);

            if (! empty($aFileInfo)) {
                $iError = self::FILE_SAVE_SUCCESS;
                $aFileMeta = File_Model_FileMeta::getFileMetaByKey($sFileKey);
                $aFileMet['iBID'] = $aFileMeta['iBID'] | $i_bID;
                File_Model_FileMeta::updData(array(
                        'iBID' => $aFileMet['iBID'],
                        'iAutoID' => $aFileMeta['iAutoID']
                    ));
                $mReturn = $this->normalizeFileInfo($aFileInfo, $aFileMeta);
                break;
            }

            $sDestFile = $this->getDestFile($sFileKey);
            $bImage = $this->isImage($sExtension);
            if ($bImage) {
                $aImageInfo = $this->getImageInfo($sFile);
                $o_aFileInfo = array(
                    'sKey' => $sFileKey,
                    'sExt' => $sExtension,
                    'sFile' => $sFileKey . '.' . $sExtension,
                    'iSize' => $iSize,
                    'iWidth' => $aImageInfo['iWidth'],
                    'iHeight' => $aImageInfo['iHeight']
                );
                $aFileMeta = array(
                    'sKey' => $sFileKey,
                    'sName' => $p_sFile,
                    'iSize' => $iSize,
                    'sMimeType' => $sMimeType,
                    'iBID' => $i_bID,
                    'iIP' => $this->ipToLong($p_sIP),
                    'iWidth' => $aImageInfo['iWidth'],
                    'iHeight' => $aImageInfo['iHeight']
                );
            } else {
                $o_aFileInfo = array(
                    'sKey' => $sFileKey,
                    'sExt' => $sExtension,
                    'sFile' => $sFileKey . '.' . $sExtension,
                    'iSize' => $iSize
                );
                $aFileMeta = array(
                    'sKey' => $sFileKey,
                    'sName' => $p_sFile,
                    'iSize' => $iSize,
                    'sMimeType' => $sMimeType,
                    'iBID' => $i_bID,
                    'iIP' => $this->ipToLong($p_sIP)
                );
            }

            // 移动文件
            if (false === Util_File::tryMoveFile($sFile, $sDestFile)) {
                $iError = self::FILE_MOVE_TMP_FILE_ERROR;
                $mReturn = "Can not move upload file";
                break;
            }

            // 存储索引
            $iHostID = $this->getDispatchHostID($sFileKey);
            $aFile = array(
                'sKey' => $sFileKey,
                'sExt' => $sExtension,
                'iHostID' => $iHostID
            );
            $iFileID = File_Model_File::addData($aFile);
            $iFileMetaID = File_Model_FileMeta::addData($aFileMeta);
            if (empty($iFileID) || empty($iFileMetaID)) {
                $iError = self::FILE_SAVE_INDEX_ERROR;
                $mReturn = 'Save file index failed.';
                break;
            }

            // 文件上传成功
            $mReturn = $o_aFileInfo;
        } while (false);

        return array(
            $iError,
            $mReturn
        );
    }


    /**
     * 保存上传的文件
     *
     * @param string $p_sName
     * @param string $p_sTmpName
     * @param int $p_iError
     * @param int $p_iSize
     * @param string $p_sIP
     * @param int $p_iTime
     * @param array $o_aFileInfo
     * @return array
     */
    public function saveFile ($p_sName, $p_sTmpName, $p_iError, $p_iSize, $p_sIP, $p_sDomain, $i_bID = File_Model_FileMeta::BID_DEFAULT)
    {
        $iError = self::FILE_SAVE_SUCCESS;

        do {
            if ($p_iError > 0) {
                // 文件上传出错
                $iError = self::FILE_UPLOAD_ERROR;
                $mReturn = $this->getUploadErrMsg($p_iError);
                break;
            }

            if (! is_uploaded_file($p_sTmpName)) {
                $iError = self::FILE_NOT_POST_UPLOAD_FILE;
                $mReturn = 'upload error, use http post to upload';
                break;
            }

            // 上传域名是否允许上传
            list ($iError, $mReturn) = $this->checkAllowedSize($p_iSize, $p_sDomain, $iError);
            if (! empty($iError)) {
                break;
            }

            $sMimeType = Util_File::getMimeType($p_sTmpName);
            if (empty($sMimeType) || $sMimeType == 'application/zip') {
                $sMimeType = Util_File::getMimeTypeByExtension($p_sName);
            }
            if (empty($sMimeType)) {
                $sMimeType = 'image/jpeg';
            }

            $sExtension = Util_File::getExtension($sMimeType);
            if ($sExtension == 'xls' || $sExtension == 'xlsx' || $sExtension == 'txt') {
                $sExtension = Util_File::getExtensionByName($p_sName);
            }

            // 上传文件类型
            list ($iError, $mReturn) = $this->checkAllowedType($sExtension, $p_sDomain, $iError);
            if (! empty($iError)) {
                break;
            }

            $sFileKey = sha1_file($p_sTmpName);
            $sDestFile = $this->getDestFile($sFileKey);

            if (!file_exists($sDestFile)) {
                $bImage = $this->isImage($sExtension);
                if ($bImage) {
                    $aImageInfo = $this->getImageInfo($p_sTmpName);
                    $o_aFileInfo = array(
                        'sKey' => $sFileKey,
                        'sExt' => $sExtension,
                        'sFile' => $sFileKey . '.' . $sExtension,
                        'iSize' => $p_iSize,
                        'iWidth' => $aImageInfo['iWidth'],
                        'iHeight' => $aImageInfo['iHeight']
                    );
                    $aFileMeta = array(
                        'sKey' => $sFileKey,
                        'sName' => $p_sName,
                        'iSize' => $p_iSize,
                        'sMimeType' => $sMimeType,
                        'iBID' => $i_bID,
                        'iIP' => $this->ipToLong($p_sIP),
                        'iWidth' => $aImageInfo['iWidth'],
                        'iHeight' => $aImageInfo['iHeight']
                    );
                } else {
                    $o_aFileInfo = array(
                        'sKey' => $sFileKey,
                        'sExt' => $sExtension,
                        'sFile' => $sFileKey . '.' . $sExtension,
                        'iSize' => $p_iSize
                    );
                    $aFileMeta = array(
                        'sKey' => $sFileKey,
                        'sName' => $p_sName,
                        'iSize' => $p_iSize,
                        'sMimeType' => $sMimeType,
                        'iBID' => $i_bID,
                        'iIP' => $this->ipToLong($p_sIP)
                    );
                }

                // 移动文件
                if (false === move_uploaded_file($p_sTmpName, $sDestFile)) {
                    $iError = self::FILE_MOVE_TMP_FILE_ERROR;
                    $mReturn = "Can not move upload file";
                    break;
                }
            }

            $aFileInfo = File_Model_File::getFileByKey($sFileKey);
            if (! empty($aFileInfo)) {
                $iError = self::FILE_SAVE_SUCCESS;
                $aFileMeta = File_Model_FileMeta::getFileMetaByKey($sFileKey);
                if (! empty($aFileMeta)) {
                    $aFileMet['iBID'] = $aFileMeta['iBID'] | $i_bID;
                    File_Model_FileMeta::updData(array(
                        'iBID' => $aFileMet['iBID'],
                        'iAutoID' => $aFileMeta['iAutoID']
                    ));
                }
                $mReturn = $this->normalizeFileInfo($aFileInfo, $aFileMeta);
                break;
            }

            // 存储索引
            $iHostID = $this->getDispatchHostID($sFileKey);
            $aFile = array(
                'sKey' => $sFileKey,
                'sExt' => $sExtension,
                'iHostID' => $iHostID
            );
            $iFileID = File_Model_File::addData($aFile);
            $iFileMetaID = File_Model_FileMeta::addData($aFileMeta);
            if (empty($iFileID) || empty($iFileMetaID)) {
                $iError = self::FILE_SAVE_INDEX_ERROR;
                $mReturn = 'Save file index failed.';
                break;
            }

            // 文件上传成功
            $mReturn = $o_aFileInfo;
        } while (false);

        return array(
            $iError,
            $mReturn
        );
    }

    public function checkAllowedSize ($p_iSize, $p_sDomain, $iError, $mReturn = '')
    {
        do {
            // 检查上传文件大小是否在限制范围内
            $aDomainAllowedSize = $this->getAllowedSize($p_sDomain);

            if (empty($aDomainAllowedSize)) {
                $iError = self::FILE_SIZE_LIMITED_ERROR;
                $mReturn = 'The upload size of domain is configured.';
                break;
            }

            if ($p_iSize < $aDomainAllowedSize['iMin']) {
                $iError = self::FILE_SIZE_LIMITED_ERROR;
                $mReturn = 'The upload file size is too small.';
                break;
            }

            if ($p_iSize > $aDomainAllowedSize['iMax']) {
                $iError = self::FILE_SIZE_LIMITED_ERROR;
                $mReturn = 'The upload file size is too large.';
                break;
            }
        } while (false);

        return array(
            $iError,
            $mReturn
        );
    }

    public function getAllowedSize ($p_sDomain)
    {
        return Yaf_G::getConf('aAllowedSize', 'file', 'file');
    }

    public function checkAllowedType ($sExtension, $p_sDomain, $iError, $mReturn = '')
    {
        do {
            // 检查文件格式
            if ('dat' == $sExtension) {
                // 未知文件类型
                $iError = self::FILE_MIME_TYPE_ERROR;
                $mReturn = 'Unknown file mime type';
                break;
            }

            $aDomainAllowedExtension = $this->getAllowedTypes($p_sDomain);

            // 上传域名文件扩展名配置
            if (empty($aDomainAllowedExtension)) {
                $iError = self::FILE_MIME_TYPE_ERROR;
                $mReturn = "The file extension($sExtension) of current domain is not configured.";
                break;
            }

            if (! in_array($sExtension, $aDomainAllowedExtension)) {
                $iError = self::FILE_MIME_TYPE_ERROR;
                $mReturn = "The file extension($sExtension) of current domain is forbidden.";
                break;
            }
        } while (false);

        return array(
            $iError,
            $mReturn
        );
    }

    public function getAllowedTypes ($p_sDomain)
    {
        return Yaf_G::getConf('aAllowedType', 'file', 'file');
    }

    /**
     * 取得原文件路径
     * @param unknown $p_sKey
     * @return string
     */
    public function getOriginalPath($p_sKey)
    {
        $aFileInfo = $this->getFileInfo($p_sKey);
        $sFilePath = $this->getDestFile($p_sKey);

        return $sFilePath;
    }

    /**
     * 取得原文件内容
     * @param unknown $p_sKey
     * @param unknown $p_sExt
     * @return Ambigous <string/false, boolean, string>
     */
    public function getOriginalFile($p_sKey)
    {
        $sFilePath = $this->getOriginalPath($p_sKey);
        return Util_File::tryReadFile($sFilePath);
    }

    /**
     *
     * @param
     *            $p_sKey
     * @param
     *            $p_sExt
     * @param
     *            $p_oFileInfo
     * @param
     *            $p_sDomain
     * @param int $p_iWidth
     * @param int $p_iHeight
     * @return int
     */
    public function getFile ($p_sKey, $p_sExt, &$p_oFileInfo, $p_sDomain, $p_iWidth = 0, $p_iHeight = 0, $p_bCrop = false, $p_iWaterMarkPosition = 0, $p_iWaterMarkPath = 0, $p_isDown = false, $p_sBid = File_Model_FileMeta::BID_DEFAULT, $bFixedWater = false, $bImageQuality=0)
    {
        $iError = 0;

        do {
            $aFileInfo = $this->getFileInfo($p_sKey);
//             $iArea = $aFileInfo['iWidth'] * $aFileInfo['iHeight'];
//             if ($iArea > 1800000) {
//                 $p_iWidth = max($aFileInfo['iWidth'] * 1800000 / $iArea, $p_iWidth);
//                 $p_iHeight = max($aFileInfo['iHeight'] * 1800000 / $iArea, $p_iHeight);
//             }
//             echo $iArea, "|", $p_iWidth, "|", $p_iHeight, "\n";exit;
            $bWaterMark = true;

            if (File_Model_FileMeta::BID_BANNER == ($aFileInfo['iBID'] & $p_sBid)) {
                $bWaterMark = false;
            }
            if (! $aFileInfo) {
                $iError = self::FILE_NOT_EXISTS;
                break;
            }
            if ($aFileInfo['sExt'] != $p_sExt) {
                $iError = self::FILE_EXT_INVAILD;
                break;
            }

            $p_sMimeType = $aFileInfo['sMimeType'];
            if ($p_isDown) {
                $aDomainViewType = $this->getAllowedDownloadType($p_sDomain);
            } else {
                $aDomainViewType = $this->getAllowedViewType($p_sDomain);
            }

            if (! in_array($p_sExt, $aDomainViewType)) {
                $iError = self::FILE_MIMETYPE_NOT_ALLOWED;
                break;
            }

            $sFilePath = $this->getDestFile($p_sKey);
            $sFileContent = Util_File::tryReadFile($sFilePath);
            if (false === $sFileContent) {
                $iError = self::FILE_NOT_EXISTS;
                break;
            }

            if ($this->isImage($p_sExt)) {

                $utilImg = Util_ImageFactory::instance('gd');

                $aDomainDimension = $this->getAllowedImageDimension($p_sDomain);
                // 针对图片操作
                $imgResource = imagecreatefromstring($sFileContent);

                if (! $imgResource) {
                    $iError = self::FILE_NOT_EXISTS;
                    break;
                }

                /*
                $sDimensionKey = $p_iWidth . 'x' . $p_iHeight;
                if ($p_iWidth > 0 && $p_iHeight > 0 && ! array_key_exists($sDimensionKey, $aDomainDimension)) {
                    $iError = self::FILE_IMAGE_DIMENSION_NOT_ALLOWED;
                    break;
                }
                */

                $sFileContent = $this->resizeImage($imgResource, $p_iWidth, $p_iHeight, $aDomainDimension, $p_sMimeType, $p_bCrop, $bWaterMark, $p_iWaterMarkPosition, $p_iWaterMarkPath, $bFixedWater, $bImageQuality);
                $p_sETag = $utilImg->buildEtagFromImg($sFileContent);
                $aFileInfo['iSize'] = sizeof($sFileContent);
            } else {
                $p_sETag = $p_sKey;
            }

            $p_oFileInfo = [
                'sContent' => $sFileContent,
                'sETag' => $p_sETag,
                'sExt' => $aFileInfo['sExt'],
                'sMimeType' => $p_sMimeType,
                'iSize' => $aFileInfo['iSize'],
                'sName' => substr($aFileInfo['sName'], 0, strrpos($aFileInfo['sName'], '.'))
            ];

            $iError = true;
        } while (false);

        return $iError;
    }

    public function getAllowedDownloadType ($p_sDomain)
    {
        return Yaf_G::getConf('aAllowedDownloadType', 'file', 'file');
    }

    /**
     * 检查使用方是否是允许的域名
     *
     * @param string $p_sReferer
     * @return true/string
     */
    public function isAllowedDomain ($p_sReferer)
    {
        $sDomain = Util_Uri::getDomain($p_sReferer);
        $aAllowedDomain = Yaf_G::getConf('aAllowedDomain', 'file', 'file');

        foreach ($aAllowedDomain as $sDomainPattern) {
            if (1 === preg_match($sDomainPattern, $sDomain)) {
                return true;
                break;
            }
        }

        return false;
    }

    /**
     * 直接存储文件
     */
    public function directSaveFile($p_sFileKey, $p_sContent)
    {
        $sDestFile = $this->getDestFile($p_sFileKey);
        if (file_put_contents($sDestFile, $p_sContent) > 0) {
            return $sDestFile;
        }

        return false;
    }

    /**
     * 获取文件存储位置
     *
     * @param
     *            $p_sFileKey
     * @return string
     */
    public function getDestFile ($p_sFileKey)
    {
        if (strpos($p_sFileKey, '.') !== false) {
            $p_sFileKey = strstr($p_sFileKey, '.', true);
        }
        $sFileRawDir = Yaf_G::getConf('sStorageDir', 'file', 'file');
        $iHostID = $this->getDispatchHostID($p_sFileKey);
        $sFilePath = $this->dispatchFilePath($p_sFileKey);
        $sDestFile = sprintf('%s%s%s%s%s%s', $sFileRawDir, DIRECTORY_SEPARATOR, $iHostID, DIRECTORY_SEPARATOR, $sFilePath, $p_sFileKey);

        return $sDestFile;
    }

    /**
     * 获取存储设备ID
     *
     * @param
     *            $p_sFileKey
     * @return int
     */
    public function getDispatchHostID ($p_sFileKey)
    {
        $aStorageHost = (array)Yaf_G::getConf('aStorageHost', 'file', 'file');
        $iHostID = $this->dispatchHostID($aStorageHost, $p_sFileKey);

        return $iHostID;
    }

    /**
     *
     * @param
     *            $p_sDomain
     * @return array
     */
    public function getAllowedImageDimension ($p_sDomain)
    {
        return Yaf_G::getConf('aDimension', 'image', 'file');
    }

    /**
     *
     * @param
     *            $p_sDomain
     * @return array
     */
    public function getAllowedViewType ($p_sDomain)
    {
        return Yaf_G::getConf('aAllowedViewType', 'file', 'file');
    }

    /**
     *
     * @param
     *            $p_sKey
     * @return array
     */
    public function getFileInfo ($p_sKey)
    {
        $mReturn = [];
        $aFileInfo = File_Model_File::getFileByKey($p_sKey);
        if (! empty($aFileInfo)) {
            $aFileMeta = File_Model_FileMeta::getFileMetaByKey($p_sKey);
            $mReturn = array_merge($aFileInfo, $aFileMeta);
        }

        return $mReturn;
    }

    /**
     * 检查是否为图片
     *
     * @param
     *            $p_sExtension
     */
    protected function isImage ($p_sExtension)
    {
        $aImageType = Yaf_G::getConf('aImageType', 'file', 'file');
        ;
        $bImage = in_array($p_sExtension, $aImageType) ? true : false;

        return $bImage;
    }

    /**
     * 给文件分配路径
     *
     * @param string $p_sFileKey
     * @return string
     */
    protected function dispatchFilePath ($p_sFileKey)
    {
        $sSubPath = '';

        for ($i = 1; $i <= 3; $i ++) {
            $sSubPath = sprintf('%s%s%s', $sSubPath, $p_sFileKey[$i], DIRECTORY_SEPARATOR);
        }

        return $sSubPath;
    }

    /**
     * 分配存储设备
     *
     * @param array $p_aHostCfg
     * @return int
     */
    protected function dispatchHostID ($p_aStorageHost, $p_sFileKey)
    {
        $sHostKey = $p_sFileKey[0];
        $iHostID = 1;
        foreach ($p_aStorageHost as $p_aHost) {
            if (isset($p_aHost['sRouteKeys']) && in_array($sHostKey, $p_aHost['sRouteKeys'])) {
                $iHostID = $p_aHost['iHostID'];
                break;
            }
        }

        return $iHostID;
    }

    /**
     * 标准化返回信息
     *
     * @param
     *            $p_aFileMeta
     */
    protected function normalizeFileInfo ($p_aFile, $p_aFileMeta)
    {
        $aFileInfo = array();
        $aFileInfo['sKey'] = isset($p_aFile['sKey']) ? $p_aFile['sKey'] : '';
        $aFileInfo['sExt'] = isset($p_aFile['sExt']) ? $p_aFile['sExt'] : '';
        $aFileInfo['iSize'] = isset($p_aFileMeta['iSize']) ? $p_aFileMeta['iSize'] : 0;
        $aFileInfo['sFile'] = $aFileInfo['sKey'] . '.' . $aFileInfo['sExt'];

        if ($this->isImage($aFileInfo['sExt'])) {
            $aFileInfo['iWidth'] = isset($p_aFileMeta['iWidth']) ? $p_aFileMeta['iWidth'] : 0;
            $aFileInfo['iHeight'] = isset($p_aFileMeta['iHeight']) ? $p_aFileMeta['iHeight'] : 0;
        }

        return $aFileInfo;
    }

    protected function resizeImage ($p_sFileContent, $p_iWidth, $p_iHeight, $p_aDomainDimension, $p_sMimeType, $p_bCrop, $p_bIsWaterMark = true, $p_iWaterMarkPosition = 0, $p_iWaterMarkPath = 0, $bFixedWater, $bImageQuality)
    {
        $utilImg = Util_ImageFactory::instance('gd');
        if ($p_iWidth > 0 && $p_iHeight > 0) {
            $sDimensionKey = $p_iWidth . 'x' . $p_iHeight;
            if (isset($p_aDomainDimension[$sDimensionKey])) {
                $aDimensionConf = $p_aDomainDimension[$sDimensionKey];
            } else {
                $aDimensionConf = array();
            }
            $p_bIsWaterMark = isset($aDimensionConf['waterMark']) ? $aDimensionConf['waterMark'] : true;

            $sWaterMarkPath = '';
            if ($p_iWaterMarkPath > 0) {
                $aWaterMarkPath = Util_Common::getConf('aWaterMarkPath', 'image', 'file');
                if (isset($aWaterMarkPath[$p_iWaterMarkPath])) {
                    $sWaterMarkPath = $aWaterMarkPath[$p_iWaterMarkPath];
                }
            }
            if (empty($sWaterMarkPath) && isset($aDimensionConf['waterMarkPath'])) {
                $sWaterMarkPath = $aDimensionConf['waterMarkPath'];
            }

            if (! empty($sWaterMarkPath)) {
                // 设置水印的图片地址
                $utilImg->setWaterMarkImg($sWaterMarkPath);
            }

            $sWaterMarkPosition = '';
            if ($p_iWaterMarkPosition > 0) {
                $aWaterMarkPosition = Util_Common::getConf('aWaterMarkPosition', 'image', 'file');
                if (isset($aWaterMarkPosition[$p_iWaterMarkPosition])) {
                    $sWaterMarkPosition = $aWaterMarkPosition[$p_iWaterMarkPosition];
                }
            }

            if (! empty($sWaterMarkPosition)) {
                // 设置水印的图片位置
                $utilImg->setWaterMarkPosition($sWaterMarkPosition);
            }
        }

        // 图片操作
        $p_sFileContent = $utilImg->resize($p_sFileContent, $p_iWidth, $p_iHeight, $p_bCrop, $p_bIsWaterMark, $bFixedWater);
        $iImageQuality = $bImageQuality==1?70:100;

        return $utilImg->getImageBlob($p_sFileContent, $p_sMimeType, $iImageQuality);
    }

    /**
     * 得到图片的扩展信息
     *
     * @param string $p_sTmpName
     * @return array
     */
    protected function getImageInfo ($p_sTmpName)
    {
        $aImageInfo = getimagesize($p_sTmpName);

        return array(
            'iWidth' => $aImageInfo[0],
            'iHeight' => $aImageInfo[1],
            'iChannels' => isset($aImageInfo['channels']) ? $aImageInfo['channels'] : '0',
            'iBits' => isset($aImageInfo['bits']) ? $aImageInfo['bits'] : '0'
        );
    }

    /**
     * 获取文件上传错误信息
     *
     * @param
     *            $p_iError
     * @return string
     */
    protected function getUploadErrMsg ($p_iError)
    {
        switch ($p_iError) {
            case 0:
                $sErrMsg = 'The file uploaded with success';
                break;
            case 1:
                $sErrMsg = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case 2:
                $sErrMsg = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                break;
            case 3:
                $sErrMsg = 'The uploaded file was only partially uploaded';
                break;
            case 4:
                $sErrMsg = 'No file was uploaded';
                break;
            case 6:
                $sErrMsg = 'Missing a temporary folder';
                break;
            case 7:
                $sErrMsg = 'Failed to write file to disk';
                break;
            case 8:
                $sErrMsg = 'A PHP extension stopped the file upload';
                break;
            default:
                $sErrMsg = 'Unkown error';
        }

        return $sErrMsg;
    }

    /**
     * IP 地址转换称无符号整数类型，ip2long 转成二进位, 再转回十进位避免负值
     *
     * @param
     *            $p_sIP
     */
    protected function ipToLong ($p_sIP)
    {
        return bindec(decbin(ip2long($p_sIP)));
    }
}
