<?php

include 'imp_files.php';
   
$DBUSER=DB_USER;
$DBPASSWD=DB_PASS;
$DATABASE= DB_NAME;

$dir = 'backups';

if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

$filename = $dir."/exchange_test_backup-" . date("d-m-Y-H-i-s") . ".sql.gz";
$mime = "application/x-gzip";

//header( "Content-Type: " . $mime );
//header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

$cmd = "mysqldump -u $DBUSER --password=$DBPASSWD $DATABASE | gzip > $filename";   

passthru( $cmd );

// Send mail
$reciever_email = [AB, RMGM];
$email_from = RM;
$email_sender = EMAIL_SENDER_NAME;
$email_subject = "Backup-".date('Y-m-d H:i:s', time());
$email_body ="Backup for Exchange Site. Date: ".date('Y-m-d H:i:s', time());
$attachments = array($filename);
$send_mail = $MailClass->do_email($reciever_email, $email_from, $email_sender, $email_subject, $email_body, $attachments);
exit(0);
?>