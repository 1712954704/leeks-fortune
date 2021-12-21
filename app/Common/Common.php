<?php

/**
 * 字符串两次md5加密
 * @param $str 要加密的字符串
 */
function double_md5($str)
{
    return md5(md5(trim($str)));
}

/**
 * 发送curl请求
 * @param string $url  url
 * @param string $code 区分请求类型
 * @param array $data 需要发送的数据
 * @param array $headerArray 请求头设置
 */
function send($url, $data = [], $code = 'get', $headerArray = array("Authorization:Basic cHJpbnRlcmZhY2U6cFIyMEBP", "Content-type:application/json", "ContentCharset:UFT-8"), $is_json = 1)
{
    $ch = curl_init();   //    初始化一个cURL会话
    curl_setopt($ch, CURLOPT_URL, $url);    // 设置url地址
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  // 禁止curl验证对等证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);   // cURL将终止从服务端进行验证

    switch ($code) {
        case "post":
            if ($is_json) {
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            break;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);   // 设置请求头
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 设置返回格式

    $output = curl_exec($ch);   // 执行一个curl会话
    curl_close($ch);            // 关闭一个curl会话
    return json_decode($output, JSON_UNESCAPED_UNICODE);
}