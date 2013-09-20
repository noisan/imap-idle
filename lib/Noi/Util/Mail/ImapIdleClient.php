<?php
namespace Noi\Util\Mail;

use Net_IMAP;
use PEAR_Error;

/**
 * ImapIdleClient extends pear/Net_IMAP and provides the IMAP IDLE command (RFC 2177).
 *
 * Usage:
 *
 * <code>
 * <?php
 * $imap = new \Noi\Util\Mail\ImapIdleClient('your.imap.host', 993, true, 'UTF-8');
 * $imap->login('username', 'password');
 * $imap->selectMailbox('INBOX');
 *
 * while (!$imap->idle(300) instanceof \PEAR_Error) {
 *     $mails = $imap->search('UNSEEN', true);
 *     foreach ($imap->getMessages($mails) as $mail) {
 *         // ...
 *     }
 * }
 * </code>
 *
 * @see http://tools.ietf.org/html/rfc2177
 *
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ImapIdleClient extends Net_IMAP
{
    const RESPONSE_TIMEOUT = 'IDLE_ABORTED';

    private $idling;
    private $maxIdleTime;

    /**
     * Uses the IMAP IDLE command (RFC 2177).
     *
     * @see http://tools.ietf.org/html/rfc2177
     *
     * @param int|null $maxIdleTime Number of seconds for timeout.
     * @return boolean|\PEAR_Error TRUE if the selected mailbox is updated,
     *                             FALSE if $maxIdleTime expires.
     *                             PEAR_Error on error.
     */
    public function idle($maxIdleTime = null)
    {
        $this->idling = true;
        $this->maxIdleTime = $maxIdleTime;

        $ret = $this->_genericCommand('IDLE');

        if ($ret instanceof PEAR_Error) {
            // this means that the socket is already disconnected
            return $ret;
        }

        if (strtoupper($ret['RESPONSE']['CODE']) != 'OK') {
            // unknown response type
            // probably we got disconnected before sending the DONE command
            return new PEAR_Error($ret['RESPONSE']['CODE'] .
                    ', ' . $ret['RESPONSE']['STR_CODE']);
        }

        if (isset($ret['PARSED'][0]['COMMAND']) and
                ($ret['PARSED'][0]['COMMAND'] == self::RESPONSE_TIMEOUT)) {
            return false;
        }
        return true;
    }

    protected function done()
    {
        $this->idling = false;
        $ret = $this->_send('DONE' . "\r\n");
        if ($ret instanceof PEAR_Error) {
            $this->onError($ret);
        }
    }

    // override
    function _recvLn()
    {
        if (!$this->idling) {
            return parent::_recvLn();
        }

        if ($this->_socket->select(NET_SOCKET_READ, $this->maxIdleTime)) {
            $lastline = parent::_recvLn();

            if ($lastline instanceof PEAR_Error) {
                return $this->onError($lastline);
            }

            if ($this->isMailboxUpdated($lastline)) {
                $this->done();
            }

            return $lastline;
        }

        $this->done();

        // this is just a dummy for the parser
        $this->lastline = $this->createDummyMessage();
        return $this->lastline;
    }

    protected function isMailboxUpdated($lastline)
    {
        return ((strpos($lastline, 'EXISTS') !== false) or
                (strpos($lastline, 'EXPUNGE') !== false));
    }

    protected function createDummyMessage()
    {
        return '* ' . self::RESPONSE_TIMEOUT . "\r\n";
    }

    // override
    function _retrParsedResponse(&$str, $token, $previousToken = null)
    {
        if (strtoupper($token) == self::RESPONSE_TIMEOUT) {
            return array($token => rtrim(substr($this->_getToEOL($str, false), 1)));
        }
        return parent::_retrParsedResponse($str, $token, $previousToken);
    }

    protected function onError($error)
    {
        return $error;
        /*
        throw new UnexpectedValueException($error->getMessage());
         */
    }
}
