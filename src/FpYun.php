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
     * 红冲
     * https://piaozone-ultimate.apifox.cn/api-146003698
     * @param string $serialNo 流水号
     * @param string $invoiceNum 要红冲的蓝票号
     * @param string $invoice_type 1专票 2 普票
     * @param string $sellerTaxpayerId 销方税号
     * @return array $data 发票信息
     */
    public function reverseInvoice($serialNo, $invoiceNum, $invoice_type = 2, $sellerTaxpayerId = '')
    {
        if ($invoice_type == '1') {
            $invoice_type = '01'; //专票
        } elseif ($invoice_type == '2') {
            $invoice_type = '02'; //普票
        }
        $url = $this->baseUrl . '/kapi/app/sim/openApi';
        $body = [
            'serialNo' => $serialNo,
            'invoiceNum' => $invoiceNum,
            'redReason' => '01',
            'invoiceType' => $invoice_type,
            'sellerTaxpayerId' => $sellerTaxpayerId,
        ];
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'access_token' => $this->access_token,
            ],
            'json' => [
                'requestId' => $this->requestId,
                'businessSystemCode' => $this->code,
                'interfaceCode' => 'ALLE.INVOICE.RED',
                'data' => base64_encode(json_encode($body)),
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        if ($status == 1) {
            $data = $res['data'] ?? '';
            return true;
        } else {
            self::$err = $message;
            if ($this->showErr) {
                throw new \Exception(self::$err);
            }
            return false;
        }
    }
    /**
     * 分页查询发票
     * https://piaozone-ultimate.apifox.cn/api-146003725
     * 
     * @param string $tax_no 税号
     * @param int $pageNo 页码，从1开始
     * @param int $pageSize 每页数量
     * @param bool $showFull 是否获取全部数据
     * @return array|false 返回发票数据数组或false(失败时)
     */
    public function findAll($tax_no = '税号', $pageSize = 50)
    {
        $output = [];
        $invoice_type = [1, 2];
        foreach ($invoice_type as $item) {
            $pageNoStatic[$item] = 1;
            $flag = true;
            while ($flag) {
                $result = $this->find($tax_no, $item, $pageNoStatic[$item], $pageSize);
                if ($result) {
                    $output = array_merge($output, $result);
                    $pageNoStatic[$item]++; // 增加页码(专票和普票
                } else {
                    $flag = false;
                }
            }
        }
        return $output;
    }

    /**
     * 分页查询发票
     * @param string $tax_no 税号
     * @param int $invoice_type 发票类型，1：专票，2：普票
     * @param int $pageNo 页码，从1开始
     * @param int $pageSize 每页数量
     */
    public function find($tax_no = '税号', $invoice_type = 2, $pageNo = 1, $pageSize = 50)
    {
        if ($invoice_type == '1') {
            $invoice_type = '01'; //专票
        } elseif ($invoice_type == '2') {
            $invoice_type = '02'; //普票
        }
        $url = $this->baseUrl . '/kapi/app/sim/openApi';
        $pageNo = $pageNo - 1;
        $body = [
            'sellerTaxpayerId' => $tax_no,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'invoiceType' => $invoice_type,
        ];
        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'access_token' => $this->access_token,
            ],
            'json' => [
                'requestId' => $this->requestId,
                'businessSystemCode' => $this->code,
                'interfaceCode' => 'ALLE.BATCH.QUERY',
                'data' => base64_encode(json_encode($body)),
            ]
        ]);
        $res = (string) $response->getBody();
        $res = json_decode($res, true);
        $message = $res['message'] ?? '';
        $status = $res['status'] ?? '';
        if ($status == 1) {
            $data = $res['data'] ?? '';
            $data = base64_decode($data);
            $data = json_decode($data, true);
            $output = [];
            foreach ($data as $key => $item) {
                $output[] = [
                    'url' => $item['invoiceFileUrl'] ?? '',
                    'ofd' => $item['ofdFileUrl'] ?? '',
                    'xml' => $item['xmlFileUrl'] ?? '',
                    'invoice_number' => $item['invoiceNum'] ?? '',
                    'data' => $item,
                ];
            }
            return $output;
        } else {
            self::$err = $message;
            return false;
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
        if ($status == 1) {
            $data = $res['data'] ?? '';
            $data = base64_decode($data);
            $data = json_decode($data, true);
            return [
                'url' => $data['invoiceFileUrl'] ?? '',
                'ofd' => $data['ofdFileUrl'] ?? '',
                'xml' => $data['xmlFileUrl'] ?? '',
                'invoice_number' => $data['invoiceNum'] ?? '',
                'data' => $data,
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
                $lineProperty = 0;
                $discount_amount = $goods['discount_amount'] ?? '';
                if ($discount_amount > 0) {
                    $lineProperty = 2;
                }
                $row = [
                    'goodsName' => $goods['goods_name'],
                    'specification' => $goods['model'] ?? '', //规格型号 
                    'quantity' => $goods['quantity'] ?? 1,
                    'price' => $goods['unit_price'] ?? '',
                    'taxRate' => $goods['tax_rate'] ?? '', //税率
                    'units' => $goods['unit'] ?? '', //单位 
                    'lineProperty' => $lineProperty,
                    'amount' => $goods['amount'] ?? '', //金额 
                ];
                $row['revenueCode'] = $goods['tax_scope_code']; //税收分类编码 
                $new_goods[] = $row;

                if ($discount_amount > 0) {
                    $row['lineProperty'] = 1;
                    $row['amount'] = bcsub(0, $discount_amount, 2);
                    $new_goods[] = $row;
                }
            }
            $invoice_type = $invoice['invoice_type'] ?? '';
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
                'sellerBank' => $invoice['seller_bank'] ?? '', // 收款银行
                'sellerBankAccount' => $invoice['seller_bank_account'] ?? '', // 收款银行账号
                'sellerAddress' => $invoice['seller_address'] ?? '', // 收款方地址
                'invoiceDetail' => $new_goods,
                'buyerRecipientPhone' => $invoice['buyer_tel'] ?? '',
                'buyerRecipientMail' => $invoice['buyer_email'] ?? '',
            ];
            $drawer = $invoice['drawer'] ?? '';
            if ($drawer) {
                $body['drawer'] = $drawer; //开票人
            }
            $remark = $invoice['remark'] ?? '';
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
        if ($status == 1) {
            return $res['data'] ?? '';
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