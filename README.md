# 发票

~~~
composer require guzzlehttp/guzzle
composer require thefunpower/fapiao
~~~

`guzzle` 为 `7.x` 版本

## 金蝶云开票

### 开发票

~~~
$zone = new FpYun();
$zone->set([
   'code'=>'',//业务编码
   'user' => '',
   'appId' => '',
   'accountId' => '',
   'appSecret' => '',
   'baseUrl' => 'https://cosmic-sandbox.piaozone.com/shhcyylqx',
]);

$zone->requestId = time();
$zone->getAccessToken();  
~~~

创建发票

~~~ 
$invoices = [
    [
        'drawer'=>'', // 开票人，金蝶
        'reviewer'=>'', // 复核人，金蝶 

        'invoice_type' => 2, // 发票类型，1：专票，2：普票 

        'custom_invoice_no' => '20250707001AA', // 自定义发票号 最长20个字符，请确保每张发票都使用唯一的发票流水号，以保证您处理回调逻辑可将发票一一对应 
        'buyer_name' => '深圳金蝶账无忧网络科技有限公司',//购买方名称
        'buyer_tax_no' => '91440300358768292H1', // 购买方税号统一社会信用代码，buyer_type=1时必填

        'seller_name'=>'上海好齿源医疗器械有限公司',  // 销方名称 
        'seller_tax_no'=>'91310104324439343T', // 销方税号 

        'seller_bank'=>'招商银行股份有限公司', // 销方开户行
        'seller_bank_account'=>'6228480310003648092', // 销方开户行账号
        'seller_address'=>'上海市浦东新区浦东南路1000号', // 销方地址

        'drawer'=>'开票人', // 开票人 必须

        //'buyer_tel'=>'13800138000', // 购买方电话
        'buyer_email'=>'68103403@qq.com',// 购买方邮箱

        'remark' => '', // 发票备注，最长支持230个字符，无备注请传入空字符串
        'goods' => [
            [
                'goods_name' => '货物运输费', // 货物或应税劳务名称，最长100个字符，如：货物运输费
                'tax_scope_code' => '3070402000000000000', // 商品和服务税收编码，如：3070402000000000000，由19个数字组成
                'amount' => 200.00,
                'tax_rate' => '0.13', // 税率，如：0.13
                'discount_amount'=> 0,//折扣金额，包含整数部分、小数部分和小数点，加起来最长16个字符，最长2位小数
                'unit' => '个', // 单位，最长10个字符，如：个
                'quantity' => '1', // 数量，最长16个字符，最长2位小数
                'unit_price' => 200.00, // 单价，包含整数部分、小数部分和小数点，加起来最长16个字符，最长2位小数 

            ]
        ]
    ], 
]; 
$res = $zone->createInvoice($invoices); 
pr($res);
~~~

查寻发票

~~~
//销售方税号
$saler_tax_no = '';

//自定义单号
$serialNo = '20250707001AA';

$res = $zone->query($serialNo,$saler_tax_no);
pr($res);
~~~

返回

~~~
Array
(
    [url] => https://api-dev.piaozone.com/doc/free/fileInfo/preview/nf1391878360442548224
    [ofd] => https://api-dev.piaozone.com/doc/free/fileInfo/preview/nf1391878349784821760
    [xml] => https://api-dev.piaozone.com/doc/free/fileInfo/preview/nf1391878344713908224
    [invoice_number] => 22539455751772180480
)
~~~

红冲

~~~
//取发票
$serialNo     = '202507081516004';
$invoiceNum   = '22545145611618979840';
$invoice_type = 1;
$zone->reverseInvoice($serialNo, $invoiceNum,$invoice_type,$sellerTaxpayerId);
~~~


`$serialNo` 流水号

`$invoiceNum` 要红冲的蓝票号

`$invoice_type` 1专票 2 普票

`$sellerTaxpayerId` 销方税号


查寻所有发票

~~~
$res = $zone->findAll($sellerTaxpayerId);
~~~

`$sellerTaxpayerId` 销方税号



查寻部分发票

~~~
find($tax_no = '税号', $invoice_type = 2, $pageNo = 1, $pageSize = 50)
~~~

`$tax_no` 税号

`$invoice_type` 发票类型，1：专票，2：普票

`$pageNo` 页码，从1开始

`$pageSize`  每页数量


## 自开票

https://fpapi.com/


安装

~~~
"thefunpower/fapiao": "dev-main"
~~~ 

`data/code.xlsx` 是 税收编码表,一般财务知道自己的税收编码。

### 开具多张发票示例
 
~~~ 
$invoices = [
    [
        'custom_invoice_no' => 'G2MKbb35', // 自定义发票号 最长20个字符，请确保每张发票都使用唯一的发票流水号，以保证您处理回调逻辑可将发票一一对应
        'invoice_type' => 2, // 发票类型，1：专票，2：普票
        'is_contain_tax' => 1, // 是否含税，1：含税，2：不含税
        'buyer_type' => 1, // 购方身份类型，1：企业，2、自然人
        'buyer_name' => '射手科技（珠海）有限公司',//购买方名称
        'buyer_tax_no' => '91440400MA51P7627N', // 购买方税号统一社会信用代码，buyer_type=1时必填
        'buyer_email' => '123456@qq.com', //购买方邮箱，用于接收开出的发票
        'remark' => '', // 发票备注，最长支持230个字符，无备注请传入空字符串
        'goods' => [
            [
                'model'=>'',//规格
                'unit'=>'',//单位
                'quantity'=>1,//数量
                'unit_price'=>100,//单价
                'goods_name' => '货物运输费', // 货物或应税劳务名称，最长100个字符，如：货物运输费
                'tax_scope_code' => '3070402000000000000', // 商品和服务税收编码，如：3070402000000000000，由19个数字组成
                'amount' => '200.00',
                'tax_rate' => '0.13', // 税率，如：0.13
                'discount_amount'=> 0,//折扣金额，包含整数部分、小数部分和小数点，加起来最长16个字符，最长2位小数
            ]
        ]
    ], 
];

try {
    $result = $api->createInvoice('your_company_id', $invoices);
    print_r($result);
} catch (Exception $e) {
    echo '开具发票失败: ' . $e->getMessage();
}
~~~

开票回调

~~~
{
  "task_id": "2PmHR63vW9VJF5HUveUDSpNS",
  "task_type": 11,
  "task_state": 1,
  "task_msg": "success",
  "company_id": "L47xj3KCX7khBcTxD1DyD0sb",
  "invoice_list": [
    {
      "custom_invoice_no": "G2MKbb35",
      "status": 1,
      "message": "[成功]-",
      "electronic_invoice_no": "23442000000047645422",
      "invoice_url": "https://p.fpapi.com/nm3C79KE6RMcMAwW1cjF6QpD"
    },
    {
      "custom_invoice_no": "9MYLtciN",
      "status": 2,
      "message": "[失败]未查询到购买方信息，且未进行批量设置",
      "redraw_task_id": "dG85gHCv4kHQ63LuS7Uk97W6"
    },
    {
      "custom_invoice_no": "dm4TBL7E",
      "status": -1,
      "message": "[失败]购销方不能为同一家企业"
    }
  ]
}
~~~

### 红冲发票示例

~~~
try {
    $result = $api->reverseInvoice(
        'your_company_id',          // 企业ID
        '23442000000178501228',     // 要红冲的发票号码
        '2023-08-15',              // 发票开具日期
        '654321@qq.com'             // 接收红冲发票的邮箱
    );
    
    print_r($result);
    /*
    成功响应示例:
    {
        "code": 200,
        "msg": "success",
        "data": {
            "task_id": "5f8d7a4b2c6d1a3e4f5g6h7"
        }
    }
    */
} catch (Exception $e) {
    echo '发票红冲失败: ' . $e->getMessage();
}
~~~

回调

https://fpapi.com/doc/#/page/hongChongFaPiao

~~~
{
  "task_id": "twVKmJh9fVmexSrGfytVA4L0",
  "task_type": 13,
  "task_state": 1,
  "task_msg": "success",
  "company_id": "G4DJAKEDf7cCFCC3EqFWkgNW",
  "be_offset_invoice": {
    "electronic_invoice_no": "23442000000178501228",
    "draw_date": "2023-08-28"
  },
  "offset_invoice": {
    "status": 1,
    "message": "[成功]-",
    "offset_invoice_no": "23442000000180092939",
    "invoice_url": "https://p.fpapi.com/80cnDLWX5xfmHxFWrvH4UUFm"
  }
}
~~~


### 上传验证码示例

~~~ 
try {
    $result = $api->uploadCaptcha(
        'your_company_id',      // 企业ID
        '5f8d7a4b2c6d1a3e4f5g6h7', // 任务ID（通常从任务回调或创建任务响应中获取）
        '123456'               // 收到的短信验证码
    );
    
    print_r($result);
    /*
    成功响应示例:
    {
        "code": 200,
        "msg": "验证码上传成功"
    }
    */
} catch (Exception $e) {
    echo '验证码上传失败: ' . $e->getMessage();
}
~~~

### 任务重发

~~~
// 重发任务示例
try {
    $result = $api->restartTask(
        'your_company_id',      // 企业ID
        '5f8d7a4b2c6d1a3e4f5g6h7' // 需要重发的任务ID
    );
    
    print_r($result);
    /*
    成功响应示例:
    {
        "code": 200,
        "msg": "任务已重新发起"
    }
    */
} catch (Exception $e) {
    echo '任务重发失败: ' . $e->getMessage();
}
~~~



### 开源协议 

[Apache License 2.0](LICENSE)
