<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/imap_idle_demo.conf.php';

$imap = new \Noi\Util\Mail\ImapIdleClient(DEMO_IMAP_HOST, DEMO_IMAP_PORT, true, 'UTF-8');
$imap->login(DEMO_IMAP_USER, DEMO_IMAP_PASS);

//$imap->setDebug(true);
$imap->selectMailbox(DEMO_IMAP_MAILBOX);

while (!$imap->idle(DEMO_IMAP_IDLE_TIMEOUT) instanceof \PEAR_Error) {
    $mails = $imap->search('UNSEEN', true);
    if ($mails instanceof \PEAR_Error) {
        break;
    }
    if (!$mails) {
        continue;
    }

    foreach ($imap->getEnvelope(null, $mails, true) as $envelope) {
        echo "======== New Message ========", "\n";
        echo "Date: ", $envelope['DATE'], "\n";
        echo "From: ", decodeHeader($envelope['FROM'][0]['RFC822_EMAIL']), "\n";
        echo "Subject: ", decodeHeader($envelope['SUBJECT']), "\n";
        echo "=============================", "\n";
    }
}

function decodeHeader($text)
{
    if (function_exists('mb_decode_mimeheader')) {
        return mb_decode_mimeheader($text);
    } else {
        return $text;
    }
}
