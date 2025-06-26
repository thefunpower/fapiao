# 发票

安装

~~~
"thefunpower/fapiao": "dev-main"
~~~ 

## 发票

~~~
<?php

use Fapiao\FpApi;

// 初始化 FpApi
$fpApi = new FpApi(
    aid: '',
    appSecret: 'FUemEpT6FRUVW6UN',
    baseUrl: 'https://api.fpapi.com'
);

// 准备请求体
$body = [
    'company_name' => '射手科技（珠海）有限公司',
    'tax_no' => '91440400MA51P7627N',
    'tax_area_code' => '3100',
    'notify_url' => 'https://baidu.com/notify'
];

// 发送 POST 请求
try {
    [$httpCode, $response] = $fpApi->createCompany($body);
    echo "HTTP 状态码: $httpCode\n";
    echo "响应内容: $response\n";
} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
~~~
 


### 开源协议 

[Apache License 2.0](LICENSE)
