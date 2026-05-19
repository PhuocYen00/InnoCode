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

const PAYOS_CLIENT_ID = 'dd9a54cf-c6a4-49d9-bf49-a7b1000becb7';
const PAYOS_API_KEY = '6c806a0b-8bef-4fd1-a1c1-9775a5b21be2';
const PAYOS_CHECKSUM_KEY = '8af5ecf3f4a7a2da35f6f88dbd9ca9de2efea81d4bcbb7e26cb7a00562353885';
const PAYOS_API_URL = 'https://api-merchant.payos.vn/v2/payment-requests';

define('PISTON_API_BASE', getenv('PISTON_API_BASE') ?: 'https://emkc.org/api/v2/piston');

const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'phuocyen.281004@gmail.com';
const SMTP_PASSWORD = 'ruvakmmenmimqfjd';
const SMTP_ENCRYPTION = 'tls';

const MAIL_FROM = 'phuocyen.281004@gmail.com';
const MAIL_FROM_NAME = 'InnoCode';

