<?php
namespace Blueline\BluelineBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class BluelineExtension extends \Twig_Extension
{
    protected $path;
    protected $chromeless;
    protected $environment;
    protected $config;

    public function __construct(ContainerInterface $container, $config)
    {
        try {
            $request          = $container->get('request');
            $this->path       = $request->getPathInfo();
            $this->chromeless = ($request->getRequestFormat() == 'html' && intval($request->query->get('chromeless')) == 1);
        } catch (\Exception $e) {
            $this->path       = '/';
            $this->chromeless = false;
        }
        $this->environment = $container->getParameter('kernel.environment');
        $this->config      = $config;
    }

    public function getName()
    {
        return 'blueline.twig';
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('count', 'count'),
            new \Twig_SimpleFunction('round', 'round'),
            new \Twig_SimpleFunction('list', array($this, 'toList')),
            new \Twig_SimpleFunction('dayToString', array($this, 'dayToString')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('count', 'count'),
            new \Twig_SimpleFilter('addAccidentals', array($this, 'addAccidentals')),
            new \Twig_SimpleFilter('toArray', array($this, 'toArray')),
        );
    }

    public function getGlobals()
    {
        return array(
            'admin_email'    => $this->config['admin_email'],
            'analytics_code' => $this->config['analytics_code'],
            'chromeless'     => $this->chromeless,
            'html_age'       => ($this->environment == 'prod') ? $this->config['asset_update'] : 'dev',
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
