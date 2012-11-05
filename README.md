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
<table>
  <tr>
    <td>Field</td>
    <td>Type</td>
    <td>Description</td>
  </tr>
  <tr>
    <td>caseNo</td>
    <td>string</td>
    <td>Case 的 ID</td>
  </tr>
  <tr>
    <td>caseSeq</td>
    <td>integer</td>
    <td>Case 的序號(應該是賣了第幾次?)</td>
  </tr>
  <tr>
    <td>address</td>
    <td>string</td>
    <td>Case 的地址</td>
  </tr>
  <tr>
    <td>pos</td>
    <td>array</td>
    <td>座標位置(lng, lat)</td>
  </tr>
  <tr>
    <td>fields</td>
    <td>FieldObject</td>
    <td>Case 的詳細資訊</td>
  </tr>
  <tr>
    <td>details</td>
    <td>array({Land|Park|House}Object ...)| Case 附屬的建物、停車或是土地</td>
  </tr>
</table>
* FieldObject
<table>
  <tr>
    <td>Field</td>
    <td>Type</td>
    <td>Description</td>
  </tr>
  <tr>
    <td>交易標的</td>
    <td>string</td>
    <td>'房地(土地+建物)+車位', '房地(土地+建物)', '土地', '建物'</td>
  </tr>
  <tr>
    <td>交易年月</td>
    <td>object(year, month)</td>
    <td></td></tr>
  <tr>
    <td>交易總價</td>
    <td>integer</td>
    <td>單位元</td>
  </tr>
  <tr>
    <td>交易單價 約</td>
    <td>integer</td>
    <td>單位(元/坪)</td>
  </tr>
  <tr>
    <td>建物移轉總面積</td>
    <td>float</td>
    <td>單位坪</td>
  </tr>
  <tr>
    <td>土地移轉總面積</td>
    <td>float</td>
    <td>單位坪</td>
  </tr>
  <tr>
    <td>交易筆棟數</td>
    <td>string</td>
    <td>文字描述</td>
  </tr>
  <tr>
    <td>土地區段位置</td>
    <td>string</td>
    <td>土地位置</td>
  </tr>
  <tr>
    <td>建物區段門牌</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>建物型態</td>
    <td>string</td>
    <td>'辦公商業大樓', '住宅大樓(11層含以上有電梯)', '華廈(10層含以下有電梯)', '公寓(5樓含以下無電梯)', '其他', '套房(1房(1廳)1衛)', '店面（店舖)', '透天厝', '廠辦', '倉庫', '工廠', '農舍'</td>
  </tr>
  <tr>
    <td>建物現況格局</td>
    <td>string</td>
    <td>x 房 x 廳 x 衛 有隔間</td>
  </tr>
  <tr>
    <td>車位總價</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>有無管理組織</td>
    <td>string</td>
    <td>有, 無</td>
  </tr>
</table>
* HouseObject
<table>
  <tr>
    <td>Field</td>
    <td>Type</td>
    <td>Description</td>
  </tr>
  <tr>
    <td>type=house</td>
    <td>string</td>
    <td>此為建物資訊</td>
  </tr>
  <tr>
    <td>建物區段位置</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>建物移轉面積</td>
    <td>float</td>
    <td>單位坪</td>
  </tr>
  <tr>
    <td>主要用途</td>
    <td>string</td>
    <td>住商用 住宅 住家用 住工用 停車空間 共有部份 共有部分 共用部份 共用部分 商業用 國民住宅 工商用 工業用 見使用執照 見其他登記事項 見其它登記事項 辦公室 農舍</td>
  </tr>
  <tr>
    <td>主要建材</td>
    <td>string</td>
    <td>加強磚造 土造 木造 混凝土造 磚造 見使用執照 見其他登記事項 見其它登記事項 鋼筋混凝土加強磚造 鋼筋混凝土構造 鋼筋混凝土造 鋼造 鋼骨混凝土造 鋼骨鋼筋混凝土造 鐵造 預力混凝土造</td></tr>
  <tr>
    <td>完成年月</td>
    <td>object(year, month)</td>
    <td></td></tr>
  <tr>
    <td>總樓層數</td>
    <td>string</td>
    <td></td></tr>
</table>
* LandObject
<table>
  <tr>
    <td>Field</td>
    <td>Type</td>
    <td>Description</td>
  </tr>
  <tr>
    <td>type=land</td>
    <td>string</td>
    <td>此為土地資訊</td>
  </tr>
  <tr>
    <td>土地區段位置</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>土地移轉面積</td>
    <td>float</td>
    <td>單位坪</td>
  </tr>
  <tr>
    <td>使用分區或編定</td>
    <td>string</td>
    <td>都市：住 都市：其他 都市：商 都市：工 都市：農 非都市： 非都市：一般農業區 非都市：國家公園區 非都市： 山坡地保育區 非都市：工業區 非都市：森林區 非都市：河川區 非都市：特定專用區 非都市：特定農業區 非都市 ：鄉村區 非都市：風景區</td>
  </tr>
</table>
* ParkObject
<table>
  <tr>
    <td>Field</td>
    <td>Type</td>
    <td>Description</td>
  </tr>
  <tr>
    <td>type=park</td>
    <td>string</td>
    <td>此為停車資訊</td>
  </tr>
  <tr>
    <td>序號</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>車位類別</td>
    <td>string</td>
    <td>一樓平面 其他 升降平面 升降機械 坡道平面 坡道機械 塔式車位</td>
  </tr>
  <tr>
    <td>車位價格</td>
    <td>string</td>
    <td></td></tr>
  <tr>
    <td>車位面積</td>
    <td>float</td>
    <td></td></tr>
</table>
