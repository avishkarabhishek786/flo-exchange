<?php

/** NOTE: The session values must match DB values of users table */

/*Change these values according to your configurations*/

define("DB_HOST", "localhost");
define("DB_NAME", "YOUR DB NAME");
define("DB_USER", "DB USER NAME");
define("DB_PASS", "DB PASSWORD");
define("MESSAGE_DATABASE_ERROR", "Failed to connect to database.");

define("EMAIL_USE_SMTP", true);
define("EMAIL_SMTP_HOST", "");
define("EMAIL_SMTP_AUTH", true);
define("EMAIL_SMTP_USERNAME", "");
define("EMAIL_SMTP_PASSWORD", "");
define("EMAIL_SMTP_PORT", 587);  //587
define("EMAIL_SMTP_ENCRYPTION", "ssl");

define("RT", "");
define("RM", "");
define("PI", "");
define("AB", "");
define("RMGM", "");
define("FINANCE", "");

define("EMAIL_SENDER_NAME", "Ranchi Mall");
define("EMAIL_SUBJECT", "Ranchi Mall Fund Transfer Request.");
define("EMAIL_SUBJECT_RTM_TRANSFER", "Ranchi Mall RMT Transfer Request.");
define("EMAIL_SUBJECT_BTC_TO_CASH", "Ranchi Mall BTC To CASH exchange Request.");

define("TOP_BUYS_TABLE", "");
define("TOP_SELL_TABLE", "");
define("CREDITS_TABLE", "");
define("CREDITS_HISTORY_TABLE", "");
define("ACCOUNTS_TABLE", "");
define("USERS_TABLE", "");
define("TRANSFER_INFO_TABLE", "");
define("MSG_TABLE", "");
define("ORDERS_TABLE", "");
define("TRANSACTIONS_TABLE", "");
define("ADMIN_BAL_RECORDS", "");

define("APP_ID", 'XXXXXXXXXX');
define("APP_SECRET", 'XXXXXXXXXXXXX');

define("ADMIN_FB_ID", "XXXXXXXXX");
define("ADMIN_ID", "XXXXXXXXXXXXX");
define("ADMIN_UNAME", "XXXXXXXXXXXXX");
