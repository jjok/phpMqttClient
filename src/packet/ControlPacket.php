<?php
/**
 * @author Oliver Lorenz
 * @since 2015-04-24
 * Time: 00:32
 */

namespace oliverlorenz\reactphpmqtt\packet;

abstract class ControlPacket
{
    protected $payload = '';

    /**
     * @param string $rawInput
     * @return static
     */
    public static function parse($rawInput)
    {
        static::checkRawInputValidControlPackageType($rawInput);

        return new static();
    }

    protected static function checkRawInputValidControlPackageType($rawInput)
    {
        $packetType = ord($rawInput{0}) >> 4;
        if ($packetType !== static::getControlPacketType()) {
            throw new \RuntimeException('raw input is not valid for this control packet');
        }
    }

    /** @return int */
    public static function getControlPacketType() {
        throw new \RuntimeException('you must overwrite getControlPacketType()');
    }

    private function getPayloadLength()
    {
        return strlen($this->getPayload());
    }

    public function getPayload()
    {
        return $this->payload;
    }

    private function getRemainingLength()
    {
        return strlen($this->getVariableHeader()) + $this->getPayloadLength();
    }

    /**
     * @return string
     */
    private function getFixedHeader()
    {
        // Figure 3.8
        $byte1 = static::getControlPacketType() << 4;
        $byte1 = $this->addReservedBitsToFixedHeaderControlPacketType($byte1);

        $byte2 = $this->getRemainingLength();

        return chr($byte1)
             . chr($byte2);
    }

    /**
     * @return string
     */
    protected function getVariableHeader()
    {
        return '';
    }

    /**
     * @param $stringToAdd
     */
    protected function addToPayLoad($stringToAdd)
    {
        $this->payload .= $stringToAdd;
    }

    /**
     * @param $fieldPayload
     */
    public function addLengthPrefixedField($fieldPayload)
    {
        $return = $this->getLengthPrefixField($fieldPayload);
        $this->addToPayLoad($return);
    }

    public function getLengthPrefixField($string)
    {
        $stringLength = strlen($string);
        $msb = $stringLength >> 8;
        $lsb = $stringLength % 256;

        return chr($msb)
             . chr($lsb)
             . $string;
    }

    public function get()
    {
        return $this->getFixedHeader()
             . $this->getVariableHeader()
             . $this->getPayload();
    }

    /**
     * @param $byte1
     * @return $byte1 unmodified
     */
    protected function addReservedBitsToFixedHeaderControlPacketType($byte1)
    {
        return $byte1;
    }

    /**
     * @param int $startIndex
     * @param string $rawInput
     * @return string
     */
    protected static function getPayloadLengthPrefixFieldInRawInput($startIndex, $rawInput)
    {
        $headerLength = 2;
        $header = substr($rawInput, $startIndex, $headerLength);
        $lengthOfMessage = ord($header{1});

        return substr($rawInput, $startIndex + $headerLength, $lengthOfMessage);
    }
}
