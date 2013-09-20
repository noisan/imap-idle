<?php
namespace Noi\Util\Mail;

use Net_IMAP;

/**
 *
 * @author Akihiro Yamanoi <akihiro.yamanoi@gmail.com>
 */
class ImapIdleClient extends Net_IMAP
{
    private $idling;

    public function idle() {
        $this->idling = true;
        $ret = $this->_genericCommand('IDLE');
    }

    protected function done()
    {
        $this->idling = false;
        $this->_send('DONE' . "\r\n");
    }

    // override
    function _recvLn()
    {
        if (!$this->idling) {
            return parent::_recvLn();
        }

        $this->done();

        // this is just a dummy for the parser
        $this->lastline = $this->createDummyMessage();

        return $this->lastline;
    }

    protected function createDummyMessage()
    {
        return '* 0 RECENT' . "\r\n";
    }
}
