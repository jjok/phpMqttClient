<?php
/**
 * @author Oliver Lorenz
 * @since 2015-05-10
 * Time: 16:35
 */

use oliverlorenz\reactphpmqtt\packet\MessageHelper;
use oliverlorenz\reactphpmqtt\packet\Publish;

/**
 * See http://docs.oasis-open.org/mqtt/mqtt/v3.1.1/os/mqtt-v3.1.1-os.html#_Toc398718038
 * for packet type, QoS, DUP and retain.
 *
 * See http://docs.oasis-open.org/mqtt/mqtt/v3.1.1/os/mqtt-v3.1.1-os.html#_Toc398718039
 * for topic and packet identifier.
 */
class PublishTest extends PHPUnit_Framework_TestCase {

    public function testPublishStandard()
    {
        $this->assertEquals(3, Publish::getControlPacketType());
    }

    public function testItIsAProtocolViolationToHaveBothQosBitsAreSet()
    {
        $this->setExpectedException(
            'oliverlorenz\reactphpmqtt\protocol\Violation',
            'A PUBLISH Packet MUST NOT have both QoS bits set to 1.'
        );

        new Publish('topic', 'payload', (Publish::QOS1 | Publish::QOS2));
    }

    public function testExceptionIsThrownForUnexpectedPacketType()
    {
        $input =
            chr(0b00100000) .
            chr(2) .
            chr(0) .
            chr(0);

        $this->setExpectedException(
            'RuntimeException',
            'raw input is not valid for this control packet'
        );

        Publish::parse($input);
    }

    public function testPublishStandardWithQos0()
    {
        $packet = new Publish('topic', 'payload');

        $expected =
            chr(0b00110000) .
            chr(14) .
            chr(0) .
            chr(5) .
            'topic'.
            'payload';

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testPublishStandardWithQos1()
    {
        $packet = new Publish('topic', '', Publish::QOS1);

        $expected =
            chr(0b00110010) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testPublishStandardWithQos2()
    {
        $packet = new Publish('topic', '', Publish::QOS2);

        $expected =
            chr(0b00110100) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testPublishStandardWithDup()
    {
        $packet = new Publish('topic', '', Publish::DUP);

        $expected =
            chr(0b00111000) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testPublishStandardWithRetain()
    {
        $packet = new Publish('topic', '', Publish::RETAIN);

        $expected =
            chr(0b00110001) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testPublishWithPayload()
    {
        $packet = new Publish('topic', 'This is the payload');

        $expected =
            chr(0b00110000) .
            chr(26) .
            chr(0) .
            chr(5) .
            'topic' .
            'This is the payload';

        $this->assertEquals('This is the payload', $packet->getPayload());

        $this->assertSerialisedPacketEquals($expected, $packet->get());
    }

    public function testTopic()
    {
        $packet = new Publish('topic/test', '');

        $expected =
            chr(0b00110000) .
            chr(12) .
            chr(0) .
            chr(10) .
            'topic/test';

        $this->assertEquals('topic/test', $packet->getTopic());

        $this->assertSerialisedPacketEquals(
            $expected,
            $packet->get()
        );
    }

    public function testSetMessageIdReturn()
    {
        $messageId = 1;

        $packet = new Publish('topic', '');
        $return = $packet->setMessageId($messageId);
        $this->assertInstanceOf('oliverlorenz\reactphpmqtt\packet\Publish', $return);
    }

    public function qosProvider() {
        return array(
            array(0,             0, 0b00110000),
            array(1, Publish::QOS1, 0b00110010),
            array(2, Publish::QOS2, 0b00110100),
        );
    }

    /**
     * @dataProvider qosProvider
     */
    public function testParseWithQos($qos, $flags, $byte1)
    {
        $input =
            chr($byte1) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';
        $parsedPacket = Publish::parse($input);

        $comparisonPacket = new Publish('topic', '', $flags);

        $this->assertEquals($qos, $parsedPacket->getQos());
        $this->assertPacketEquals($comparisonPacket, $parsedPacket);
    }

    public function testParseWithRetain()
    {
        $input =
            chr(0b00110001) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';
        $parsedPacket = Publish::parse($input);

        $comparisonPacket = new Publish('topic', '', Publish::RETAIN);

        $this->assertPacketEquals($comparisonPacket, $parsedPacket);
    }

    public function testParseWithDup()
    {
        $input =
            chr(0b00111000) .
            chr(7) .
            chr(0) .
            chr(5) .
            'topic';
        $parsedPacket = Publish::parse($input);

        $comparisonPacket = new Publish('topic', '', Publish::DUP);

        $this->assertPacketEquals($comparisonPacket, $parsedPacket);
    }

    public function testParseWithTopic()
    {
        $expectedPacket = new Publish('some/test/topic', '');

        $input =
            chr(0b00110000) .
            chr(17) .
            chr(0) .
            chr(15) .
            'some/test/topic';
        $parsedPacket = Publish::parse($input);

        $this->assertPacketEquals($expectedPacket, $parsedPacket);
        $this->assertEquals('some/test/topic', $parsedPacket->getTopic());
    }

    public function testParseWithPayload()
    {
        $expectedPacket = new Publish('topic', 'My payload');

        $input =
            chr(0b00110000) .
            chr(17) .
            chr(0) .
            chr(5) .
            'topic' .
            'My payload';
        $parsedPacket = Publish::parse($input);

        $this->assertPacketEquals($expectedPacket, $parsedPacket);
        $this->assertEquals('My payload', $parsedPacket->getPayload());
    }

    private function assertPacketEquals(Publish $expected, Publish $actual)
    {
        $this->assertEquals($expected, $actual);
        $this->assertSerialisedPacketEquals($expected->get(), $actual->get());
    }

    private function assertSerialisedPacketEquals($expected, $actual)
    {
        $this->assertEquals(
            MessageHelper::getReadableByRawString($expected),
            MessageHelper::getReadableByRawString($actual)
        );
    }
}
