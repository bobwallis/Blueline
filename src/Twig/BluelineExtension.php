<?php

namespace Blueline\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

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
        return [
            new \Twig\TwigFunction('count', 'count'),
            new \Twig\TwigFunction('round', 'round'),
            new \Twig\TwigFunction('list', [$this, 'toList']),
            new \Twig\TwigFunction('dayToString', [$this, 'dayToString']),
        ];
    }

    /**
     * Register custom Twig filters.
     *
     * @return array<int, \Twig\TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('count', 'count'),
            new \Twig\TwigFilter('toArray', [$this, 'toArray']),
        ];
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
            $chromeless = is_null($request) ? false : ('html' == $request->getRequestFormat() && 1 == intval($request->query->get('chromeless')));
        } catch (\Exception $e) {
            $chromeless = false;
        }

        return [
            'chromeless' => $chromeless,
            'db_age' => $this->params->get('blueline.database_update'),
        ];
    }

    /**
     * Join list items with a configurable separator.
     *
     * @param array<int, mixed> $list
     * @param string            $glue
     * @param string            $last
     *
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
        }

        return array_pop($list);
    }

    private static $days = ['', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /**
     * Convert a numeric day-of-week value to its name.
     *
     * @param int|string $day Numeric day index (1-7)
     *
     * @return string
     */
    public function dayToString($day)
    {
        return self::$days[intval($day)];
    }

    /**
     * Convert supported objects/arrays to plain arrays for Twig rendering.
     *
     * @param string|array<int, string>|null $fields
     *
     * @return array<mixed>|mixed
     *
     * @throws RuntimeError When conversion is requested for unsupported objects
     */
    public function toArray($obj, $fields = null)
    {
        if (is_callable([$obj, '__toArray'])) {
            return $obj->__toArray($fields);
        } elseif (is_array($obj)) {
            return array_map(function ($item) use ($fields) {
                return $this->toArray($item, $fields);
            }, $obj);
        } elseif (is_callable([$obj, 'toArray'])) {
            return array_map([$this, 'toArray'], $obj->toArray());
        } else {
            throw new RuntimeError("toArray requested on object that doesn't implement it");
        }
    }
}
