<?php
namespace vipnytt\RobotsTxtParser;

use vipnytt\RobotsTxtParser\Client\Directives\CleanParamClient;
use vipnytt\RobotsTxtParser\Client\Directives\HostClient;
use vipnytt\RobotsTxtParser\Client\Directives\SitemapClient;
use vipnytt\RobotsTxtParser\Client\Directives\UserAgentClient;
use vipnytt\RobotsTxtParser\Exceptions\ClientException;
use vipnytt\RobotsTxtParser\Handler\EncodingHandler;
use vipnytt\RobotsTxtParser\Parser\RobotsTxtParser;

/**
 * Class TxtClient
 *
 * @see https://github.com/VIPnytt/RobotsTxtParser/blob/master/docs/methods/TxtClient.md for documentation
 * @package vipnytt\RobotsTxtParser
 */
class TxtClient extends RobotsTxtParser
{
    /**
     * Status code
     * @var int|null
     */
    private $statusCode;

    /**
     * Robots.txt content
     * @var string
     */
    private $content;

    /**
     * Encoding
     * @var string
     */
    private $encoding;

    /**
     * TxtClient constructor.
     *
     * @param string $baseUri
     * @param int|null $statusCode
     * @param string $content
     * @param string $encoding
     * @param string|null $effectiveUri
     * @param int|null $byteLimit
     */
    public function __construct($baseUri, $statusCode, $content, $encoding = self::ENCODING, $effectiveUri = null, $byteLimit = self::BYTE_LIMIT)
    {
        $this->statusCode = $statusCode;
        $this->content = $content;
        $this->encoding = $encoding;
        $this->convertEncoding();
        $this->limitBytes($byteLimit);
        parent::__construct($baseUri, $this->content, $effectiveUri);
    }

    /**
     * Convert character encoding
     *
     * @return string
     */
    private function convertEncoding()
    {
        $convert = new EncodingHandler($this->content, $this->encoding);
        if (($result = $convert->auto()) !== false) {
            $this->encoding = self::ENCODING;
            mb_internal_encoding(self::ENCODING);
            return $this->content = $result;
        }
        mb_internal_encoding(self::ENCODING);
        return $this->content;
    }

    /**
     * Byte limit
     *
     * @param int|null $bytes
     * @return string
     * @throws ClientException
     */
    private function limitBytes($bytes)
    {
        if ($bytes === null) {
            return $this->content;
        } elseif ($bytes < (self::BYTE_LIMIT * 0.25)) {
            throw new ClientException('Byte limit is set dangerously low! Default value=' . self::BYTE_LIMIT);
        }
        return $this->content = mb_strcut($this->content, 0, intval($bytes));
    }

    /**
     * Get User-agent list
     *
     * @return string[]
     */
    public function getUserAgents()
    {
        return $this->handler->userAgent()->getUserAgents();
    }

    /**
     * Clean-param
     *
     * @return CleanParamClient
     */
    public function cleanParam()
    {
        return $this->handler->cleanParam()->client();
    }

    /**
     * Host
     *
     * @return HostClient
     */
    public function host()
    {
        return $this->handler->host()->client();
    }

    /**
     * Sitemaps
     *
     * @return SitemapClient
     */
    public function sitemap()
    {
        return $this->handler->sitemap()->client();
    }

    /**
     * User-agent specific rules
     *
     * @param string $product
     * @param int|string|null $version
     * @return UserAgentClient
     */
    public function userAgent($product = self::USER_AGENT, $version = null)
    {
        return $this->handler->userAgent()->client($product, $version, $this->statusCode);
    }
}
