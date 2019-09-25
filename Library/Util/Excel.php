<?php

/**
 * Excel处理相关
 */
class Util_Excel
{

    private $sFile;

    private $oPHPExcel;

    private $oReader;

    private $oSheet;

    private $iRowNum;

    private $iColNum;

    private $iCurrRow;

    private $sType;

    private $aType = array(
        'xls' => 'Excel5',
        'xlsx' => 'Excel2007',
        'csv' => 'Csv'
    );

    public function load ($sFile, $sExt)
    {
        $sExt = strtolower($sExt);
        $sType = isset($this->aType[$sExt]) ? $this->aType[$sExt] : 'Excel5';

        $this->sType = $sType;
        $this->sFile = $sFile;
        if ($sType == 'Csv') {
            $this->oReader = fopen($sFile, "r");
        } else {
            $this->oReader = PHPExcel_IOFactory::createReader($sType);
            $this->oReader->setReadDataOnly(true);
            $this->oPHPExcel = $this->oReader->load($sFile);
            $this->oSheet = $this->oPHPExcel->getActiveSheet();
            $this->iRowNum = $this->oSheet->getHighestRow();
            $sHighestColumn = $this->oSheet->getHighestColumn();
            $this->iColNum = PHPExcel_Cell::columnIndexFromString($sHighestColumn);
        }
        $this->iCurrRow = 1;
    }

    /**
     * 查找Excel的下一行
     *
     * @return array
     */
    public function getNextRow ($aKV = null)
    {
        if ($this->sType == 'Csv') {
            return $this->getCsvNextRow($aKV);
        } else {
            return $this->getExcelNextRow($aKV);
        }
    }

    public function getCsvNextRow ($aKV)
    {
        $aRow = array();
        while (($aData = fgetcsv($this->oReader, 4096, ",")) !== FALSE) {
            $aRow = array();
            $sFind = false;
            for ($iCol = 0; $iCol < count($aData); $iCol ++) {
                $sVal = trim($aData[$iCol]);
                $sVal = Util_Tools::toSCB($sVal);
                $sVal = trim($sVal);
                //$sVal = preg_replace('/\s/', '', $sVal);
                if ($aKV == null) {
                    $aRow[] = $sVal;
                } elseif (isset($aKV[$iCol])) {
                    $aRow[$aKV[$iCol]] = $sVal;
                }
                
                if (! empty($sVal)) {
                    $sFind = true;
                }
            }
            
            if ($sFind) {
                break;
            }
        }
        
        return $aRow;
    }

    public function getExcelNextRow ($aKV)
    {
        $aRow = array();
        for (; $this->iCurrRow <= $this->iRowNum; $this->iCurrRow ++) {
            $aRow = array();
            $sFind = false;
            for ($iCol = 0; $iCol < $this->iColNum; $iCol ++) {
                $sVal = trim($this->oSheet->getCellByColumnAndRow($iCol, $this->iCurrRow)->getValue());
                $sVal = Util_Tools::toSCB($sVal);
                $sVal = preg_replace('/\s/', '', $sVal);
                if ($aKV == null) {
                    $aRow[] = $sVal;
                } elseif (isset($aKV[$iCol])) {
                    $aRow[$aKV[$iCol]] = $sVal;
                }
                
                if (! empty($sVal)) {
                    $sFind = true;
                }
            }
            
            if ($sFind) {
                break;
            }
        }
        
        $this->iCurrRow ++;
        
        return $aRow;
    }

    /**
     * 转换成日期
     * 
     * @param int $iDate
     * @return string
     */
    public static function toDate ($iDate)
    {
        $iDate = $iDate > 25568 ? $iDate + 1 : 25569;
        /* There was a bug if Converting date before 1-1-1970 (tstamp 0) */
        $iOffset = (70 * 365 + 17 + 3) * 86400;
        return date("Y-m-d", ($iDate * 86400) - $iOffset);
    }

    /**
     * 转换成整型
     * 
     * @param string $sVal
     * @return mixed
     */
    public static function toInt ($sVal)
    {
        return (int) preg_replace('@^.*?(\d+).*$@', '$1', $sVal);
    }

    /**
     * 转换成Float
     * 
     * @param string $sVal
     * @return number
     */
    public static function toFloat ($sVal)
    {
        return (int) preg_replace('@^.*?([\d\.]+).*$@', '$1', $sVal);
    }

    /**
     * 从浏览器下载excel
     *
     * @param array aTitle Excel第一行标题 $aTitle=['title1','title2']
     * @param array aData 数组数据 二维数组 $aData = [ ['id'=>"332","name"=>"name1"],['id'=>"333","name"=>"name2"]]
     * @param string sFilename 导出excel文件名
     * @param string $sheetName sheet名
     * @return void
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public static function downloadExcel($aTitle, $aData, $sFilename = "export", $sheetName = "sheet1")
    {
        //校验
//        if(empty($aTitle) && empty($aData))  return;
        //初始化
        $objPHPExcel=new PHPExcel();
        $objSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objSheet->setTitle($sheetName);
        //写标题头
        $aTitle = array_values($aTitle);
        for ($i = 0; $i < count($aTitle); $i++){
            $colIndex = PHPExcel_Cell::stringFromColumnIndex($i)."1";//A1,B1
            $objSheet->setCellValue($colIndex,$aTitle[$i]);
        }
        //写数据
        for ($i = 0; $i < count($aData); $i++){
            if(!is_array($aData[$i])) continue;
            $aRow = array_values($aData[$i]);
            for ($j = 0; $j < count($aRow); $j++) {
                $columnIndex = PHPExcel_Cell::stringFromColumnIndex($j) . ($i + 2);
                $objSheet->setCellValue($columnIndex, $aRow[$j]);
            }
        }
        //强制下载
        header('Content-Type: application/force-download');
        // header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . $sFilename . ".xlsx");
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save("php://output");
        exit;
    }

    /**
     * 一次性读取excel文件
     *
     * @param string $filename 文件路径
     * @param int $sheetIndex 第几个sheet
     * @return array $aData
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     */
    public static function readExcelFile($filename,$sheetIndex=0){
        $fileArr = explode('.',$filename);
        $exp = end($fileArr);
        if($exp == 'xlsx')
            $objReader=PHPExcel_IOFactory::createReader('Excel2007');
        else
            $objReader=PHPExcel_IOFactory::createReader('Excel5');

        //读文件
        $objPHPExcel=$objReader->load($filename);
        $sheet=$objPHPExcel->getSheet($sheetIndex);
        $highestRow=$sheet->getHighestRow();//取得总行数
        $highestColumn=$sheet->getHighestColumn(); //取得总列数 (A,B,C)
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
        //开始读取数据
        $data = [];
        for($j=1;$j<=$highestRow;$j++)
        {
            for ($col = 0; $col < $highestColumnIndex; $col++)
            {
                $rows[] =(string)$sheet->getCellByColumnAndRow($col, $j)->getValue();
            }
            $data[] = $rows;
            unset($rows);
        }
        return $data;
    }
    public static function exportExcel($aTitle,$aData,$sSheet="sheet1") {

        header('Content-Type: application/vnd.ms-excel');
        //header('Content-Disposition: attachment;filename="'.$sFileName.'.xlsx"');
        foreach($aTitle as $sV) {
            $header1[$sV] = 'string';
        }
        $writer = new Util_XLSXWriter();
        $writer->writeSheetHeader($sSheet, $header1);
        foreach ($aData as $aItem) {
            $data = array();
            foreach($aItem as $sV) {
                $data[] = $sV;
            }
            $writer->writeSheetRow($sSheet, $data);
        }
        $writer->writeToStdOut();
        exit();
    }

    /**
     * 下载excel
     *
     * @param array aTitle Excel 第一行标题
     * @param array aData 数组数据 二维数组 $aData = [ ['id'=>"332","name"=>"name1"],['id'=>"333","name"=>"name2"]]
     * @param array $opts 选项 ['fileName' => '下载文件名, 默认export', '工作表名, 默认sheetName' => 'Sheet1', 'assoc' => false, 'download' => false]
     * @return void
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public static function outputExcel($aTitle, $aData, $opts = ['fileName' => 'export', 'sheetName' => 'Sheet1', 'assoc' => false, 'download' => false])
    {
        // 文件名
        $fileName = empty($opts['fileName']) ? 'export' : $opts['fileName'];
        // 工作表名
        $sheetName = empty($opts['sheetName']) ? 'Sheet1' : $opts['sheetName'];
        // 传入的表头是否是关联数组
        $assoc = isset($opts['assoc']) ? $opts['assoc'] : false;
        // 是否直接浏览器下载
        $download = isset($opts['download']) ? $opts['download'] : false;

        // 初始化
        $objPHPExcel = new PHPExcel();
        $objSheet = $objPHPExcel->setActiveSheetIndex(0);
        $objSheet->setTitle($sheetName);
        if ($assoc) {
            // 写标题头
            // 表头数据格式 [ ['prop'=>'field1', 'label'=>'字段名1'], ['prop'=>'field2', 'label'=>'字段名2'] ]
            $aTitleName = array_column($aTitle, 'label');
            for ($i = 0; $i < count($aTitleName); $i++) {
                $colIndex = PHPExcel_Cell::stringFromColumnIndex($i) . '1'; // A1,B1
                $objSheet->setCellValue($colIndex, $aTitleName[$i]);
            }
            // 写数据
            // [ ['field1'=>'field1的值', 'field2'=>'field2的值', 'field3'=>'不在aTitle中的列不会输出'], ['field2'=>'field2的值', 'field1'=>'field1的值'] ]
            $iRow = 2; // 表头占了第一行, 所以从第二行开始写表格体数据
            foreach ($aData as $k => $aRow) {
                if (!is_array($aRow)) {
                    continue;
                }
                foreach ($aTitle as $iCol => $_aTitle) {
                    if (!isset($_aTitle['prop'])) {
                        continue;
                    }
                    $_field = $_aTitle['prop'];
                    $cellValue = isset($aRow[$_field]) ? $aRow[$_field] : '';
                    $columnIndex = PHPExcel_Cell::stringFromColumnIndex($iCol) . ($iRow);
                    $objSheet->setCellValue($columnIndex, $cellValue);
                }
                $iRow++;
            }
        } else {
            // 写标题头
            // 表头数据格式 ['title1', 'title2', 'title3']
            $aTitle = array_values($aTitle);
            for ($i = 0; $i < count($aTitle); $i++) {
                $colIndex = PHPExcel_Cell::stringFromColumnIndex($i) . "1"; // A1,B1
                $objSheet->setCellValue($colIndex, $aTitle[$i]);
            }
            // 写数据
            // [ ['field1'=>'field1的值', 'field2'=>'field2的值'], ['field1'=>'field1的值', 'field2'=>'field2的值'] ]
            $i = 0;
            foreach ($aData as $k => $item) {
                if (!is_array($item)) {
                    $i++;
                    continue;
                }
                $aRow = array_values($item);
                for ($j = 0; $j < count($aRow); $j++) {
                    $columnIndex = PHPExcel_Cell::stringFromColumnIndex($j) . ($i + 2);
                    $objSheet->setCellValue($columnIndex, $aRow[$j]);
                }
                $i++;
            }
        }

        if ($download) {
            // 强制下载
            header('Content-Type: application/force-download');
            header('Content-Disposition: attachment;filename=' . $fileName . ".xlsx");
        } else {
            header('Content-Type: application/vnd.ms-excel');
        }
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save("php://output");
        exit;
    }

}