<?php

ini_set('memory_limit' , '2048m');
class RealParser
{
    public function getTypesFromBizCode($bizcode)
    {
        switch ($bizcode) {
        case 1:
            return array('land', 'house');
        case 2:
            return array('land', 'house', 'park');
        case 3:
            return array('land');
        case 4:
            return array('house');
        default:
            return array('park');
        }
    }

    public function checkField($field, $doms, $doc)
    {
        $field_array = $this->getFieldArray();

        array_shift($doms);
        $doms = array_values($doms);
        if (!array_key_exists($field, $field_array)) {
            throw new Exception("找不到 $field 這個欄位: " . implode(', ', array_map(array($doc, 'saveHTML'), $doms)));
        }
        $field_data = $field_array[$field];
        if (count($field_data['match']) != count($doms)) {
            throw new Exception("$field 數量(" . count($doms) . ")與 match({coun 不符");
        }
        $ret = array();
        foreach ($field_data['match'] as $i => $match) {
            if (is_null($match)) { // 都符合
                $ret[] = $doms[$i]->nodeValue;
            } elseif (is_array($match)) { // 列表
                $val = preg_replace('/^[\s]*/u', '', $doms[$i]->nodeValue);
                $val = preg_replace('/[\s]*$/u', '', $val);

                if (!in_array($val, $match)) {
                    throw new Exception("欄位 {$field} 的值預期只會有 " .implode(',', $match) . ", 出現了 {$val}");
                }
                $ret[] = $doms[$i]->nodeValue;
            } else {
                $val = preg_replace('/^[\s]*/u', '', $doms[$i]->nodeValue);
                $val = preg_replace('/[\s]*$/u', '', $val);

                if (!preg_match($match, $val, $matches)) {
                    throw new Exception("欄位 {$field} 的值 '" . json_encode($val) . "' 不符合 regex {$match}");
                }
                $ret[] = $matches;
            }
        }
        if (array_key_exists('return', $field_data)) {
            return call_user_func($field_data['return'], $ret);
        }
        return $ret[0];
    }

    public function getFieldArray()
    {
        return array(
            '交易標的' => array(
                'match' => array(
                    array('房地(土地+建物)+車位', '房地(土地+建物)', '土地', '建物'),
                ),
            ),
            '交易年月' => array(
                'match' => array(
                    '/^(\d+)年(\d+)月$/',
                ),
                'return' => function($matches){
                    $ret = new StdClass;
                    $ret->year = $matches[0][1];
                    $ret->month= $matches[0][2];
                    return $ret;
                },
            ),
            '交易總價' => array(
                'match' => array(
                    '/^[0-9,]*$/',
                    array('元', ''),
                ),
                'return' => function($matches){
                    return intval(str_replace(',', '', $matches[0][0]));
                },
            ),
            '交易單價 約' => array(
                'match' => array(
                    '/^[0-9,]*$/',
                    array('(元/坪)', ''),
                ),
                'return' => function($matches){
                    return intval(str_replace(',', '', $matches[0][0]));
                },
            ),
            '建物移轉總面積' => array(
                'match' => array(
                    '/^[\d.,]*$/',
                    array('坪', ''),
                ),
                'return' => function($matches){
                    return floatval(str_replace(',', '', $matches[0][0]));
                },
            ),
            '土地移轉總面積' => array(
                'match' => array(
                    '/^[\d.,]*$/',
                    array('坪', ''),
                ),
                'return' => function($matches){
                    return floatval(str_replace(',', '', $matches[0][0]));
                },
            ),
            '交易筆棟數' => array(
                'match' => array(
                    null, // 土地：2筆, 建物：2棟 ...
                ),
            ),
            '土地區段位置' => array(
                'match' => array(
                    null, // 臨沂段四小段151~200地號
                ),
            ),
            '建物區段門牌' => array(
                'match' => array(
                    null, // 臺北市松山區復興北路51~100號
                ),
            ),
            '建物型態' => array(
                'match' => array(
                    array('辦公商業大樓', '住宅大樓(11層含以上有電梯)', '華廈(10層含以下有電梯)', '公寓(5樓含以下無電梯)', '其他', '套房(1房(1廳)1衛)', '店面（店舖)', '透天厝', '廠辦', '倉庫', '工廠', '農舍'),
                ),
            ),
            '建物現況格局' => array(
                'match' => array(
                    null, // 5 房 1 廳 0 衛 有隔間
                ),
            ),
            '車位總價' => array(
                'match' => array(
                    null, // ??
                ),
            ),
            '有無管理組織' => array(
                'match' => array(
                    array('有', '無'),
                ),
            ),
        );
    }

    public function main(){
        chdir(__DIR__ . '/raw');
        foreach (glob('*') as $file) {
            error_log($file);
            if (file_exists(__DIR__ . '/entry/' . $file)) {
                continue;
            }
            $entries = json_decode(file_get_contents($file));
            foreach ($entries as $entry) {
                // 處理 content 部分
                $doc = new DOMDocument("1.0", "UTF-8");
                $full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $entry->content . '</body></html>';
                @$doc->loadHTML($full_body);

                $new_entry = new StdClass;
                $new_entry->caseNo = $entry->caseNo;
                $new_entry->caseSeq = $entry->caseSeq;
                $new_entry->address = $entry->address;

                $tr_doms = array();
                foreach (array(1, 2) as $pos) {
                    foreach ($doc->getElementsByTagName('table')->item($pos)->getElementsByTagName('tr') as $tr_dom) {
                        $tr_doms[] = $tr_dom;
                    }
                }

                $fields = new StdClass;
                foreach ($tr_doms as $tr_dom) {
                    $td_doms = array();
                    foreach ($tr_dom->childNodes as $child_dom) {
                        if ($child_dom->nodeName == '#text') {
                            continue;
                        }
                        $td_doms[] = $child_dom;
                    }

                    if (1 == count($td_doms)) { // 只有一欄
                        if ($td_doms[0]->getAttribute('colspan') == 3) {
                            // <td class="text_bu_bold" colspan="3">松山區</td>
                            // TODO: 確認是否是 xx區
                        } else {
                            throw new Exception("正常來說 table 內只有一個 td 只會是區");
                        }
                        continue;
                    }

                    if (preg_match('#^(.*)(：|:)$#', $td_doms[0]->nodeValue, $matches)) { // 欄位
                        $fields->{$matches[1]} = $this->checkField($matches[1], $td_doms, $doc);
                    } elseif (preg_match('#^\d+/\d+$#', $td_doms[0]->nodeValue)) {
                        if (count($td_doms) != 2) {
                            throw new Exception('頁碼區右邊, 只會有一欄');
                        }
                        // XXX: 右邊看明細和看地圖不用抓了，之前 crawler 時抓過了
                    } else {
                        throw new Exception("未知的第一欄: " . $td_doms[0]->nodeValue);
                    }
                }
                $new_entry->fields = $fields;

                // 處理 script 部份，抓座標出來
                if (!preg_match("#flytoposAll\('([^']*)','([0-9.]*)','([0-9.]*)','([^']*)','([^']*)','([0-9.]*)','([^']*)','([0-9])'\)#", $entry->script, $matches)) {
                    throw new Exception("script 不正確");
                }
                list(, $type, $pos_x84, $pos_y84, $pos_img, $pos_txt, $radius, $id, $bclass) = $matches;
                $new_entry->pos = array($pos_x84, $pos_y84);

                // 處理 details
                $types = $this->getTypesFromBizCode($entry->bizcode);
                if (count($types) != count($entry->details)) {
                    throw new Exception("details 數量不正確");
                }

                $details = array();
                foreach (array_combine($types, $entry->details) as $type => $detail) {
                    if ('house' == $type) {
                        $doc = new DOMDocument(); //"1.0", "UTF-8");
                        $full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $detail . '</body></html>';
                        @$doc->loadHTML($full_body);

                        $tr_doms = $doc->getElementsByTagName('tr');
                        for ($i = 1; $i < $tr_doms->length; $i ++) {
                            $td_doms = $tr_doms->item($i)->getElementsByTagName('td');
                            $detail_data = new StdClass;
                            $detail_data->type = $type;
                            $detail_data->{'建物區段位置'} = $td_doms->item(0)->nodeValue;
                            $detail_data->{'建物移轉面積'} = floatval($td_doms->item(1)->nodeValue);
                            $val = $td_doms->item(2)->nodeValue;
                            // 住商用 住宅 住家用 住工用 停車空間 共有部份 共有部分 共用部份 共用部分 商業用 國民住宅 工商用 工業用 見使用執照 見其他登記事項 見其它登記事項 辦公室 農舍 '))) { 
                            $detail_data->{'主要用途'} = $td_doms->item(2)->nodeValue;
                            // TODO: 確認集合
                            // 加強磚造 土造 木造 混凝土造 磚造 見使用執照 見其他登記事項 見其它登記事項 鋼筋混凝土加強磚造 鋼筋混凝土構造 鋼筋混凝土造 鋼造 鋼骨混凝土造 鋼骨鋼筋混凝土造 鐵造 預力混凝土造
                            $detail_data->{'主要建材'} = $td_doms->item(3)->nodeValue;
                            list($year, $month) = explode('/', $td_doms->item(4)->nodeValue);
                            $detail_data->{'完成年月'} = new StdClass;
                            $detail_data->{'完成年月'}->year = $year;
                            $detail_data->{'完成年月'}->month = $month;
                            $detail_data->{'總樓層數'} = $td_doms->item(5)->nodeValue;
                            $details[] = $detail_data;
                        }
                    } elseif ('land' == $type) {
                        $doc = new DOMDocument(); //"1.0", "UTF-8");
                        $full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $detail . '</body></html>';
                        @$doc->loadHTML($full_body);

                        $tr_doms = $doc->getElementsByTagName('tr');
                        for ($i = 1; $i < $tr_doms->length; $i ++) {
                            $td_doms = $tr_doms->item($i)->getElementsByTagName('td');
                            $detail_data = new StdClass;
                            $detail_data->type = $type;
                            $detail_data->{'土地區段位置'} = $td_doms->item(0)->nodeValue;
                            $detail_data->{'土地移轉面積'} = floatval($td_doms->item(1)->nodeValue);
                            // 都市：住 都市：其他 都市：商 都市：工 都市：農 非都市： 非都市：一般農業區 非都市：國家公園區 非都市： 山坡地保育區 非都市：工業區 非都市：森林區 非都市：河川區 非都市：特定專用區 非都市：特定農業區 非都市 ：鄉村區 非都市：風景區
                            $detail_data->{'使用分區或編定'} = $td_doms->item(2)->nodeValue;
                            // TODO: 確認集合
                            $details[] = $detail_data;
                        }
                    } elseif ('park' == $type) {
                        $doc = new DOMDocument(); //"1.0", "UTF-8");
                        $full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $detail . '</body></html>';
                        @$doc->loadHTML($full_body);

                        $tr_doms = $doc->getElementsByTagName('tr');
                        for ($i = 1; $i < $tr_doms->length; $i ++) {
                            $td_doms = $tr_doms->item($i)->getElementsByTagName('td');
                            $detail_data = new StdClass;
                            $detail_data->type = $type;
                            $detail_data->{'序號'} = $td_doms->item(0)->nodeValue;
                            // 一樓平面 其他 升降平面 升降機械 坡道平面 坡道機械 塔式車位
                            $detail_data->{'車位類別'} = $td_doms->item(1)->nodeValue;
                            $detail_data->{'車位價格'} = $td_doms->item(2)->nodeValue;
                            $detail_data->{'車位面積'} = floatval($td_doms->item(3)->nodeValue);
                            $details[] = $detail_data;
                        }
                        // TODO
                    }
                }

                $new_entry->details = $details;
                $new_entries[$new_entry->caseNo . '-' . $new_entry->caseSeq] = $new_entry;

            }
        }
        echo json_encode(array_values($new_entries), JSON_UNESCAPED_UNICODE);
    }
}

$parser = new RealParser;
$parser->main();
