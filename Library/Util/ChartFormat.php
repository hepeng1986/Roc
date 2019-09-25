<?php

/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2019/5/8
 * Time: 17:27
 */
class Util_ChartFormat
{
    /*
     * 柱状折线图
     * @param array $xData  X轴数据
     * @param array $yData  Y轴数据
     * $yData:
     [['name' => '成交面积', 'data' => [100,200,300,400,500], 'type' => 'line', 'unit' => '㎡', 'align' => 'left'],
      ['name' => '供应面积', 'data' => [100,200,300,400,500], 'type' => 'bar', 'unit' => '㎡', 'align' => 'right']
      ['name' => '供应面积', 'data' => [100,200,300,400,500], 'type' => 'bar', 'unit' => '㎡', 'align' => 'right']
    ]
     * @return array
     */
    public static function getBarLineFormat($aXData = [], $aYData = [])
    {
        $aGraph = ["chartType" => "barline", "legend" => [], "xAxis" => [], "yAxis" => [], "series" => []];
        //x轴
        $aGraph["xAxis"] = $aXData;
        //y轴
        foreach ($aYData as $ydk => $ydv) {
            $aTemp = [];
            $aTemp["name"] = $ydv["name"];
            $aTemp["type"] = $ydv["type"];
            if ($ydv["align"] == "left") {
                $aGraph["yAxis"][0]["name"] = $ydv["unit"];//设置单位，最后一个出现的左轴
                $aTemp["yAxisIndex"] = 0;
            } else {
                $aGraph["yAxis"][1]["name"] = $ydv["unit"];//最后一个出现的右轴
                $aTemp["yAxisIndex"] = 1;
            }
            $aTemp["data"] = $ydv["data"];
            $aGraph["series"][] = $aTemp;
            $aGraph["legend"][] = $ydv["name"];
        }
        return $aGraph;
    }

    /*
     * 水平柱状条形图
     * @param array $yData Y轴数据[1，2，3]
     * @param array $xData X轴数据 一般情况下数组只有一行值，堆叠的时候多行
     * $xData:
     [['name' => '成交面积', 'data' => [100,200,300,400,500],
      ['name' => '供应面积', 'data' => [100,200,300,400,500]]
     * @param string $unit 单位
     * @param bool $bStack 是否水平堆叠图
     * @return array
     */
    public static function getBarHorizonFormat($aYData = [], $aXData = [], $sUnit = "", $bStack = false)
    {
        $aGraph = ["chartType" => "horizontal", "legend" => [], "xAxis" => [], "yAxis" => [], "series" => []];
        //x轴
        $aGraph["xAxis"][] = ["name" => $sUnit];
        $aGraph["yAxis"] = $aYData;
        //y轴
        foreach ($aXData as $xdk => $xdv) {
            $aTemp = [];
            $aTemp["name"] = $xdv["name"];
            $aTemp["type"] = "bar";
            if ($bStack) {
                $aTemp["stack"] = "default";
            }
            $aTemp["data"] = $xdv["data"];
            $aGraph["series"][] = $aTemp;
            $aGraph["legend"][] = $xdv["name"];
        }
        return $aGraph;
    }

    /*
     * 堆叠图
     * @param array $xData X轴数据
     * @param array $yData Y轴数据
     * @param string $unit 单位
     * $yData:
     [['name' => '成交面积', 'data' => [100,200,300,400,500],
      ['name' => '供应面积', 'data' => [100,200,300,400,500]]
     * @return array
     */
    public static function getStackFormat($aXData = [], $aYData = [], $sUnit = "")
    {
        $aGraph = ["chartType" => "stack", "legend" => [], "xAxis" => [], "yAxis" => [], "series" => []];
        //x轴
        $aGraph["xAxis"] = $aXData;
        $aGraph["yAxis"][] = ["name" => $sUnit];
        //y轴
        foreach ($aYData as $ydk => $ydv) {
            $aTemp = [];
            $aTemp["name"] = $ydv["name"];
            $aTemp["type"] = "bar";//堆叠图就是bar
            $aTemp["stack"] = "default";
            $aTemp["data"] = $ydv["data"];
            $aGraph["series"][] = $aTemp;
            $aGraph["legend"][] = $ydv["name"];
        }
        return $aGraph;
    }

    /*
     * 饼图
     * @param $aData array 系列数据[["value"=>14,"name"=>"纯住宅"],["value"=>60,"name"=>"别墅"]]
     * @param string $title 可不填
     * @return array
     */
    public static function getPieFormat($aData = [], $sTitle = "")
    {
        $aGraph = ["chartType" => "pie", "legend" => [], "series" => []];
        $aGraph["legend"] = array_column($aData, "name");
        $aGraph["series"][] = ["name" => $sTitle, "data" => $aData];
        return $aGraph;
    }

    /*
     * 雷达图
     * @param array $aIndicator
         [['name' => '限购', 'max' => 5],
          ['name' => '限贷', 'max' => 5],
          ['name' => '限签', 'max' => 5]]
     * @param array $aData
         [['name' => '福州市', 'data' => [100,200,300,400,500],
          ['name' => '厦门市', 'data' => [100,200,300,400,500]]
     * @param string $title 可不填
     * @return array
     */
    public static function getRadarFormat($aIndicator = [], $aData = [], $sTitle = "")
    {
        $aGraph = ["chartType" => "radar", "legend" => [], "indicator" => [], "series" => []];
        $aGraph["indicator"] = $aIndicator;
        $aGraph["legend"] = array_column($aData, "name");
        //拼接series
        $aTemp = [];
        $aTemp["name"] = $sTitle;
        $aTemp["type"] = "radar";
        $aTemp["data"] = $aData;
        $aGraph["series"][] = $aTemp;
        return $aGraph;
    }
}