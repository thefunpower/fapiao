<?php

namespace Fapiao;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * FpApi 类用于处理带有 HMAC-SHA256 签名认证的 API 请求。
 */
class FpApi
{
    private $aid;
    private $appSecret;
    private $baseUrl;
    private $client;

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
    public function createCompany(array $body, ?string $queryStr = '', ?int $timestamp = null, ?string $nonceStr = null): array
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
            $httpCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();
            return [$httpCode, $responseBody];
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
     * @param array $body 请求体
     * @param string $nonceStr 随机字符串
     * @param int $timestamp 时间戳
     * @return array 包含签名的请求头
     */
    private function generateSignature(string $queryStr, array $body, string $nonceStr, int $timestamp): array
    {
        $queryHash = hash_hmac('SHA256', $queryStr, $this->appSecret);
        $bodyStr = json_encode($body);
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

    public function getArea($name = '上海'){
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
        if($name)  {
            return $area[$name]?? '';
        }
        return $area;
    }
    
}