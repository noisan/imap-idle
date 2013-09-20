<?php
namespace Noi\Tests\Util\Mail;

use PEAR_Error;

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

        // inject a mock socket
        $imap->Net_IMAPProtocol();
        $imap->_socket = $socket;
        $imap->_connected = true;
        $imap->_lastCmdID = 'A0001';
        //$imap->setPrintErrors(true);

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

    public function testIdle_ReturnsFalse_Timeout()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(false));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->returnValue("+ idling\r\n"),
                    $this->returnValue($this->imap->getLastCmdId() . " OK IDLE terminated (Success)\r\n"),
                )));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertFalse($result);
    }

    public function testIdle_ReturnsTrue_NewMailArrival()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(true));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->returnValue("+ idling\r\n"),
                    $this->returnValue("* 1 EXISTS\r\n"),
                    $this->returnValue($this->imap->getLastCmdId() . " OK IDLE terminated (Success)\r\n"),
                )));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertTrue($result);
    }

    public function testIdle_ReturnsTrue_MailExpunction()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(true));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->returnValue("+ idling\r\n"),
                    $this->returnValue("* 2 EXPUNGE\r\n"),
                    $this->returnValue($this->imap->getLastCmdId() . " OK IDLE terminated (Success)\r\n"),
                )));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertTrue($result);
    }

    public function testIdle_ReturnsTrue_ArrivalAndExpunction()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(true));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->returnValue("+ idling\r\n"),
                    $this->returnValue("* 3 EXPUNGE\r\n"),
                    $this->returnValue("* 2 EXISTS\r\n"),
                    $this->returnValue($this->imap->getLastCmdId() . " OK IDLE terminated (Success)\r\n"),
                )));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertTrue($result);
    }

    public function testIdle_WillWaitForSpecifiedTimeout()
    {
        // Setup
        $timeout = 300;

        // Expect
        $this->mockServer->expects($this->once())
                ->method('select')->with($this->anything(), $timeout);

        // Act
        $this->imap->idle($timeout);
    }

    public function testIdle_ReturnsErrorObject_SocketDisconnection()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(true));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->returnValue(''));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertInstanceOf('PEAR_Error', $result);
    }

    public function testIdle_ReturnsErrorObject_UnknowResponse()
    {
        // Setup
        $this->mockServer->expects($this->any())
                ->method('select')
                ->will($this->returnValue(true));

        $this->mockServer->expects($this->any())
                ->method('gets')
                ->will($this->verifyConsecutiveCalls(array(
                    $this->returnValue("+ idling\r\n"),
                    $this->returnValue("* BYE Session expired, please login again.\r\n"),
                )));

        // Act
        $result = $this->imap->idle();

        // Assert
        $this->assertInstanceOf('PEAR_Error', $result);
    }
}
