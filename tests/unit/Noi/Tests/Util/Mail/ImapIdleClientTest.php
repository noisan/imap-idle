<?php
namespace Noi\Tests\Util\Mail;

class ImapIdleClientTest extends \ImapIdleClientTestCase
{
    private $imap;
    private $mockServer;

    public function setUp()
    {
        $this->mockServer = $this->createMockServerSocket();
        $this->imap = $this->createImap($this->mockServer);
    }

    protected function createImap($socket)
    {
        $imap = $this->getMockBuilder('Noi\Util\Mail\ImapIdleClient')
                ->setMethods(array('Net_IMAP'))
                ->disableOriginalConstructor()
                ->getMock();

        $imap->Net_IMAPProtocol();
        $imap->_socket = $socket;
        $imap->_connected = true;

        return $imap;
    }

    protected function createMockServerSocket()
    {
        return $this->getMock('Net_Socket');
    }

    public function testIdle_SendsIdleAndDoneCommands()
    {
        // Expect
        $this->mockServer->expects($this->exactly(2))
                ->method('write')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->stringEndsWith('IDLE' . "\r\n"),
                    $this->stringEndsWith('DONE' . "\r\n"),
                )));

        // Act
        $this->imap->idle();
    }
}
