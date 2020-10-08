<?php
namespace Blueline\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class BluelineExtension extends AbstractExtension implements GlobalsInterface
{
    protected $path;
    protected $chromeless;
    protected $environment;

    public function __construct(ContainerInterface $container)
    {
        try {
            $request          = $container->get('request_stack')->getCurrentRequest();
            $this->path       = is_null($request)? '/' : $request->getPathInfo();
            $this->chromeless = is_null($request)? false : ($request->getRequestFormat() == 'html' && intval($request->query->get('chromeless')) == 1);
        } catch (\Exception $e) {
            $this->path       = '/';
            $this->chromeless = false;
        }
        $this->environment = $container->getParameter('kernel.environment');
    }

    public function getName()
    {
        return 'blueline.twig';
    }

    public function getFunctions()
    {
        return array(
            new \Twig\TwigFunction('count', 'count'),
            new \Twig\TwigFunction('round', 'round'),
            new \Twig\TwigFunction('list', array($this, 'toList')),
            new \Twig\TwigFunction('dayToString', array($this, 'dayToString')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig\TwigFilter('count', 'count'),
            new \Twig\TwigFilter('addAccidentals', array($this, 'addAccidentals')),
            new \Twig\TwigFilter('toArray', array($this, 'toArray')),
        );
    }

    public function getGlobals(): array
    {
        return array(
            'analytics_code' => getenv('ANALYTICS_CODE'),
            'chromeless'     => $this->chromeless,
            'html_age'       => ($this->environment == 'prod') ? getenv('ASSET_UPDATE') : date('YmdHis'),
            'db_age'         => getenv('DATABASE_UPDATE'),
            'isAppStartPage' => ($this->path == '/') && ($this->environment == 'prod'),
        );
    }

    public function toList(array $list, $glue = ', ', $last = ' and ')
    {
        $list = array_filter($list);
        if (empty($list)) {
            return '';
        }
        if (count($list) > 1) {
            return implode($glue, array_slice($list, null, -1)).$last.array_pop($list);
        } else {
            return array_pop($list);
        }
    }

    public function addAccidentals($str)
    {
        return preg_replace(array( '/(^|\s)([A-G1-9]{1})b($|\s)/', '/(^|\s)([A-G1-9]{1})#($|\s)/' ), array( '$1$2♭$3', '$1$2♯$3' ), $str);
    }

    private static $days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
    public function dayToString($day)
    {
        return self::$days[intval($day)];
    }

    public function toArray($obj)
    {
        if (is_callable(array( $obj, '__toArray' ))) {
            return $obj->__toArray();
        } elseif (is_array($obj)) {
            return array_map(array( $this, 'toArray' ), $obj);
        } elseif (is_callable(array( $obj, "toArray" ))) {
            return array_map(array( $this, 'toArray' ), $obj->toArray());
        } else {
            throw \Twig_Error_Runtime("toArray requested on object that doesn't implement it");
        }
    }
}
