<?php
namespace vipnytt\RobotsTxtParser\Directives;

use vipnytt\RobotsTxtParser\ObjectTools;
use vipnytt\RobotsTxtParser\RobotsTxtInterface;
use vipnytt\RobotsTxtParser\UrlToolbox;

/**
 * Class CleanParam
 *
 * @package vipnytt\RobotsTxtParser\Directives
 */
final class CleanParam implements DirectiveInterface, RobotsTxtInterface
{
    use ObjectTools;
    use UrlToolbox;

    /**
     * Directive
     */
    const DIRECTIVE = self::DIRECTIVE_CLEAN_PARAM;

    /**
     * Clean-param array
     * @var array
     */
    protected $array = [];

    /**
     * CleanParam constructor.
     */
    public function __construct()
    {
    }

    /**
     * Add
     *
     * @param string $line
     * @return bool
     */
    public function add($line)
    {
        // split into parameter and path
        $array = array_map('trim', mb_split('\s+', $line, 2));
        // strip any invalid characters from path prefix
        $path = isset($array[1]) ? $this->urlEncode(mb_ereg_replace('[^A-Za-z0-9\.-\/\*\_]', '', $array[1])) : '/*';
        $param = array_map('trim', mb_split('&', $array[0]));
        foreach ($param as $key) {
            $this->array[$key][] = $path;
        }
        return true;
    }

    /**
     * Check
     *
     * @param  string $path
     * @return bool
     */
    public function check($path)
    {
        foreach ($this->array as $param => $paths) {
            if (
                mb_strpos($path, "?$param=") ||
                mb_strpos($path, "&$param=")
            ) {
                if (empty($paths)) {
                    return true;
                }
                if ($this->checkPath($path, $paths)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Export
     *
     * @return array
     */
    public function export()
    {
        return empty($this->array) ? [] : [self::DIRECTIVE => $this->array];
    }
}
