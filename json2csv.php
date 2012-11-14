<?php
ini_set('memory_limit', '2048m');

$case_fp = fopen('case.csv', 'w');
$park_fp = fopen('park.csv', 'w');
$land_fp = fopen('land.csv', 'w');
$house_fp = fopen('house.csv', 'w');
fputs($case_fp, '#CaseNo,CaseSeq,單價,總價,交易年月,廳,衛,隔間,房,門牌,有無管理組織,車位總價,建物數,車位數,土地數,建物總面積,建物型態,位置,地址' . PHP_EOL);
fputs($house_fp, '#CaseNo,CaseSeq,總樓層數,主要用途,建物移轉面積,建物區段位置,完成年月,主要建材' . PHP_EOL);
fputs($park_fp, '#CaseNo,CaseSeq,車位類別,序號,車位面積,車位價格' . PHP_EOL);
fputs($land_fp, '#CaseNo,CaseSeq,土地移轉面積,土地區段位置,使用分區或編定' . PHP_EOL);
$json = json_decode(file_get_contents($_SERVER['argv'][1]));
foreach ($json as $case) {
    $fields = $case->fields;
    $data = array(
        $case->caseNo,
        $case->caseSeq,
        $fields->{'交易單價(含車位)'},
        $fields->{'交易總價(含車位)'},
        $fields->{'交易年月'}->year . '-' . $fields->{'交易年月'}->month,
        intval($fields->{'建物現況格局'}->{'廳'}),
        intval($fields->{'建物現況格局'}->{'衛'}),
        strval($fields->{'建物現況格局'}->{'隔間'}),
        intval($fields->{'建物現況格局'}->{'房'}),
        $fields->{'建物區段門牌'},
        $fields->{'有無管理組織'},
        $fields->{'車位總價'},
        $fields->{'交易筆棟數'}->{'建物'},
        $fields->{'交易筆棟數'}->{'車位'},
        $fields->{'交易筆棟數'}->{'土地'},
        $fields->{'建物移轉總面積'},
        $fields->{'建物型態'},
        implode(',', $case->pos),
        $case->address,
    );
    fputcsv($case_fp, $data);

    foreach ($case->details as $detail) {
        if ($detail->type == 'house') {
            $data = array(
                $case->caseNo,
                $case->caseSeq,
                $detail->{'總樓層數'},
                $detail->{'主要用途'},
                $detail->{'建物移轉面積'},
                $detail->{'建物區段位置'},
                $detail->{'完成年月'}->year . '/' . $detail->{'完成年月'}->month,
                $detail->{'主要建材'}
            );
            fputcsv($house_fp, $data);
        } elseif ($detail->type == 'park') {
            $data = array(
                $case->caseNo,
                $case->caseSeq,
                $detail->{'車位類別'},
                $detail->{'序號'},
                $detail->{'車位面積'},
                $detail->{'車位價格'},
            );
            fputcsv($park_fp, $data);
        } elseif ($detail->type == 'land') {
            $data = array(
                $case->caseNo,
                $case->caseSeq,
                $detail->{'土地移轉面積'},
                $detail->{'土地區段位置'},
                $detail->{'使用分區或編定'},
            );
            fputcsv($land_fp, $data);
        }
    }
}
