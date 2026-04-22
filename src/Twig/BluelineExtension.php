<?php

namespace Blueline\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Error\RuntimeError;

/**
 * Twig extension providing custom functions, filters, and global variables for Blueline templates.
 *
 * Custom functions:
 * - count(): Count array/collection elements
 * - round(): Round numbers
 * - list(array, glue, last): Format array as human-readable list (e.g., "a, b, and c")
 * - dayToString(date): Format date as day name (e.g., "Monday")
 *
 * Custom filters:
 * - count: Count elements (alias to function)
 * - toArray: Convert entity to array representation (calls __toArray() method)
 *
 * Global template variables:
 * - chromeless: Whether UI chrome should be hidden (API mode)
 * - db_age: Last imported database update timestamp
 */
class BluelineExtension extends AbstractExtension implements GlobalsInterface
{
    protected $params;
    protected $requestStack;

    /**
     * Initialise request-dependent Twig globals.
     *
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->requestStack = $requestStack;
    }

    /**
     * Register custom Twig functions.
     *
     * @return array<int, \Twig\TwigFunction>
     */
    public function getFunctions(): array
    {
        return array(
            new \Twig\TwigFunction('count', 'count'),
            new \Twig\TwigFunction('round', 'round'),
            new \Twig\TwigFunction('list', array($this, 'toList')),
            new \Twig\TwigFunction('dayToString', array($this, 'dayToString')),
        );
    }

    /**
     * Register custom Twig filters.
     *
     * @return array<int, \Twig\TwigFilter>
     */
    public function getFilters(): array
    {
        return array(
            new \Twig\TwigFilter('count', 'count'),
            new \Twig\TwigFilter('toArray', array($this, 'toArray')),
        );
    }

    /**
     * Provide global Twig variables.
     *
     * @return array{chromeless: bool, db_age: mixed}
     */
    public function getGlobals(): array
    {
        $chromeless = false;
        try {
            $request = $this->requestStack->getCurrentRequest();
            $chromeless = is_null($request) ? false : ($request->getRequestFormat() == 'html' && intval($request->query->get('chromeless')) == 1);
        } catch (\Exception $e) {
            $chromeless = false;
        }

        return array(
            'chromeless' => $chromeless,
            'db_age' => $this->params->get('blueline.database_update'),
        );
    }

    /**
     * Join list items with a configurable separator.
     *
     * @param array<int, mixed> $list
     * @param string $glue
     * @param string $last
     * @return string
     */
    public function toList(array $list, $glue = ', ', $last = ' and ')
    {
        $list = array_filter($list);
        if (empty($list)) {
            return '';
        }
        if (count($list) > 1) {
            return implode($glue, array_slice($list, 0, -1)).$last.array_pop($list);
        } else {
            return array_pop($list);
        }
    }

    private static $days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );

    /**
     * Convert a numeric day-of-week value to its name.
     *
     * @param int|string $day Numeric day index (1-7)
     * @return string
     */
    public function dayToString($day)
    {
        return self::$days[intval($day)];
    }

    /**
     * Convert supported objects/arrays to plain arrays for Twig rendering.
     *
     * @param mixed $obj
     * @param string|array<int, string>|null $fields
     * @return array<mixed>|mixed
     * @throws RuntimeError When conversion is requested for unsupported objects
     */
    public function toArray($obj, $fields = null)
    {
        if (is_callable(array( $obj, '__toArray' ))) {
            return $obj->__toArray($fields);
        } elseif (is_array($obj)) {
            return array_map(function ($item) use ($fields) {
                return $this->toArray($item, $fields);
            }, $obj);
        } elseif (is_callable(array( $obj, "toArray" ))) {
            return array_map(array( $this, 'toArray' ), $obj->toArray());
        } else {
            throw new RuntimeError("toArray requested on object that doesn't implement it");
        }
    }
}
