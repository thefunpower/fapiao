<?php

namespace Fapiao;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * https://fpapi.com/ 
 */
class FpApi
{
    private $aid;
    private $appSecret;
    private $baseUrl;
    private $client;

    /**
     * 重发任务
     * 
     * 当出现验证码超时、人脸认证超时或其他情况时，允许通过此接口重新发起任务
     *
     * @param string $company_id 企业在平台对应的唯一标识
     * @param string $task_id 任务唯一ID
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function restartTask(
        string $company_id,
        string $task_id,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        // 基本参数验证
        if (empty($task_id)) {
            throw new \Exception('任务ID不能为空');
        }

        // 空body，因为接口不需要请求体参数
        $body = [];

        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id . '/task/' . $task_id . '/restart';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 上传短信验证码
     * 
     * 用于除广东、浙江、湖北外的电子税局登录时的验证码上传
     *
     * @param string $company_id 企业在平台对应的唯一标识
     * @param string $task_id 任务唯一ID
     * @param string $captcha 短信验证码
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function uploadCaptcha(
        string $company_id,
        string $task_id,
        string $captcha,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        // 验证码基础验证
        if (empty($captcha)) {
            throw new \Exception('验证码必须');
        }

        $body = [
            'captcha' => $captcha
        ];

        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id . '/task/' . $task_id . '/sms-code';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }
    /**
     * 开具发票
     *
     * @param string $company_id 企业在平台对应的唯一标识
     * @param array $invoices 需要开具的发票列表
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function createInvoice(
        string $company_id,
        array $invoices,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        // 验证必填字段
        foreach ($invoices as $invoice) {
            if (!isset(
                $invoice['custom_invoice_no'],
                $invoice['invoice_type'],
                $invoice['is_contain_tax'],
                $invoice['buyer_type'],
                $invoice['buyer_name'],
                $invoice['buyer_email'],
                $invoice['remark'],
                $invoice['goods']
            )) {
                throw new \Exception('缺少必填发票字段');
            }

            // 如果是企业购方，必须提供税号
            if ($invoice['buyer_type'] == 1 && !isset($invoice['buyer_tax_no'])) {
                throw new \Exception('企业购方必须提供税号');
            }

            // 验证商品信息
            foreach ($invoice['goods'] as $goods) {
                if (!isset($goods['goods_name'], $goods['tax_scope_code'], $goods['amount'], $goods['tax_rate'])) {
                    throw new \Exception('缺少必填商品字段');
                }
            }

            // 特定业务类型验证
            if (isset($invoice['specific_service_type'])) {
                switch ($invoice['specific_service_type']) {
                    case 2001: // 成品油
                        foreach ($invoice['goods'] as $goods) {
                            if (!isset($goods['unit'])) {
                                throw new \Exception('成品油业务必须提供单位');
                            }
                        }
                        break;
                    case 2002: // 不动产经营租赁服务
                        if (!isset($invoice['bdcjyzl_specific_service'])) {
                            throw new \Exception('不动产经营租赁服务必须提供相关服务信息');
                        }
                        break;
                    case 2003: // 货物运输服务
                        if (!isset($invoice['hwysfw_specific_service'])) {
                            throw new \Exception('货物运输服务必须提供相关服务信息');
                        }
                        break;
                    case 2004: // 旅客运输服务
                        if (!isset($invoice['lkysfw_specific_service'])) {
                            throw new \Exception('旅客运输服务必须提供相关服务信息');
                        }
                        break;
                }
            }
        }

        $body = ['invoices' => $invoices];
        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id . '/invoicing';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 红冲发票
     *
     * @param string $company_id 企业在平台对应的唯一标识
     * @param string $electronic_invoice_no 需要红冲的全电发票号码
     * @param string $draw_date 需要红冲的全电发票开具日期
     * @param string $email 接收红冲发票的电子邮箱
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function reverseInvoice(
        string $company_id,
        string $electronic_invoice_no,
        string $draw_date,
        string $email,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        // 验证日期格式
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $draw_date)) {
            throw new \Exception('日期格式不正确，请使用YYYY-MM-DD格式');
        }

        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('邮箱格式不正确');
        }

        $body = [
            'electronic_invoice_no' => $electronic_invoice_no,
            'draw_date' => $draw_date,
            'email' => $email
        ];

        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id . '/invoice_offsetting';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 创建开票员
     *
     * @param string $drawer_name 开票员姓名
     * @param string $id_number 身份证号码
     * @param string $phone_number 手机号码
     * @param string $login_pwd 税局登录密码
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function createDrawer(
        string $drawer_name,
        string $id_number,
        string $phone_number,
        string $login_pwd,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        $body = [
            'drawer_name' => $drawer_name,
            'id_number' => $id_number,
            'phone_number' => $phone_number,
            'login_pwd' => $login_pwd
        ];

        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/drawers';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 绑定开票员到企业
     *
     * @param string $company_id 企业ID
     * @param string $login_account 法人/财务身份证号
     * @param string $login_password 税局登录密码
     * @param string $drawer_code 开票员编码
     * @param int $need_consign_file 是否需要发票源文件(0/1)
     * @param int $auto_renewal 是否自动续费(0/1)
     * @param string|null $queryStr 查询字符串(可选)
     * @param int|null $timestamp 时间戳(可选)
     * @param string|null $nonceStr 随机字符串(可选)
     * @return array
     * @throws GuzzleException
     */
    public function bindDrawerToCompany(
        string $company_id,
        string $login_account,
        string $login_password,
        string $drawer_code,
        int $need_consign_file = 0,
        int $auto_renewal = 1,
        ?string $queryStr = '',
        ?int $timestamp = null,
        ?string $nonceStr = null
    ) {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();

        $body = [
            'login_account' => $login_account,
            'login_password' => $login_password,
            'drawer_code' => $drawer_code,
            'need_consign_file' => $need_consign_file,
            'auto_renewal' => $auto_renewal
        ];

        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id . '/add_drawer';

        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);

            $res = (string)$response->getBody();
            return json_decode($res, true);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 创建公司 API 请求。
     *
     * @param array $body 请求体，包含 company_name, tax_no, tax_area_code, notify_url
     * @param string|null $queryStr 查询字符串（可选）
     * @param int|null $timestamp 时间戳（可选，默认为当前时间）
     * @param string|null $nonceStr 随机字符串（可选，默认为生成）
     * @return array [httpCode, responseBody] HTTP 状态码和响应体
     * @throws GuzzleException
     */
    public function createCompany(array $body, ?string $queryStr = '', ?int $timestamp = null, ?string $nonceStr = null)
    {
        $timestamp = $timestamp ?? time();
        $nonceStr = $nonceStr ?? $this->generateNonceStr();
        $headers = $this->generateSignature($queryStr, $body, $nonceStr, $timestamp);
        $url = $this->baseUrl . '/' . $this->aid . '/companies';
        try {
            $response = $this->client->request('POST', $url, [
                'json' => $body,
                'headers' => $headers,
            ]);
            $res = (string) $response->getBody();
            $res = json_decode($res, true);
            return $res;
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 删除企业
     */
    public function deleteCompany($company_id)
    {
        $url = $this->baseUrl . '/' . $this->aid . '/company/' . $company_id;
        /**
         * DELETE请求
         */
        try {
            $response = $this->client->request('DELETE', $url, [
                'headers' => $this->generateSignature('', [], $this->generateNonceStr(), time()),
            ]);
            $res = (string) $response->getBody();
            $res = json_decode($res, true);
            return $res;
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * 构造函数，初始化 API 凭据、基础 URL 和 Guzzle 客户端。
     *
     * @param string $aid 应用 ID
     * @param string $appSecret 应用密钥
     * @param string $baseUrl API 基础 URL
     */
    public function __construct(string $aid, string $appSecret, string $baseUrl = 'https://api.fpapi.com')
    {
        $this->aid = $aid;
        $this->appSecret = $appSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false,
        ]);
    }

    /**
     * 生成 HMAC-SHA256 签名。
     *
     * @param string $queryStr 查询字符串
     * @param  $body 请求体
     * @param string $nonceStr 随机字符串
     * @param int $timestamp 时间戳
     * @return array 包含签名的请求头
     */
    private function generateSignature(string $queryStr, $body, string $nonceStr, int $timestamp): array
    {
        $queryHash = hash_hmac('SHA256', $queryStr, $this->appSecret);
        if ($body) {
            $bodyStr = json_encode($body);
        } else {
            $bodyStr = '';
        }
        $bodyHash = hash_hmac('SHA256', $bodyStr, $this->appSecret);

        $originStr = "app_secret={$this->appSecret}\n"
            . "body={$bodyHash}\n"
            . "nonce_str={$nonceStr}\n"
            . "query={$queryHash}\n"
            . "timestamp={$timestamp}";

        $sign = hash_hmac('SHA256', $originStr, $this->appSecret);

        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-FP-NonceStr' => $nonceStr,
            'X-FP-Timestamp' => $timestamp,
            'Authorization' => "FP-API-SIGN-HMAC-SHA256 {$sign}",
        ];
    }

    /**
     * 生成随机字符串。
     *
     * @param int $length 字符串长度
     * @return string 随机字符串
     */
    private function generateNonceStr(int $length = 8): string
    {
        return substr(str_shuffle('1234567890abcdefghijklmnopqrstuvwxyz'), 0, $length);
    }

    public function getAreaCode($name = '')
    {
        $area = [
            "北京" => 1100,
            "天津" => 1200,
            "河北" => 1300,
            "山西" => 1400,
            "内蒙" => 1500,
            "辽宁" => 2100,
            "大连" => 2102,
            "吉林" => 2200,
            "黑龙江" => 2300,
            "上海" => 3100,
            "江苏" => 3200,
            "浙江" => 3300,
            "宁波" => 3302,
            "安徽" => 3400,
            "福建" => 3500,
            "厦门" => 3502,
            "江西" => 3600,
            "山东" => 3700,
            "青岛" => 3702,
            "河南" => 4100,
            "湖北" => 4200,
            "湖南" => 4300,
            "广东" => 4400,
            "深圳" => 4403,
            "广西" => 4500,
            "海南" => 4600,
            "重庆" => 5000,
            "四川" => 5100,
            "贵州" => 5200,
            "云南" => 5300,
            "西藏" => 5400,
            "陕西" => 6100,
            "甘肃" => 6200,
            "青海" => 6300,
            "宁夏" => 6400,
            "新疆" => 6500
        ];
        if ($name) {
            return $area[$name] ?? '';
        }
        return $area;
    }
}
