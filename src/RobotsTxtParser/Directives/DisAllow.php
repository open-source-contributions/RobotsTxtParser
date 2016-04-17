<?php
namespace vipnytt\RobotsTxtParser\Directives;

use vipnytt\RobotsTxtParser\Exceptions;
use vipnytt\RobotsTxtParser\ObjectTools;
use vipnytt\RobotsTxtParser\RobotsTxtInterface;
use vipnytt\RobotsTxtParser\UrlToolbox;

/**
 * Class DisAllow
 *
 * @package vipnytt\RobotsTxtParser\Directives
 */
final class DisAllow implements DirectiveInterface, RobotsTxtInterface
{
    use ObjectTools;
    use UrlToolbox;

    /**
     * Directive alternatives
     */
    const DIRECTIVE = [
        self::DIRECTIVE_ALLOW,
        self::DIRECTIVE_DISALLOW,
    ];

    /**
     * Sub directives white list
     */
    const SUB_DIRECTIVES = [
        self::DIRECTIVE_CLEAN_PARAM,
        self::DIRECTIVE_HOST,
    ];

    /**
     * Directive
     */
    protected $directive;

    /**
     * Rule array
     * @var array
     */
    protected $array = [];

    /**
     * Sub-directive Clean-param
     * @var CleanParam
     */
    protected $cleanParam;

    /**
     * Sub-directive Host
     * @var Host
     */
    protected $host;

    /**
     * DisAllow constructor
     *
     * @param string $directive
     * @throws Exceptions\ParserException
     */
    public function __construct($directive)
    {
        $this->directive = $this->validateDirective($directive, self::DIRECTIVE);
        $this->cleanParam = new CleanParam();
        $this->host = new Host();
    }

    /**
     * Add
     *
     * @param string $line
     * @return bool
     */
    public function add($line)
    {
        $pair = $this->generateRulePair($line, self::SUB_DIRECTIVES);
        switch ($pair['directive']) {
            case self::DIRECTIVE_CLEAN_PARAM:
                return $this->cleanParam->add($pair['value']);
            case self::DIRECTIVE_HOST:
                return $this->host->add($pair['value']);
        }
        return $this->addPath($line);
    }

    /**
     * Add plain path to allow/disallow
     *
     * @param string $rule
     * @return bool
     */
    protected function addPath($rule)
    {
        // Return an array of paths
        if (isset($this->array['path']) && in_array($rule, $this->array['path'])) {
            return false;
        }
        $this->array['path'][] = $rule;
        return true;
    }

    /**
     * Check
     *
     * @param  string $url
     * @return bool
     */
    public function check($url)
    {
        $path = $this->getPath($url);
        return ($path === false) ? false : (
            $this->checkPath($path, isset($this->array['path']) ? $this->array['path'] : []) ||
            $this->cleanParam->check($path) ||
            $this->host->check($url)
        );
    }

    /**
     * Get path
     *
     * @param string $url
     * @return string
     * @throws Exceptions\ClientException
     */
    protected function getPath($url)
    {
        $url = $this->urlEncode($url);
        if (mb_stripos($url, '/') === 0) {
            // URL already is a path
            return $url;
        }
        if (!$this->urlValidate($url)) {
            throw new Exceptions\ClientException('Invalid URL');
        }
        return parse_url($url, PHP_URL_PATH);
    }

    /**
     * Export
     *
     * @return array
     */
    public function export()
    {
        $result = $this->array
            + $this->cleanParam->export()
            + $this->host->export();
        return empty($result) ? [] : [$this->directive => $result];
    }
}
