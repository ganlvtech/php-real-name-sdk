# 网络游戏防沉迷实名认证系统 SDK

[网络游戏防沉迷实名认证系统](https://wlc.nppa.gov.cn/fcm_company/index.html)

注意：本仓库**不是**官方 SDK，不对服务质量做保证

代码使用宽松的 MIT License 发布，您可以在协议允许的范围内正常商用。

## 安装

使用 Composer 安装

```bash
composer require ganlvtech/real-name-sdk
```

## 使用方法

```php
<?php

use GuzzleHttp\Exception\GuzzleException;
use RealNameSdk\BadResponseException;
use RealNameSdk\RealNameSdk;

require __DIR__ . '/vendor/autoload.php';

$sdk = new RealNameSdk('app_id', 'biz_id', 'secret_key');
try {
    $result = $sdk->checkIdCard('张三', '440106198202020555', '1234567');
} catch (GuzzleException $e) {
    return "实名认证失败：网络错误：" . $e->getMessage();
} catch (BadResponseException $e) {
    return "实名认证失败：接口返回格式不正确：" . $e->getMessage();
}
if ($result['errcode'] !== RealNameSdk::ERR_CODE_OK) {
    switch ($result['errcode']) {
        case RealNameSdk::ERR_CODE_BUS_AUTH_IDNUM_ILLEGAL:
            return "实名认证失败：身份证号格式校验失败";
        case RealNameSdk::ERR_CODE_BUS_AUTH_RESOURCE_LIMIT:
            return "实名认证错误：实名认证条目已达上限";
        case RealNameSdk::ERR_CODE_BUS_AUTH_CODE_NO_AUTH_RECODE:
            return "实名认证错误：无该编码提交的实名认证记录";
        case RealNameSdk::ERR_CODE_BUS_AUTH_CODE_ALREADY_IN_USE:
            return "实名认证错误：编码已经被占用";
        default:
            return "实名认证错误：{$result['errmsg']}";
    }
}
switch ($result['data']['result']['status']) {
    case RealNameSdk::CHECK_RESULT_OK:
        return "实名认证成功";
    case RealNameSdk::CHECK_RESULT_PROCESSING:
        return "正在实名认证中";
    case RealNameSdk::CHECK_RESULT_FAILED:
        return "实名认证失败";
}

$result = $sdk->queryIdCardAsyncResult('1234567');
// queryIdCardAsyncResult 和 checkIdCard 接口的返回格式完全一致，可以使用相同方式进行错误处理和结果处理

$result = $sdk->reportUserBehavior([
    [
        'si' => $session_id,
        'bt' => $behavior_type,
        'ot' => $operation_timestamp,
        'ct' => $is_user_checked ? RealNameSdk::CLIENT_TYPE_PLAYER : RealNameSdk::CLIENT_TYPE_DEVICE,
        'di' => $device_id,
        'pi' => $player_identifier,
    ]
]);
if ($result['errcode'] !== RealNameSdk::ERR_CODE_OK) {
    echo '用户行为数据上报错误：' .  $result['errcode'] . ' ' . $result['errmsg']), PHP_EOL;
}
// $session_id 通常为玩家 ID 即可，如果支持多端同时登录的话，可能与 TCP 连接或 access_token 有关
// $behavior_type 为 BEHAVIOR_TYPE_LOGOUT BEHAVIOR_TYPE_LOGIN 下线上限
// 其他参数请参考 reportUserBehavior 的注释说明
// 这个接口仅上报，没有有意义的返回，也不会对用户造成影响，通常无需检查返回结果。不过也应当监测返回结果是否成功，避免系统变更时（例如重置 secret_key 等操作）实名认证异常。
```

## 常见错误

### cURL error 60: SSL certificate problem: unable to get local issuer certificate

方法一：下载证书，并修改 php.ini

1. 从 cURL 官网[下载最新的 cacert.pem](https://curl.haxx.se/ca/cacert.pem)

2. 修改 php.ini，将 `curl.cainfo` 改为下载路径

    ```ini
    curl.cainfo="/path/to/downloaded/cacert.pem"
    ```

参考资料: https://stackoverflow.com/questions/24611640/curl-60-ssl-certificate-problem-unable-to-get-local-issuer-certificate/31830614#31830614

方法二：下载证书，并使用自定义 `GuzzleHttp\Client`

```php
<?php
use GuzzleHttp\Client;
$client = new Client([
    'verify' => '/path/to/downloaded/cacert.pem',
]);
$sdk = new RealNameSdk('app_id', 'biz_id', 'secret_key', $client);
```

另外一种方法（不推荐）是，关闭 cURL 的证书校验功能（不安全，通常用于测试环境，或者系统时间被修改的环境）

```php
<?php
use GuzzleHttp\Client;
$client = new Client([
    'verify' => false,
]);
$sdk = new RealNameSdk('app_id', 'biz_id', 'secret_key', $client);
```

