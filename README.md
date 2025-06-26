# 发票

安装

~~~
"thefunpower/fapiao": "dev-main"
~~~ 

## 开具多张发票示例
 
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

## 红冲发票示例

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



### 开源协议 

[Apache License 2.0](LICENSE)
