<?php
/**
 * Created by PhpStorm.
 * User: xiejinci
 * Date: 14/12/24
 * Time: 上午9:49
 */

class Util_ImageMagick
{
    /**
     * @var string
     * 水印图片的地址
     */
    protected $_sWaterMarkImgPath;

    /**
     * @var array
     * 需要水印的尺寸
     * 最小打水印尺寸为241X241
     */
    protected $_aNeedWaterMarkSize = array(
        'iWidth'  => 241,
        'iHeight' => 241
    );

    /**
     * @var array
     * 水印的位置
     */
    protected $_sWaterMarkPosition = 'bottom-right';

    private $oImage = null;

    public function __construct()
    {
        $this->_sWaterMarkImgPath = Util_Common::getConf('defaultWaterMarkPath', 'image');
        $this->_aNeedWaterMarkSize = Util_Common::getConf('aNeedWaterMarkSize', 'image', 'file');
    }

    public function __destruct()
    {
        if (!empty($this->oImage)) {
            $this->oImage->clear();
            $this->oImage->destroy();
        }
    }

    public function setFile($sFile)
    {
        $this->oImage = new Imagick($sFile);
    }

    /**
     * @param $p_sPosition
     */
    public function setWaterMarkPosition($p_sPosition)
    {
        $this->_sWaterMarkPosition = $p_sPosition;
    }
    /**
     * @param $p_sWaterImg
     */
    public function setWaterMarkImg($p_sWaterImg)
    {
        $this->_sWaterMarkImgPath = $p_sWaterImg;
    }

    /**
     * @return string
     */
    public function getWaterMarkImg()
    {
        return $this->_sWaterMarkImgPath;
    }

    /**
     * @param $p_iWidth
     * @param $p_iHeight
     */
    public function setWaterMarkSize($p_iWidth, $p_iHeight)
    {
        $this->_aNeedWaterMarkSize['iWidth']  = $p_iWidth;
        $this->_aNeedWaterMarkSize['iHeight'] = $p_iHeight;
    }

    /**
     * @return array
     */
    public function getWaterMarkSize()
    {
        return $this->_aNeedWaterMarkSize;
    }

    /**
     * 检测图片是否需要打水印
     */
    protected function chkWaterMark()
    {
        $iWidth  = $this->oImage->getimagewidth();
        $iHeight = $this->oImage->getimageheight();
        if ($this->_aNeedWaterMarkSize['bNeed'] && $iWidth > $this->_aNeedWaterMarkSize['iWidth'] && $iHeight > $this->_aNeedWaterMarkSize['iHeight']) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function waterMark()
    {
        $oWaterMarkImg    = new Imagick($this->_sWaterMarkImgPath);
        $iWaterMarkWidth  = $oWaterMarkImg->getimagewidth();
        $iWaterMarkHeight = $oWaterMarkImg->getimageheight();

        $iImgWidth  = $this->oImage->getimagewidth();
        $iImgHeight = $this->oImage->getimageheight();
        //图片大小小于水印大小加上30 就不打水印了
        if ($iImgWidth < $iWaterMarkWidth + 30 || $iImgHeight < $iWaterMarkHeight + 30) {
            return true;
        }

        switch($this->_sWaterMarkPosition) {
            //左下
            case "bottom-left":
                $iDesX = 30;
                $iDesY = $iImgHeight - $iWaterMarkHeight - 30;
                break;
            //右下
            case "bottom-right":
                $iDesX = $iImgWidth - $iWaterMarkWidth - 30;
                $iDesY = $iImgHeight - $iWaterMarkHeight - 30;
                break;
            //中下
            case "bottom-middle":
                $iDesX = ($iImgHeight / 2) - 15;
                $iDesY = $iImgHeight - $iWaterMarkHeight - 30;
                break;
            //左上
            case "top-left":
                $iDesX = 30;
                $iDesY = 30;
                break;
                break;
            //中上
            case "top-middle":
                $iDesX = ($iImgHeight / 2) - 15;
                $iDesY = 30;
                break;
                break;
            //右上
            case "top-right":
                $iDesX = $iImgWidth - $iWaterMarkWidth - 30;
                $iDesY = 30;
                break;
                break;
            //默认右下
            default:
                $iDesX = $iImgWidth - $iWaterMarkWidth - 30;
                $iDesY = $iImgHeight - $iWaterMarkHeight - 30;
                break;
        }

        if ($this->oImage->getimageformat() == 'GIF') {
            $aFrame = $this->oImage->coalesceImages();
            $draw = new ImagickDraw();
            $draw->composite($oWaterMarkImg->getImageCompose(), $iDesX, $iDesY, $iWaterMarkWidth, $iWaterMarkHeight, $oWaterMarkImg);
            foreach ($aFrame as $frame) {
                $frame->drawImage($draw);
            }
            $aFrame->optimizeimagelayers();
            $this->oImage = $aFrame;
        } else {
            $draw = new ImagickDraw();
            $draw->composite($oWaterMarkImg->getImageCompose(), $iDesX, $iDesY, $iWaterMarkWidth, $iWaterMarkHeight, $oWaterMarkImg);
            $this->oImage->drawImage($draw);
        }
    }

    /**
     * @param $p_iWidth
     * @param $p_iHeight
     * @param bool $p_bCrop 是否裁剪 默认裁剪
     * @return resource
     */
    public function resize($p_iWidth, $p_iHeight, $p_bCrop = true, $p_WaterMark = true, $bFixedWater = false)
    {
        $iOriginalWidth  = $this->oImage->getImageWidth();
        $iOriginalHeight = $this->oImage->getImageHeight();

        $iDesX = 0;
        $iDesY = 0;

        if (0 == $p_iWidth && 0 == $p_iHeight) {
            if ($this->oImage->getimageformat() != 'GIF') {
                $this->oImage->resizeImage($iOriginalWidth, $iOriginalHeight, Imagick::FILTER_CATROM, 1, true);
            }
        } else {
            if ($p_bCrop) {
                if ($this->oImage->getimageformat() == 'GIF') {
                    $aFrame = $this->oImage->coalesceImages();
                    foreach ($aFrame as $frame) {
                        $frame->cropThumbnailImage($p_iWidth, $p_iHeight);
                    }
                    $aFrame->optimizeimagelayers();
                    $this->oImage = $aFrame;
                } else {
                    $this->oImage->cropThumbnailImage($p_iWidth, $p_iHeight);
                }
            } else {
                $fRateWidth  = doubleval($p_iWidth) / doubleval($iOriginalWidth);
                $fRateHeight = doubleval($p_iHeight) / doubleval($iOriginalHeight);
                if ($p_iWidth == 1) {
                    $p_iWidth = $fRateHeight * $iOriginalWidth;
                } else if ($p_iHeight == 1) {
                    $p_iHeight = $fRateWidth * $iOriginalHeight;
                } else if($fRateWidth > $fRateHeight) {
                    $p_iWidth = $fRateHeight * $iOriginalWidth;
                } else {
                    $p_iHeight = $fRateWidth * $iOriginalHeight;
                }
                if ($this->oImage->getimageformat() == 'GIF') {
                    $aFrame = $this->oImage->coalesceImages();
                    foreach ($aFrame as $frame) {
                        $frame->cropThumbnailImage($p_iWidth, $p_iHeight);
                    }
                    $aFrame->optimizeimagelayers();
                    $this->oImage = $aFrame;
                } else {
                    $this->oImage->thumbnailImage($p_iWidth, $p_iHeight);
                }
            }
        }

        if ($bFixedWater || $this->chkWaterMark() && $p_WaterMark) {
            $this->waterMark();
        }
    }

    /**
     * @param string $p_sType
     * @param null $p_sFileName
     */
    public function createTypeImg($p_sType = 'jpeg', $p_sFileName = null)
    {
        if (empty($p_sFileName)) {
            $p_sFileName = tempnam("/dev/shm/", "phpimg_");
        }
        $this->oImage->setimageformat($p_sType);
        $this->oImage->writeimage($p_sFileName);

        return $p_sFileName;
    }

    /**
     * @param $p_imgBlob
     * @return string
     */
    public function buildEtagFromImg()
    {
        $etag = substr(md5($this->oImage->getImageBlob()), 0, 8);
        return "\"$etag\"";
    }

    /**
     * @param string $p_sType
     * @return string
     */
    public function getImageBlob($p_sType = 'jpeg')
    {
        $this->oImage->stripimage();
        if ($p_sType == 'jpeg' || $p_sType == 'jpg') {
            $this->oImage->setimageformat($p_sType);
            $this->oImage->setImageCompression(Imagick::COMPRESSION_JPEG);
        } elseif ($p_sType == 'png') {
            $this->oImage->setimageformat($p_sType);
            $this->oImage->setImageCompression(Imagick::COMPRESSION_UNDEFINED);
        } elseif ($p_sType == 'webp') {
            $this->oImage->setimageformat($p_sType);
        } elseif ($p_sType == 'gif') {
            $sTmpfile = tempnam("/dev/shm/", "phpimg_");
            $this->oImage->writeImages($sTmpfile, true);
            $sBlob = file_get_contents($sTmpfile);
            @unlink($sTmpfile);

            return $sBlob;
        }
        
        $this->oImage->setimagecompressionquality(90);
        return $this->oImage->getImageBlob();
    }
}