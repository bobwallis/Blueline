<?php
namespace Blueline\BluelineBundle\Twig\Extension;

use \Twig_Extension;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BluelineExtension extends Twig_Extension
{
    protected $path;
    protected $chromeless;
    protected $environment;
    protected $config;

    public function __construct(ContainerInterface $container, $config)
    {
        try {
            $request          = $container->get( 'request' );
            $this->path       = $request->getPathInfo();
            $this->chromeless = ( $request->getRequestFormat() == 'html' && intval( $request->query->get( 'chromeless' ) ) == 1 );
        } catch ( \Exception $e ) {
            $this->path       = '/';
            $this->chromeless = false;
        }
        $this->environment = $container->getParameter( 'kernel.environment' );
        $this->config      = $config;
    }

    public function getName()
    {
        return 'blueline.twig';
    }

    public function getFunctions()
    {
        return array(
            'count'       => new \Twig_Function_Function( 'count' ),
            'round'       => new \Twig_Function_Function( 'round' ),
            'list'        => new \Twig_Function_Method( $this, 'toList' ),
            'dayToString' => new \Twig_Function_Method( $this, 'dayToString' )
        );
    }

    public function getFilters()
    {
        return array(
            'count'          => new \Twig_Filter_Function( 'count' ),
            'addAccidentals' => new \Twig_Filter_Method( $this, 'addAccidentals' )
        );
    }

    public function getGlobals()
    {
        return array(
            'admin_email'    => $this->config['admin_email'],
            'analytics_code' => $this->config['analytics_code'],
            'chromeless'     => $this->chromeless,
            'html_age'       => ($this->environment == 'prod')? $this->config['asset_update'] : 'dev',
            'isAppStartPage' => ($this->path == '/') && ($this->environment == 'prod')
        );
    }

    public function toList(array $list, $glue = ', ', $last = ' and ')
    {
        $list = array_filter( $list );
        if ( empty( $list ) ) {
            return '';
        }
        if ( count( $list ) > 1 ) {
            return implode( $glue, array_slice( $list, null, -1 ) ) . $last . array_pop( $list );
        } else {
            return array_pop( $list );
        }
    }

    public function addAccidentals($str)
    {
        return preg_replace( array( '/(^|\s)([A-G1-9]{1})b($|\s)/', '/(^|\s)([A-G1-9]{1})#($|\s)/' ), array( '$1$2♭$3', '$1$2♯$3' ), $str );
    }

    private static $days = array( '', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
    public function dayToString($day)
    {
        return self::$days[intval( $day )];
    }
}
