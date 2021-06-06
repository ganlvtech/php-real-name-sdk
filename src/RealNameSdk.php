<?php

namespace RealNameSdk;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class RealNameSdk
{
    const CIPHER = 'aes-128-gcm';
    /** @var string 实名认证接口 */
    const CHECK_ID_CARD_URL = 'https://api.wlc.nppa.gov.cn/idcard/authentication/check';
    /** @var string 实名认证结果查询接口 */
    const QUERY_ID_CARD_ASYNC_RESULT_URL = 'http://api2.wlc.nppa.gov.cn/idcard/authentication/query';
    /** @var string 游戏用户行为数据上报接口 */
    const REPORT_USER_BEHAVIOR_URL = 'http://api2.wlc.nppa.gov.cn/behavior/collection/loginout';
    /** @var int 实名认证接口限流 */
    const CHECK_ID_CARD_QPS_LIMIT = 100;
    /** @var int 实名认证结果查询接口限流 */
    const QUERY_ID_CARD_ASYNC_RESULT_QPS_LIMIT = 300;
    /** @var int 游戏用户行为数据上报接口限流 */
    const REPORT_USER_BEHAVIOR_QPS_LIMIT = 10;
    /** @var int 游戏用户行为数据上报接口每次上报数量限制 */
    const REPORT_USER_BEHAVIOR_COLLECTION_LIMIT = 128;
    /** @var int 游戏用户行为数据：用户行为类型：下线 */
    const BEHAVIOR_TYPE_LOGOUT = 0;
    /** @var int 游戏用户行为数据：用户行为类型：上线 */
    const BEHAVIOR_TYPE_LOGIN = 1;
    /** @var int 游戏用户行为数据：上报类型：已认证通过用户 */
    const CLIENT_TYPE_PLAYER = 0;
    /** @var int 游戏用户行为数据：上报类型：游客用户 */
    const CLIENT_TYPE_DEVICE = 2;
    /** @var int 实名认证结果：认证成功 */
    const CHECK_RESULT_OK = 0;
    /** @var int 实名认证结果：认证中 */
    const CHECK_RESULT_PROCESSING = 1;
    /** @var int 实名认证结果：认证失败 */
    const CHECK_RESULT_FAILED = 2;

    // region 系统异常
    /** @var int 请求成功 */
    const ERR_CODE_OK = 0;
    /** @var int 系统错误 */
    const ERR_CODE_SYS_ERROR = 1001;
    /** @var int 接口请求的资源不存在 */
    const ERR_CODE_SYS_REQ_RESOURCE_NOT_EXIST = 1002;
    /** @var int 接口请求方式错误 */
    const ERR_CODE_SYS_REQ_METHOD_ERROR = 1003;
    /** @var int 接口请求核心参数缺失 */
    const ERR_CODE_SYS_REQ_HEADER_MISS_ERROR = 1004;
    /** @var int 接口请求IP地址非法 */
    const ERR_CODE_SYS_REQ_IP_ERROR = 1005;
    /** @var int 接口请求超出流量限制 */
    const ERR_CODE_SYS_REQ_BUSY_ERROR = 1006;
    /** @var int 接口请求过期 */
    const ERR_CODE_SYS_REQ_EXPIRE_ERROR = 1007;
    /** @var int 接口请求方身份非法 */
    const ERR_CODE_SYS_REQ_PARTNER_ERROR = 1008;
    /** @var int 接口请求方权限未启用 */
    const ERR_CODE_SYS_REQ_PARTNER_AUTH_DISABLE = 1009;
    /** @var int 接口请求方无该接口权限 */
    const ERR_CODE_SYS_REQ_AUTH_ERROR = 1010;
    /** @var int 接口请求方身份核验错误 */
    const ERR_CODE_SYS_REQ_PARTNER_AUTH_ERROR = 1011;
    /** @var int 接口请求报文核验失败 */
    const ERR_CODE_SYS_REQ_PARAM_CHECK_ERROR = 1012;
    // endregion
    // region 接口测试业务异常
    /** @var int 测试系统错误 */
    const ERR_CODE_TEST_SYS_ERROR = 4001;
    /** @var int 测试任务不存在 */
    const ERR_CODE_TEST_TASK_NOT_EXIST = 4002;
    /** @var int 测试参数无效 */
    const ERR_CODE_TEST_PARAM_INVALID_ERROR = 4003;
    // endregion
    // region 实名认证业务异常
    /** @var int 身份证号格式校验失败 */
    const ERR_CODE_BUS_AUTH_IDNUM_ILLEGAL = 2001;
    /** @var int 实名认证条目已达上限 */
    const ERR_CODE_BUS_AUTH_RESOURCE_LIMIT = 2002;
    /** @var int 无该编码提交的实名认证记录 */
    const ERR_CODE_BUS_AUTH_CODE_NO_AUTH_RECODE = 2003;
    /** @var int 编码已经被占用 */
    const ERR_CODE_BUS_AUTH_CODE_ALREADY_IN_USE = 2004;
    // endregion
    // region 游戏用户行为数据上报业务异常
    /** @var int 行为数据部分上报失败 */
    const ERR_CODE_BUS_COLL_PARTIAL_ERROR = 3001;
    /** @var int 行为数据为空 */
    const ERR_CODE_BUS_COLL_BEHAVIOR_NULL_ERROR = 3002;
    /** @var int 行为数据超出条目数量限制 */
    const ERR_CODE_BUS_COLL_OVER_LIMIT_COUNT = 3003;
    /** @var int 行为数据编码错误 */
    const ERR_CODE_BUS_COLL_NO_INVALID = 3004;
    /** @var int 行为发生时间错误 */
    const ERR_CODE_BUS_COLL_BEHAVIOR_TIME_ERROR = 3005;
    /** @var int 用户类型无效 */
    const ERR_CODE_BUS_COLL_PLAYER_MODE_INVALID = 3006;
    /** @var int 行为类型无效 */
    const ERR_CODE_BUS_COLL_BEHAVIOR_MODE_INVALID = 3007;
    /** @var int 缺失PI（用户唯一标识）值 */
    const ERR_CODE_BUS_COLL_PLAYERID_MISS = 3008;
    /** @var int 缺失DI（设备标识）值 */
    const ERR_CODE_BUS_COLL_DEVICEID_MISS = 3009;
    /** @var int PI（用户唯一标识）值无效 */
    const ERR_CODE_BUS_COLL_PLAYERID_INVALID = 3010;
    // endregion

    /** @var string */
    public $app_id;
    /** @var string */
    public $biz_id;
    /** @var string */
    public $secret_key;
    /** @var \GuzzleHttp\Client */
    public $client;

    public function __construct(string $app_id, string $biz_id, string $secret_key, ?Client $client = null)
    {
        if (!in_array(self::CIPHER, openssl_get_cipher_methods())) {
            throw new RuntimeException('openssl cipher not support: ' . self::CIPHER);
        }
        if ($client === null) {
            $client = new Client();
        }
        $this->app_id = $app_id;
        $this->biz_id = $biz_id;
        $this->secret_key = $secret_key;
        $this->client = $client;
    }

    protected function encrypt(string $data)
    {
        $cipher = self::CIPHER;
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $secret_key_bin = hex2bin($this->secret_key);
        $encrypt_data = openssl_encrypt($data, $cipher, $secret_key_bin, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $encrypt_data . $tag);
    }

    protected function decrypt(string $data): ?string
    {
        $r = base64_decode($data);
        $cipher = 'aes-128-gcm';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = substr($r, 0, $ivlen);
        $tag = substr($r, -16);
        $ciphertext = substr($r, $ivlen, -16);
        $secret_key_bin = hex2bin($this->secret_key);
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $secret_key_bin, OPENSSL_RAW_DATA, $iv, $tag);
        if ($original_plaintext === false) {
            return null;
        }
        return $original_plaintext;
    }

    /**
     * 获取当前时间戳（秒）
     * 
     * 如果需要测试时间，可以重写此方法
     * 
     * @return int|float
     */
    protected function getTimestamp()
    {
        // 通常返回秒级时间戳即可，无需使用 microtime(true)
        return time();
    }

    protected function headers(): array
    {
        return [
            'appId' => $this->app_id,
            'bizId' => $this->secret_key,
            'timestamps' => (int)(1000 * $this->getTimestamp()),
        ];
    }

    protected function sign(array $headers, array $query, string $body)
    {
        $data = array_merge($headers, $query);
        ksort($data);
        $string_parts = [];
        foreach ($data as $key => $value) {
            if ($key !== 'sign' && $key != 'Content-Type') {
                $string_parts[] = $key . $value;
            }
        }
        $string = implode('', $string_parts);
        $string = $this->secret_key . $string . $body;
        return hash('sha256', $string);
    }

    /**
     * 通用请求接口
     *
     * @param string $method
     * @param string $uri
     * @param array $query
     * @param array $data
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $method, string $uri, array $query = [], array $data = []): ResponseInterface
    {
        $headers = $this->headers();
        $body = json_encode($data);
        $encrypted_body = $this->encrypt($body);
        $json_body = json_encode(['data' => $encrypted_body]);
        $headers['sign'] = $this->sign($headers, $query, $json_body);
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return $this->client->request($method, $uri, [
            'headers' => $headers,
            'query' => $query,
            'body' => $json_body,
            'connect_timeout' => 5000,
            'timeout' => 5000,
        ]);
    }

    /**
     * 实名认证接口
     *
     * 网络游戏用户实名认证服务接口，面向已经接入网络游戏防沉迷实名认证系统的游戏运营单位提供服务，游戏运营单位调用该接口进行用户实名认证工作，本版本仅支持大陆地区的姓名和二代身份证号核实认证。
     *
     * 接口说明
     * 1. 接口调用地址：https://api.wlc.nppa.gov.cn/idcard/authentication/check
     * 2. 接口请求方式：POST
     * 3. 接口理论响应时间：300ms
     * 4. 报文超时时间（TIMESTAMPS）：5s
     * 5. 客户端接口超时时间（建议）：5s
     * 6. 接口限流：100 QPS（超出后会被限流 1 分钟）
     *
     * 备注
     * 1. 实名认证接口返回包括两种情况，可以立即返回实名认证结果和无法立即返回实名认证结果；无法立即返回实名认证结果的情况，可以通过实名认证结果查询接口查询，调用实名认证接口无法获得查询结果。
     * 2. 无法立即返回实名认证结果的实名认证请求，可以在 48 小时之内查询结果，如果 48 小时之内无法查询到结果，请联系系统管理员处理。
     * 3. 实名认证结果查询接口中，ai 值所关联的实名认证结果被查询成功后，该结果将在 300 秒之后被删除，被删除的结果将无法被再次查询。
     * 4. 未被删除的 ai 值在实名认证接口中不可以被重复使用。
     *
     * @param string $id_card 用户身份证号码。游戏用户身份证号码（实名信息）
     * @param string $name 用户姓名。游戏用户姓名（实名信息）
     * @param string $ai 游戏内部成员标识。本次实名认证行为在游戏内部对应的唯一标识，该标识将作为实名认证结果查询的唯一依据。备注：不同企业的游戏内部成员标识有不同的字段长度，对于超过 32 位的建议使用哈希算法压缩，不足 32 位的建议按企业自定规则补齐
     *
     * @return array { errcode: int,       // 状态码
     *                 errmsg: string,     // 状态描述
     *                 data: {             // 响应结果
     *                   result: {         // 响应结果内容
     *                     status: int,    // 实名认证结果。认证结果：0：认证成功，1：认证中，2：认证失败。
     *                     pi: string      // 用户唯一标识：已通过实名认证用户的唯一标识
     *                   }
     *                 }
     *               }
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RealNameSdk\BadResponseException
     * @see queryIdCardAsyncResult
     */
    public function checkIdCard(string $id_card, string $name, string $ai): array
    {
        $response = $this->request('POST', self::CHECK_ID_CARD_URL, [], [
            'ai' => $ai,
            'name' => $name,
            'idNum' => $id_card,
        ]);
        $body = (string)$response->getBody();
        if (!$body) {
            throw new BadResponseException('返回 Body 为空', $response);
        }
        $data = json_decode($body, true);
        if (!$data) {
            throw new BadResponseException('返回 Body json 解析失败 [' . json_last_error() . ']' . json_last_error_msg(), $response);
        }
        if (!(is_array($data)
            && isset($data['errcode'])
            && isset($data['errmsg'])
            && is_int($data['errcode'])
            && is_string($data['errmsg']))) {
            throw new BadResponseException('返回 Body json 缺少 errcode 或 errmsg', $response);
        }
        if ($data['errcode'] === self::ERR_CODE_OK) {
            if (!(isset($data['data'])
                && is_array($data['data'])
                && isset($data['data']['result'])
                && is_array($data['data']['result']))) {
                throw new BadResponseException('返回 Body json 缺少 data.result', $response);
            }
            if (!(isset($data['data']['result']['status'])
                && isset($data['data']['result']['pi'])
                && is_int($data['data']['result']['status'])
                && is_string($data['data']['result']['pi']))) {
                throw new BadResponseException('返回 Body json 缺少 data.result.status 或 data.result.pi', $response);
            }
        }
        return $data;
    }

    /**
     * 实名认证结果查询接口
     *
     * 网络游戏用户实名认证结果查询服务接口，面向已经提交用户实名认证且没有返回结果的游戏运营单位提供服务，游戏运营单位可以调用该接口，查询已经提交但未返回结果用户的实名认证结果。
     *
     * 接口说明
     * 1. 接口调用地址：http://api2.wlc.nppa.gov.cn/idcard/authentication/query
     * 2. 接口请求方式：GET
     * 3. 接口理论响应时间：300ms
     * 4. 报文超时时间（TIMESTAMPS）：5s
     * 5. 客户端接口超时时间（建议）：5s
     * 6. 接口限流：300 QPS（超出后会被限流 1 分钟）
     *
     * 备注
     * 1. 实名认证结果查询接口中，ai 值所关联的实名认证结果被查询成功后，该结果将在 300 秒之后被删除，被删除的结果将无法被再次查询。
     * 2. 未被删除的 ai 值所关联的实名认证结果可以被多次查询。
     *
     * @param string $ai 游戏内部成员标识。本次实名认证行为在游戏内部对应的唯一标识，该标识将作为实名认证结果查询的唯一依据
     *
     * @return array { errcode: int,       // 状态码
     *                 errmsg: string,     // 状态描述
     *                 data: {             // 响应结果
     *                   result: {         // 响应结果内容
     *                     status: int,    // 实名认证结果。认证结果：0：认证成功，1：认证中，2：认证失败。
     *                     pi: string      // 用户唯一标识：已通过实名认证用户的唯一标识
     *                   }
     *                 }
     *               }
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RealNameSdk\BadResponseException
     */
    public function queryIdCardAsyncResult(string $ai): array
    {
        $response = $this->request('GET', self::QUERY_ID_CARD_ASYNC_RESULT_URL, [
            'ai' => $ai,
        ]);
        $body = (string)$response->getBody();
        if (!$body) {
            throw new BadResponseException('返回 Body 为空', $response);
        }
        $data = json_decode($body, true);
        if (!$data) {
            throw new BadResponseException('返回 Body json 解析失败 [' . json_last_error() . ']' . json_last_error_msg(), $response);
        }
        if (!(is_array($data)
            && isset($data['errcode'])
            && isset($data['errmsg'])
            && is_int($data['errcode'])
            && is_string($data['errmsg']))) {
            throw new BadResponseException('返回 Body json 缺少 errcode 或 errmsg', $response);
        }
        if ($data['errcode'] === self::ERR_CODE_OK) {
            if (!(isset($data['data'])
                && is_array($data['data'])
                && isset($data['data']['result'])
                && is_array($data['data']['result']))) {
                throw new BadResponseException('返回 Body json 缺少 data.result', $response);
            }
            if (!(isset($data['data']['result']['status'])
                && isset($data['data']['result']['pi'])
                && is_int($data['data']['result']['status'])
                && is_string($data['data']['result']['pi']))) {
                throw new BadResponseException('返回 Body json 缺少 data.result.status 或 data.result.pi', $response);
            }
        }
        return $data;
    }

    /**
     * 游戏用户行为数据上报接口
     *
     * 游戏用户行为数据上报接口，面向已经接入网络游戏防沉迷实名认证系统的游戏运营单位提供服务，游戏运营单位调用该接口上报游戏用户上下线行为数据。
     *
     * 接口说明
     * 1. 接口调用地址：http://api2.wlc.nppa.gov.cn/behavior/collection/loginout
     * 2. 接口请求方式：POST
     * 3. 接口理论响应时间：300ms
     * 4. 报文超时时间（TIMESTAMPS）：5s
     * 5. 客户端接口超时时间（建议）：5s
     * 6. 接口限流：10 QPS（超出后会被限流 1 分钟）
     *
     * 备注
     * 1. 接口支持长/短连接，单 IP 连接数上限为 1000，若在某连接 120 秒以内没有接口调用，服务端会主动销毁连接。
     * 2. 每次接口调用中最多接受单次 128 条行为数据上报。
     * 3. 每一组行为数据中的最早行为发生时间（collections[n].ot）与数据上报时间（timestamps）差值应小于180秒，同时最晚行为发生时间（collections[n].ot）应早于数据上报时间（timestamps）。
     *
     * @param array[] $collections { no: int,    // [必填] 条目编码。在批量模式中标识一条行为数据，取值范围 1-128
     *                               si: string, // [必填] 会话标识，最长 32 字节。一个会话标识只能对应唯一的实名用户，一个实名用户可以拥有多个会话标识；同一用户单次游戏会话中，上下线动作必须使用同一会话标识上报备注：会话标识仅标识一次用户会话，生命周期仅为一次上线和与之匹配的一次下线，不会对生命周期之外的任何业务有任何影响
     *                               bt: int,    // [必填] 用户行为类型。游戏用户行为类型。0：下线，1：上线
     *                               ot: int,    // [必填] 行为发生时间戳。行为发生时间戳，单位秒
     *                               ct: int,    // [必填] 上报类型。用户行为数据上报类型。0：已认证通过用户，2：游客用户
     *                               di: string, // [di pi 二选一] 设备标识。游客模式设备标识，由游戏运营单位生成，游客用户下必填
     *                               pi: string, // [di pi 二选一] 用户唯一标识。已通过实名认证用户的唯一标识，已认证通过用户必填
     *                             }[]
     *
     * @return array { errcode: int,       // 状态码
     *                 errmsg: string,     // 状态描述
     *                 data: {             // 响应结果
     *                   results: {        // 响应结果列表
     *                     no: int,        // 条目编码
     *                     errcode: int,   // 状态码
     *                     errmsg: string, // 状态描述
     *                   }[]
     *                 }
     *               }
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RealNameSdk\BadResponseException
     */
    public function reportUserBehavior(array $collections): array
    {
        $response = $this->request('GET', self::REPORT_USER_BEHAVIOR_URL, [], [
            'collections' => $collections,
        ]);
        $body = (string)$response->getBody();
        if (!$body) {
            throw new BadResponseException('返回 Body 为空', $response);
        }
        $data = json_decode($body, true);
        if (!$data) {
            throw new BadResponseException('返回 Body json 解析失败 [' . json_last_error() . ']' . json_last_error_msg(), $response);
        }
        if (!(is_array($data)
            && isset($data['errcode'])
            && isset($data['errmsg'])
            && is_int($data['errcode'])
            && is_string($data['errmsg']))) {
            throw new BadResponseException('返回 Body json 缺少 errcode 或 errmsg', $response);
        }
        if ($data['errcode'] === self::ERR_CODE_OK) {
            if (!(isset($data['data'])
                && is_array($data['data'])
                && isset($data['data']['results'])
                && is_array($data['data']['results']))) {
                throw new BadResponseException('返回 Body json 缺少 data.results', $response);
            }
            foreach ($data['data']['results'] as $i => $result) {
                if (!(is_array($result)
                    && isset($result['no'])
                    && isset($result['errcode'])
                    && isset($result['errmsg'])
                    && is_int($result['no'])
                    && is_int($result['errcode'])
                    && is_string($result['errmsg']))) {
                    throw new BadResponseException("返回 Body json data.results[$i] 缺少 no, errcode 或 errmsg", $response);
                }
            }
        }
        return $data;
    }
}
