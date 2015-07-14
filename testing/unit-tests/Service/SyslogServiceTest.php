<?php

namespace MCP\Logger\Service;

use MCP\Logger\LogLevelInterface;

global $openlogError;
global $syslogError;

function openlog($ident, $options, $facility)
{
    global $openlogError;

    if (isset($openlogError) && $openlogError === true) {
        return false;
    }

    return \openlog($ident, $options, $facility);
}

function syslog($priority, $message)
{
    global $syslogError;

    if (isset($syslogError) && $syslogError === true) {
        return false;
    }

    return \syslog($priority, $message);
}

class SyslogServiceTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        global $openlogError;
        global $syslogError;

        $openlogError = $syslogError = false;
    }

    /**
     * @dataProvider sendData
     */
    public function testSend($level)
    {
        $message = $this->getMockBuilder('MCP\Logger\Message\Message')
            ->disableOriginalConstructor()
            ->setMethods(['level'])
            ->getMock();

        $message->expects($this->once())
            ->method('level')
            ->will($this->returnValue($level));

        $renderer = $this->getMockBuilder('MCP\Logger\RendererInterface')
            ->disableOriginalConstructor()
            ->setMethods(['__invoke', 'contentType'])
            ->getMock();

        $renderer->expects($this->once())
            ->method('__invoke')
            ->with($message)
            ->will($this->returnValue(''));

        $service = new SyslogService($renderer);
        $service->send($message);
    }

    public function sendData()
    {
        return [
            [
                LogLevelInterface::DEBUG
            ],
            [
                LogLevelInterface::INFO
            ],
            [
                LogLevelInterface::WARN
            ],
            [
                LogLevelInterface::ERROR
            ],
            [
                LogLevelInterface::FATAL
            ],
            [
                LogLevelInterface::AUDIT
            ],
            [
                'random_crap'
            ]
        ];
    }

    public function testSendFailBackup()
    {
        global $syslogError;
        $syslogError = true;

        $message = $this->getMockBuilder('MCP\Logger\Message\Message')
            ->disableOriginalConstructor()
            ->setMethods(['level'])
            ->getMock();

        $message->expects($this->once())
            ->method('level')
            ->will($this->returnValue('Warn'));

        $renderer = $this->getMockBuilder('MCP\Logger\RendererInterface')
            ->disableOriginalConstructor()
            ->setMethods(['__invoke', 'contentType'])
            ->getMock();

        $renderer->expects($this->once())
            ->method('__invoke')
            ->with($message)
            ->will($this->returnValue(''));

        $backup = $this->getMockBuilder('MCP\Logger\ServiceInterface')
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();

        $backup->expects($this->once())
            ->method('send')
            ->with($message);

        $service = new SyslogService($renderer, [], $backup);
        $service->send($message);
    }

    /**
     * @expectedException \MCP\Logger\Exception
     */
    public function testSendFail()
    {
        global $syslogError;
        $syslogError = true;

        $message = $this->getMockBuilder('MCP\Logger\Message\Message')
            ->disableOriginalConstructor()
            ->setMethods(['level'])
            ->getMock();

        $message->expects($this->once())
            ->method('level')
            ->will($this->returnValue('Warn'));

        $renderer = $this->getMockBuilder('MCP\Logger\RendererInterface')
            ->disableOriginalConstructor()
            ->setMethods(['__invoke', 'contentType'])
            ->getMock();

        $renderer->expects($this->once())
            ->method('__invoke')
            ->with($message)
            ->will($this->returnValue(''));

        $service = new SyslogService($renderer, [
            SyslogService::CONFIG_SILENT => false
        ]);
        $service->send($message);
    }

    /**
     * @expectedException \MCP\Logger\Exception
     */
    public function testConnectError()
    {
        global $openlogError;
        $openlogError = true;

        $message = $this->getMockBuilder('MCP\Logger\Message\Message')
            ->disableOriginalConstructor()
            ->getMock();

        $renderer = $this->getMockBuilder('MCP\Logger\RendererInterface')
            ->disableOriginalConstructor()
            ->setMethods(['__invoke', 'contentType'])
            ->getMock();

        $service = new SyslogService($renderer, [
            SyslogService::CONFIG_SILENT => false
        ]);
        $service->send($message);
    }
}