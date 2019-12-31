<?php

/**
 * @see       https://github.com/laminas/laminas-mail for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mail/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mail/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mail\Header;

use Laminas\Mime\Mime;

class GenericHeader implements HeaderInterface, UnstructuredInterface
{
    /**
     * @var string
     */
    protected $fieldName = null;

    /**
     * @var string
     */
    protected $fieldValue = null;

    /**
     * Header encoding
     *
     * @var string
     */
    protected $encoding = 'ASCII';

    public static function fromString($headerLine)
    {
        list($name, $value) = self::splitHeaderLine($headerLine);
        $decodedValue = HeaderWrap::mimeDecodeValue($value);
        $wasEncoded = ($decodedValue !== $value);
        $value = $decodedValue;
        $header = new static($name, $value);
        if ($wasEncoded) {
            $header->setEncoding('UTF-8');
        }
        return $header;
    }

    /**
     * Splits the header line in `name` and `value` parts.
     *
     * @param string $headerLine
     * @return string[] `name` in the first index and `value` in the second.
     * @throws Exception\InvalidArgumentException If header does not match with the format ``name:value``
     */
    public static function splitHeaderLine($headerLine)
    {
        $parts = explode(':', $headerLine, 2);
        if (count($parts) !== 2) {
            throw new Exception\InvalidArgumentException('Header must match with the format "name:value"');
        }

        if (! HeaderName::isValid($parts[0])) {
            throw new Exception\InvalidArgumentException('Invalid header name detected');
        }

        if (! HeaderValue::isValid($parts[1])) {
            throw new Exception\InvalidArgumentException('Invalid header value detected');
        }

        $parts[0] = $parts[0];
        $parts[1] = ltrim($parts[1]);

        return $parts;
    }

    /**
     * Constructor
     *
     * @param string $fieldName  Optional
     * @param string $fieldValue Optional
     */
    public function __construct($fieldName = null, $fieldValue = null)
    {
        if ($fieldName) {
            $this->setFieldName($fieldName);
        }

        if ($fieldValue) {
            $this->setFieldValue($fieldValue);
        }
    }

    /**
     * Set header name
     *
     * @param  string $fieldName
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldName($fieldName)
    {
        if (!is_string($fieldName) || empty($fieldName)) {
            throw new Exception\InvalidArgumentException('Header name must be a string');
        }

        // Pre-filter to normalize valid characters, change underscore to dash
        $fieldName = str_replace(' ', '-', ucwords(str_replace(array('_', '-'), ' ', $fieldName)));

        if (! HeaderName::isValid($fieldName)) {
            throw new Exception\InvalidArgumentException(
                'Header name must be composed of printable US-ASCII characters, except colon.'
            );
        }

        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Set header value
     *
     * @param  string $fieldValue
     * @return GenericHeader
     * @throws Exception\InvalidArgumentException;
     */
    public function setFieldValue($fieldValue)
    {
        $fieldValue  = (string) $fieldValue;

        // Raw values will be encoded when cast to string; as such we need to
        // mark them as quoted-printable to allow validation to work correctly.
        if (!HeaderWrap::canBeEncoded($fieldValue)) {
            throw new Exception\InvalidArgumentException(
                'Header value must be composed of printable US-ASCII characters and valid folding sequences.'
            );
        }

        if (!Mime::isPrintable($fieldValue)) {
            $this->setEncoding('UTF-8');
        }

        $this->fieldValue = $fieldValue;
        return $this;
    }

    public function getFieldValue($format = HeaderInterface::FORMAT_RAW)
    {
        if (HeaderInterface::FORMAT_ENCODED === $format) {
            return HeaderWrap::wrap($this->fieldValue, $this);
        }

        return $this->fieldValue;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function toString()
    {
        $name  = $this->getFieldName();
        if (empty($name)) {
          throw new Exception\RuntimeException('Header name is not set, use setFieldName()');
        }
        $value = $this->getFieldValue(HeaderInterface::FORMAT_ENCODED);
        if (empty($value)) {
          throw new Exception\RuntimeException('Header value is not set, use setFieldValue()');
        }

        return $name . ': ' . $value;
    }
}
