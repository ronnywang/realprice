crawler.php 
爬資料的程式
需要安裝 pecl-http, php5-readline
以及需要有 jp2a (把 captcha 圖片轉成 ascii ，這樣只要專心在 console 上輸入認證碼就好)

raw/
放從實價登錄網頁抓到原始資料，包含 HTML 和 javascript

完整 json 檔案:
https://www.dropbox.com/sh/k4bf49ebpga1ubz/Ac9nhbnZB2
2012/11/06 處理完成，一共 17,826 筆

json 格式:
* Array(CaseObject ... )
* CaseObject:
| Field | Type | Description |
| caseNo | string | Case 的 ID |
| caseSeq | integer | Case 的序號(應該是賣了第幾次?) |
| address | string | Case 的地址 |
| pos | array | 座標位置(lng, lat) |
| fields | FieldObject | Case 的詳細資訊 |
| details | array({Land|Park|House}Object ...)| Case 附屬的建物、停車或是土地 |
* FieldObject
| Field | Type | Description |
| 交易標的 | string | '房地(土地+建物)+車位', '房地(土地+建物)', '土地', '建物' |
| 交易年月 | object(year, month) | |
| 交易總價 | integer | 單位元 |
| 交易單價 約 | integer | 單位(元/坪) |
| 建物移轉總面積 | float | 單位坪 |
| 土地移轉總面積 | float | 單位坪 |
| 交易筆棟數 | string | 文字描述 |
| 土地區段位置 | string | 土地位置 |
| 建物區段門牌 | string | |
| 建物型態 | string | '辦公商業大樓', '住宅大樓(11層含以上有電梯)', '華廈(10層含以下有電梯)', '公寓(5樓含以下無電梯)', '其他', '套房(1房(1廳)1衛)', '店面（店舖)', '透天厝', '廠辦', '倉庫', '工廠', '農舍' |
| 建物現況格局 | string | x 房 x 廳 x 衛 有隔間 |
| 車位總價 | string | |
| 有無管理組織 | string | 有, 無 |
* HouseObject
| Field | Type | Description |
| type=house | string | 此為建物資訊 |
| 建物區段位置 | string | |
| 建物移轉面積 | float | 單位坪 |
| 主要用途 | string | 住商用 住宅 住家用 住工用 停車空間 共有部份 共有部分 共用部份 共用部分 商業用 國民住宅 工商用 工業用 見使用執照 見其他登記事項 見其它登記事項 辦公室 農舍 |
| 主要建材 | string | 加強磚造 土造 木造 混凝土造 磚造 見使用執照 見其他登記事項 見其它登記事項 鋼筋混凝土加強磚造 鋼筋混凝土構造 鋼筋混凝土造 鋼造 鋼骨混凝土造 鋼骨鋼筋混凝土造 鐵造 預力混凝土造|
| 完成年月 | object(year, month) | |
| 總樓層數 | string | |
* LandObject
| Field | Type | Description |
| type=land | string | 此為土地資訊 |
| 土地區段位置 | string | |
| 土地移轉面積 | float | 單位坪 |
| 使用分區或編定 | string | 都市：住 都市：其他 都市：商 都市：工 都市：農 非都市： 非都市：一般農業區 非都市：國家公園區 非都市： 山坡地保育區 非都市：工業區 非都市：森林區 非都市：河川區 非都市：特定專用區 非都市：特定農業區 非都市 ：鄉村區 非都市：風景區 |
* ParkObject
| Field | Type | Description |
| type=park | string | 此為停車資訊 |
| 序號 | string | |
| 車位類別 | string | 一樓平面 其他 升降平面 升降機械 坡道平面 坡道機械 塔式車位 |
| 車位價格 | string | |
| 車位面積 | float | |
