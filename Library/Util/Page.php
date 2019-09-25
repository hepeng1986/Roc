<?php

class Util_Page
{

    public static function getUrl ($url, $params)
    {
        if ($url != '') {
            $delimiter = '/';
            $url = rtrim($url, $delimiter);
            if (! empty($params)) {
                foreach ($params as $k => $v) {
                    if ($v === '' || is_null($v)) {
                        unset($params[$k]);
                    }
                }
                $url .= $delimiter . str_replace(array(
                    '&',
                    '='
                ), $delimiter, http_build_query($params));
            }

            $url .= '.html';
            //$url .= '?' . http_build_query($params);
        } else {
            $url = Util_Common::getUrl($url, $params, true);
        }

        return $url;
    }

    public static function getPage ($total, $currpage, $pagesize, $url = '', $params = array(), $pagenum = 9, $iPost = true, $bShowCnt = true)
    {
        if ($total <= $pagesize) {
            return false;
        }
        $pager = '<form action="' . self::getUrl($url, $params) . '"><ul class="pagination">';
        if ($currpage <= 1) {
            $pager .= '<li class="disabled"><a href="javascript:;">首页</a></li><li class="disabled"><a href="javascript:;">上一页</a></li>';
        } else {
            $params['page'] = 1;
            $pager .= '<li><a href="' . self::getUrl($url, $params) . '">首页</a></li>';
            $params['page'] = $currpage - 1;
            $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">上一页</a></li>';
        }

        $c_page = $currpage;
        $a_page = ceil($total / $pagesize);
        if ($c_page < $pagenum) {
            $s_page = 1;
            $e_page = $a_page > $pagenum ? $pagenum : $a_page;
        } else {
            if (($c_page + 2) > $a_page) {
                $s_page = $c_page - 4;
                $e_page = $c_page;
            } else {
                $s_page = $c_page - 2;
                $e_page = $c_page + 2;
            }
        }

        for ($i = $s_page; $i <= $e_page; $i ++) {
            $params['page'] = $i;
            if ($c_page == $i) {
                $pager .= '<li class="disabled"><a href="javascript:;">' . $i . '</a></li>';
            } else {
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">' . $i . '</a></li>';
            }
        }

        if ($currpage >= $a_page) {
            $pager .= '<li class="disabled"><a href="javascript:;">下一页</a></li>';
            $pager .= '<li class="disabled"><a href="javascript:;">尾页</a></li>';
        } else {
            $params['page'] = $currpage + 1;
            $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">下一页</a></li>';

            $params['page'] = $a_page;
            $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">尾页</a></li>';
        }

        if ($bShowCnt) {
            $pager .= '<li class="disabled"><a href="javascript:;">第' . $currpage . '页 / 共' . $a_page . '页' . $total . '条数据</a></li>';
        }

        if ($iPost) {
            $pager .= '<li class="disabled"><a href="javascript:;" style="padding:3px 12px 3px 6px">&nbsp;跳转至&nbsp;<input type="text" style="text-align:center" size="3" value="' . $currpage . '" name="page">&nbsp;<input type="submit" name="pageBtn"></a></li>';
        }
        $pager .= "</ul></form>";

        return $pager;
    }

    public static function getFrontPage ($total, $currpage, $pagesize, $url = '', $params = array())
    {
        if ($total <= $pagesize) {
            return false;
        }
        $a_page = ceil($total / $pagesize);
        $pager = '<ul class="pagination">';

        if ($currpage < 6) {
            $s_page = 1;
            $e_page = $a_page > 6 ? 6 : $a_page;
            if ($currpage == 5) {
                $e_page = $a_page > 6 ? 7 : $a_page;
            }
        } else {
            if ($a_page >= ($currpage + 2)) {
                $s_page = ($currpage - 3);
                $e_page = ($currpage + 2);
            } else {
                $s_page = ($a_page - 5);
                $e_page = $a_page;
            }
            if ($a_page == ($e_page + 1)) {
                $e_page = $a_page;
            }
        }

        if ($currpage <= 1) {
            $pager .= '<li class="disabled"><a href="javascript:;">上一页</a></li>';
        } else
            if ($s_page >= 3) {
                $params['page'] = $currpage - 1;
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">上一页</a></li>';
                $params['page'] = 1;
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">1</a></li>';
                $pager .= '<li class="disabled dot">...</li>';
            } else {
                $params['page'] = $currpage - 1;
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">上一页</a></li>';
            }

        for ($i = $s_page; $i <= $e_page; $i ++) {
            $params['page'] = $i;
            if ($currpage == $i) {
                $pager .= '<li class="disabled current"><a href="javascript:;">' . $i . '</a></li>';
            } else {
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">' . $i . '</a></li>';
            }
        }

        if ($currpage >= $a_page) {
            $pager .= '<li class="disabled"><a href="javascript:;">下一页</a></li>';
        } else
            if (($a_page - 2) >= $e_page) {
                $params['page'] = $a_page;
                $pager .= '<li class="disabled dot">...</li>';
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">' . $params['page'] . '</a></li>';

                $params['page'] = $currpage + 1;
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">下一页</a></li>';
            } else {

                $params['page'] = $currpage + 1;
                $pager .= '<li class=""><a href="' . self::getUrl($url, $params) . '">下一页</a></li>';
            }
        $pager .= "</ul>";

        return $pager;
    }
}