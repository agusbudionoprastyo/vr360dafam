<?php
$data = $_POST;
$secretWord = '';
$saleId = $data['sale_id'];
$invoiceId = $data['invoice_id'];
$vendorId = $data['vendor_id'];
$parameters = [
    $saleId,
    $invoiceId,
    $vendorId,
    $secretWord
];
$hash = strtoupper(md5(implode($parameters)));

if($hash === $data['md5_hash']) {
    //file_put_contents(realpath(dirname(__FILE__))."/log_2checkout.txt",'OK: '.serialize($_POST).PHP_EOL,FILE_APPEND);
} else {
    //file_put_contents(realpath(dirname(__FILE__))."/log_2checkout.txt",'NO: '.$hash." ".$data['md5_hash'].PHP_EOL,FILE_APPEND);
}

http_response_code(200);