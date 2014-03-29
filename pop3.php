<?php
/**
 * pop3.php
 * POP3より取得したメールを一括でファイルに保存する
 *
 * 起動方法:
 * php pop3.php
 */

require_once('spyc/spyc.php');

$list = array(
    array(
        'server' => "imap.gmail.com",
        'port' => 993, // ポート番号
        'account' => "****@gmail.com", // メールアカウント
        'password' => "****n" // パスワード
    )
);

foreach ($list as $info) {
    fetch_mail_list($info);
}

/**
 * fetch_mail_list
 *
 * @param array $info
 */
function fetch_mail_list($info)
{
    $server = $info['server'];
    $port = $info['port'];
    $account = $info['account'];
    $password = $info['password'];

    // 受信
    // メールサーバ接続
    if (($mbox = imap_open("{".$server.":".$port."/novalidate-cert/imap/ssl}INBOX", $account, $password)) == false) {
        echo "メールサーバに接続できない、またはアカウントorパスワードが間違っています。";

        return false;
    }

    // メールボックスチェック
    $mboxes = imap_check($mbox);

    // メールボックスにあるメッセージ数
    $msg_number = imap_num_msg($mbox);

    // 取得したメールの数
    $my_mail_number = 0;

    //  while ($msg_number > 0) {
    while ($my_mail_number < 10) {
        check_message($mbox, $msg_number, $server);
        $msg_number -= 1;
        $my_mail_number += 1;
    }
    imap_close($mbox);
}

/**
 * check_message
 *
 * @param resource $mbox
 * @param int $msg_number
 * @param string $server
 */
function check_message($mbox, $msg_number, $server)
{
    // メール $msg_number 番目取得
    $header = imap_fetch_overview($mbox, $msg_number);

    $set_state = $header[0];

    // 内部文字エンコーディングをUTF-8に指定
    mb_internal_encoding("UTF-8");

    // 正規表現
    $pattern = "/\n|\r|\s/";
    $replacement = "";

    $array_header = array(
        'subject' => mb_ereg_replace($pattern, $replacement, mb_decode_mimeheader($set_state->subject)),
        'from' => mb_ereg_replace($pattern, $replacement, mb_decode_mimeheader($set_state->from)),
        'to' => mb_ereg_replace($pattern, $replacement, mb_decode_mimeheader($set_state->to)),
        'date' => mb_ereg_replace($pattern, $replacement, mb_decode_mimeheader($set_state->date)),
    );

    $body = imap_body($mbox, $msg_number, FT_INTERNAL);
    $utf8_body = mb_convert_encoding($body, 'UTF-8', 'ISO-2022-JP');

    $data = array(
        'header' => $array_header,
        'body'   => $utf8_body
    );

    // YAMLダンプ
    // $yamldata = spyc::YAMLDump($data);

    // ファイル名
    $sFileName = sprintf("pop_%s%s.txt", $server, $msg_number);

    // ファイルパス
    $sPath = '/vagrant/php/data/' . $sFileName;

    // ファイルの存在確認
    if (file_exists($sPath)) {
        echo "ファイル" . $sPath . "は既に存在します\n";
        return;
    }

    // ファイルへの書き込み
    // if (!file_put_contents($sPath, $yamldata)) {
    if (!file_put_contents($sPath, $utf8_body)) {
        echo "ファイル書き込み失敗\n";
        return;
    }

    // ファイルのパーミッションの変更
    if (!chmod($sPath, 0777)) {
        echo "ファイルパーミッション失敗\n";
        return;
    }
}