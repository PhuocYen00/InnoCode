<?php

declare(strict_types=1);

const APP_NAME = 'InnoCode';

$folderName = basename(dirname(__DIR__));
define('APP_URL', 'http://localhost/' . $folderName);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'codemarket';
const DB_USER = 'root';
const DB_PASS = '';

const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin123';

const BANK_CODE = '970436';
const BANK_NAME = 'Vietcombank';
const BANK_ACCOUNT_NUMBER = '3392604697';
const BANK_ACCOUNT_NAME = 'TRAN PHUOC YEN';

const VNPAY_TMN_CODE = '';
const VNPAY_HASH_SECRET = '';
const VNPAY_URL = 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';

const MOMO_PARTNER_CODE = '';
const MOMO_ACCESS_KEY = '';
const MOMO_SECRET_KEY = '';
const MOMO_ENDPOINT = 'https://test-payment.momo.vn/v2/gateway/api/create';

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'phuocyen.281004@gmail.com';
const SMTP_PASSWORD = 'ruvakmmenmimqfjd';
const SMTP_ENCRYPTION = 'tls';

const MAIL_FROM = 'phuocyen.281004@gmail.com';
const MAIL_FROM_NAME = 'InnoCode';

