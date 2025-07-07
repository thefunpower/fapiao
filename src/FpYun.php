<?php

namespace Fapiao;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 金蝶云发票
 * https://cosmic-sandbox.piaozone.com/
 * https://piaozone-ultimate.apifox.cn/doc-3655357
 */
class FpYun
{
    public $user = '';
    public $appId = '';
    public $accountId = '';
    public $appSecret = '';
    public $baseUrl = '';
    public $code = '';
    public $requestId = '';

    private $client;
    public static $err = '';
    private $apptoken;
    public $access_token;
    public $serialNo;
    public $showErr = true;

    public function __construct()
    {
        $this->requestId = time() . mt_rand(100000, 999999);
        $this->client = new Client([
            'timeout' => 10,
            'verify' => false,
        ]);
    }

    public function set($arr)
    {
        foreach ($arr as $key => $value) {
            $this->$key = $value;
        }
    }
    /**
     * 查询发票
     * https://piaozone-ultimate.apifox.cn/api-146003723
     * @param string $serialNo 流水号
     * @param string $saler_tax_no 销方税号
     * @return array $data 发票信息
     */
    public function query($serialNo, $saler_tax_no)
    {
        $url = $this->baseUrl . '/kapi/app/sim/openApi';
        $body = [
            'serialNo' => $serialNo,
            'sellerTaxpayerId' => $saler_tax_no,
        ];
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'access_token' => $this->access_token,
            ],
            'json' => [
                'requestId' => $this->requestId,
                'businessSystemCode' => $this->code,
                'interfaceCode' => 'ALLE.INVOICE.QUERY',
                'data' => base64_encode(json_encode($body)),
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        pr($res);
        exit;
        if ($status == 1) {
            $this->serialNo = $res['data'] ?? '';
            return $this->serialNo;
        } else {
            self::$err = $message;
            if ($this->showErr) {
                throw new \Exception(self::$err);
            }
            return false;
        }
    }
    /**
     * 创建发票
     * 返回流水号
     * https://piaozone-ultimate.apifox.cn/api-149332334
     * @param array $invoices 
     */
    public function createInvoice($invoices, $showMore = false)
    {
        $url = $this->baseUrl . '/kapi/app/sim/openApi';
        foreach ($invoices as $invoice) {
            $new_goods = [];
            foreach ($invoice['goods'] as $goods) {
                /**
                 * 行性质，0：正常商品行；1：折扣行[折扣行金额需为负数，它的上一行必须是被折扣行]；2：被折扣行[此商品行下一行必须是折扣行]【长度：2】
                 */
                $lineProperty = 2; 
                $row = [
                    'goodsName' => $goods['goods_name'],
                    'specification' => $goods['model'] ?? '', //规格型号 
                    'quantity' => $goods['quantity'] ?? 1,
                    'price' => $goods['unit_price'] ?? '',
                    'taxRate' => $goods['tax_rate'] ?? '', //税率
                    'units' => $goods['unit'] ?? '', //单位 
                    'lineProperty' => $lineProperty, //行性质，1折扣行(折扣行必须紧跟被折扣的正常商品行)，2正常商品行 
                    'amount' => $goods['amount']?? '', //金额 
                   
                ];  
                $row['revenueCode'] = $goods['tax_scope_code']; //税收分类编码 
                $new_goods[] = $row;
            }
            $invoice_type = $invoice['invoice_type']?? '';
            if ($invoice_type == '1') {
                $invoice_type = '01'; //专票
            } elseif ($invoice_type == '2') {
                $invoice_type = '02'; //普票
            }
            $body = [
                'serialNo' => $invoice['custom_invoice_no'], 
                'invoiceType' => $invoice_type, // 01-数字化电子专票，02-数字化电子普票 
                'buyerName' => $invoice['buyer_name'],
                'buyerTaxpayerId' => $invoice['buyer_tax_no'] ?? '',
                'sellerName' => $invoice['seller_name'] ?? '',
                'sellerTaxpayerId' => $invoice['seller_tax_no'] ?? '', 
                'sellerBank'=> $invoice['seller_bank']?? '', // 收款银行
                'sellerBankAccount'=> $invoice['seller_bank_account']?? '', // 收款银行账号
                'sellerAddress'=> $invoice['seller_address']?? '', // 收款方地址
                'invoiceDetail' => $new_goods,
                'buyerTel'=> $goods['buyer_tel']?? '', //购买方电话
                'buyerAddress'=> $goods['buyer_address']?? '', //购买方地址 
            ];
            $drawer = $invoice['drawer']?? '';
            if ($drawer) {
                $body['drawer'] = $drawer; //开票人
            }
            $remark = $invoice['remark']?? '';
            if ($remark) {
                $body['remark'] = $remark;
            }
            $reviewer = $invoice['reviewer'] ?? '';
            if ($reviewer) {
                $body['reviewer'] = $reviewer; //复核人
            } 
        } 
        
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'access_token' => $this->access_token,
            ],
            'json' => [
                'requestId' => $this->requestId,
                'businessSystemCode' => $this->code,
                'interfaceCode' => 'ALLE.INVOICE.OPEN',
                'data' => base64_encode(json_encode($body)),
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        pr($res);
        exit;
        if ($status == 1) {
            $this->serialNo = $res['data'] ?? '';
            return $this->serialNo;
        } else {
            self::$err = $message;
            if ($this->showErr) {
                throw new \Exception(self::$err);
            }
            return false;
        }
    }
    /**
     * 获取access_token
     */
    public function getAccessToken()
    {
        $this->getAppToken();
        $url = $this->baseUrl . '/api/login.do';
        $response = $this->client->request('POST', $url, [
            'json' => [
                'apptoken' => $this->apptoken,
                'accountId' => $this->accountId,
                'user' => $this->user,
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $access_token = $res['data']['access_token'] ?? '';
        $expire_time = $res['data']['expire_time'] ?? '';
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        if ($status == 1) {
            $this->access_token = $access_token;
            return [
                'access_token' => $access_token,
                'expire_time' => $expire_time,
            ];
        } else {
            self::$err = $message;
            if ($this->showErr) {
                throw new \Exception(self::$err);
            }
            return false;
        }
    }
    /**
     * 获取apptoken
     */
    public function getAppToken()
    {
        $url = $this->baseUrl . '/api/getAppToken.do';
        $response = $this->client->request('POST', $url, [
            'json' => [
                'appId' => $this->appId,
                'accountId' => $this->accountId,
                'appSecret' => $this->appSecret,
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $app_token = $res['data']['app_token'] ?? '';
        $expire_time = $res['data']['expire_time'] ?? '';
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        if ($status == 1) {
            $this->apptoken = $app_token;
            return [
                'app_token' => $app_token,
                'expire_time' => $expire_time,
            ];
        } else {
            self::$err = $message;
            if ($this->showErr) {
                throw new \Exception(self::$err);
            }
            return false;
        }
    }
}
