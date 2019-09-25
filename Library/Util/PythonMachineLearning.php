<?php

/**
 * Python机器学习算法调用
 * created on 2018-08-08
 * created by xucaixia
 */
Class Util_PythonMachineLearning {

    private $sAlgFunc = null;
    private $aOutputResult = array();
    private $aSupportAlg = ['gene', 'effect', 'borda', 'pca', 'cluster'];
    private $sPythonCmd = 'python';
    private $sTempFileName = '/tmp/data_tmp_%s.csv';
    private $sPythonAlgFile = null;

    /**
     *  构造函数
     */
    public function __construct() {
        
    }

    /**
     *  判断是否支持此算法
     */
    public function checkSupportAlgFunc($sAlgFunc) {
        if (in_array($sAlgFunc, $this->aSupportAlg)) {
            $this->sAlgFunc        = $sAlgFunc;
            $this->sPythonAlgFile  = sprintf('%s/%s.py', LIB_PATH . '/Python', $sAlgFunc);
        } else {
            throw new Exception("暂不支持此算法", 1);
        }
    }

    /**
     *  获取算法结果
     *  @param sAlgFunc			stirng 	算法名称  gene(因子算法)  effect(功效系数法)  borda(borda综合算法)
     *  @param sInputJsonData	json 	数据json串
     *  @param iShowTitleCount 	int     左列显示名称个数
     *  @param bHasFieldName	bool 	头行是否为字段名
     *  @param aParamConfig     array   算法参数配置
     *  @return array
     */
    public function getResult($sAlgFunc, $sInputJsonData, $iShowTitleCount = 1, $bHasFieldName = false, $aParamConfig = array(), $bDirectReturn = false) {
        $this->checkSupportAlgFunc($sAlgFunc);

        //S1、处理运算数据
        $aInputData = json_decode($sInputJsonData);
        $aInputAlgData = array();
        if ($bHasFieldName) {
            array_shift($aInputData);
            //TODO effect算法时需要用到
        }
        foreach ($aInputData as $key => $value) {
            //如果第一行是字段行，则去掉此行

            if (!empty($value)) {
                //从第几列开始为运算数据
                $value = array_slice($value, $iShowTitleCount);
                $aInputAlgData[] = join(',', $value);
            }
        }
        //S2、获取运算结果		
        $aAlgResult = $this->execPythonAlg($aInputAlgData, $aParamConfig);
        if ($bDirectReturn) {
            return $aAlgResult;
        } else {
            //S3、拼接数据输出
            $aResult = array();
            if (!empty($aAlgResult)) {
                foreach ($aInputData as $x => $y) {
                    $aResult[] = array_merge(array_slice($y, 0, $iShowTitleCount), array($aAlgResult[$x]));
                }
            }
            return $aResult;
        }
        
    }

    /**
     *  调用python获得单个算法结果
     *  @param aInputData		array 	输入数据
     *  @param aParamConfig		array 	输入配置
     *  @return aOutputResult 	array 	输出结果
     */
    public function execPythonAlg($aInputData, $aParamConfig) {
        //TODO添加预警，如果有报错时如何提醒
        //1、将数据写入临时文本中
        $this->sTempFileName = sprintf($this->sTempFileName, time() . rand(0, 1000));
        file_put_contents($this->sTempFileName, join("\r\n", $aInputData));
        //2、调用具体算法返回运行结果
        exec(sprintf('%s %s %s %s', $this->sPythonCmd, $this->sPythonAlgFile, $this->sTempFileName, join(',', $aParamConfig)), $aOutputResult);
        return $aOutputResult;
    }

}

?>