<?php

class RealPriceCrawler
{
    public $city = array(
        'A' => '臺北市',
        'C' => '基隆市',
        'F' => '新北市',
        'H' => '桃園縣',
        'O' => '新竹市',
        'J' => '新竹縣',
        'K' => '苗栗縣',
        'B' => '臺中市',
        'M' => '南投縣',
        'N' => '彰化縣',
        'P' => '雲林縣',
        'I' => '嘉義市',
        'Q' => '嘉義縣',
        'D' => '臺南市',
        'E' => '高雄市',
        'T' => '屏東縣',
        'G' => '宜蘭縣',
        'U' => '花蓮縣',
        'V' => '臺東縣',
        'X' => '澎湖縣',
        'W' => '金門縣',
        'Z' => '連江縣',
    );

    public function getAreasFromCity($city)
    {
        $url = 'http://lvr.land.moi.gov.tw/N11/pro/setArea.jsp';
        $response = http_get($url . '?city=' . urlencode($city), array(
            'cookies' => array('JSESSIONID' => $this->cookie)
        ));
        $content = http_parse_message($response)->body;
        $country = new SimpleXMLElement($content);
        $ret = array();
        foreach ($country->Code as $code) {
            $ret[strval($code->id)] = strval($code->name);
        }
        return $ret;
    }

    public function authCode()
    {
        // 1. 先輸入認證碼
        $url = 'http://lvr.land.moi.gov.tw/N11/ImageNumberN13';
        $response = http_get($url);
        $message = http_parse_message($response);
        if (!preg_match('#JSESSIONID=([^;]*);#', $message->headers['Set-Cookie'], $matches)) {
            throw new Exception("找不到 JSESSIONID");
        }
        $this->cookie = $matches[1];

        $tmpfp = tmpfile();
        fwrite($tmpfp, $message->body);
        $tmp_meta = stream_get_meta_data($tmpfp);
        system('jp2a ' . escapeshellarg($tmp_meta['uri']));
        fclose($tmpfp);
        $code = readline("請輸入認證碼: ");

        // 2. 輸入認證碼
        $response = http_post_fields('http://lvr.land.moi.gov.tw/N11/login.action', array(
            'command' => 'login',
            'count' => 0,
            'rand_code' => intval($code),
            'in_type' => 'land',
        ), array(), array(
            'cookies' => array('JSESSIONID' => $this->cookie),
        ));

        if (strpos(http_parse_message($response)->body, '驗證碼錯誤')) {
            error_log('驗證碼錯誤，重試一次');
            $this->authCode();
        }
    }

    protected function getBaseOptions($city_id, $area)
    {
        return array(
            'type' => 'Qrydata',
            'Qry_city' => $city_id,
            'Qry_area_office' => $area,
            'Qry_paytype' => '1,2,3,4',
            'Qry_build' => '',
            'Qry_price_s' => '',
            'Qry_price_e' => '',
            'Qry_p_yyy_s' => 101,
            'Qry_p_yyy_e' => 101,
            'Qry_season_s' => '',
            'Qry_season_e' => '',
            'Qry_doorno' => '',
            'Qry_area_s' => '',
            'Qry_area_e' => '',
            'Qry_order' => 'QA08&desc',
            'Qry_unit' => '2',
            'Qry_area_srh' => '',
        );
    }

    protected function parseHTML($body)
    {
        if (strpos($body, '<div class="description">未找到相關資料</div>')) {
            return array();
        }

        $doc = new DOMDocument();
        $full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $body . '</body></html>';
        @$doc->loadHTML($full_body);

        $result_dom = $doc->getElementById('hiddenresult');
        if (is_null($result_dom)) {
            var_dump($body);
            throw new Exception('找不到 div#hiddenresult');
        }

        $count = 0;
        $results = array();
        foreach ($result_dom->childNodes as $div_result_dom) {
            if ('#text' == $div_result_dom->nodeName) {
                continue;
            }
            if ('div' != $div_result_dom->nodeName) {
                throw new Exception("div#hiddenresult 下面只會是 div.result");
            }
            foreach ($div_result_dom->childNodes as $tr_dom) {
                if ('tr' == $tr_dom->nodeName) {
                    $count ++;
                    $results[$count] = new StdClass;
                    $results[$count]->content = $doc->saveHTML($tr_dom);
                } elseif ('script' == $tr_dom->nodeName) {
                    $results[$count]->script = $doc->saveHTML($tr_dom);
                }
            }
        }
        return array_values($results);
    }

    protected $_last_fetch = null;

    protected function getBodyFromOptions($options)
    {
        $post_data = array();
        foreach ($options as $key => $value) {
            $post_data[$key] = base64_encode($value);
        }
        while (!is_null($this->_last_fetch) and (microtime(true) - $this->_last_fetch) < 3) {
            sleep(1);
        }
        $this->_last_fetch = microtime(true);

        $url = 'http://lvr.land.moi.gov.tw/N11/LandBuildBiz';
        $response = http_post_fields($url, $post_data, array(), array(
            'cookies' => array('JSESSIONID' => $this->cookie),
            'useragent' => 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4',
            'referer' => 'http://lvr.land.moi.gov.tw/N11/login.action',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Origin' => 'http://lvr.land.moi.gov.tw',
                'X-Requested-With' => 'XMLHttpRequest',
            ),
        ));
        $message = http_parse_message($response);
        if (strpos($message->body, '系統連線已逾時,請重新登入')) {
            $this->authCode();
            return $this->getBodyFromOptions($options);
        }

        if ('' == $message->body) {
            var_dump($response);
            print_r($options);
            throw new Exception("抓到的內容是空的");
        }
        return $message->body;
    }

    public function crawlerData($city_id, $area)
    {
        error_log($area);
        $options = $this->getBaseOptions($city_id, $area);

        $datas = array();

        $last_price = null;
        while (true) {
            $body = $this->getBodyFromOptions($options);
            $results = $this->parseHTML($body);
            if (count($results) < 28) {
                break;
            }
            $datas = array_merge($datas, $results);
            if (!preg_match('#<td class="text_10">交易總價：</td>\s+<td width="34%" class="text_red_bold">([^<]*)</td>#m', $results[count($results) - 1]->content, $matches)) {
                throw new Exception('找不到任何價錢?');
            }
            $price = intval(str_replace(',', '', $matches[1]));
            if ($last_price == $price) {
                file_put_contents(
                    __DIR__ . '/warnings',
                    "{$area} 價錢為 {$price} 超過 28 筆，可能會遺漏資料\n",
                    FILE_APPEND
                );
                break;
            }
            $last_price = $price;
            $options['Qry_price_e'] = intval($price / 10000);
            echo $price . "\n";
        }

        return $datas;
    }
}

$crawler = new RealPriceCrawler;
$crawler->authCode();
foreach ($crawler->city as $city_id => $city_name) {
    foreach ($crawler->getAreasFromCity($city_id) as $area_id => $area_name) {
        $filename = $area_id . '-' . $city_name . '-' . $area_name;
        if (file_exists(__DIR__ . '/raw/' . $filename)) {
            continue;
        }
        $data = $crawler->crawlerData($city_id, $area_id);
        file_put_contents(__DIR__ . '/raw/' . $filename, json_encode($data));
    }
}
