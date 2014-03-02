<?php
namespace Blueline\AssociationsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class CheckAssociationsLinksCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName( 'blueline:checkAssociationsLinks' )
            ->setDescription( 'Checks whether the URLs to associations are active' );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Set up styles
        $output->getFormatter()
               ->setStyle( 'title', new OutputFormatterStyle( 'white', null, array( 'bold' ) ) );
        $output->getFormatter()
               ->setStyle( 'found', new OutputFormatterStyle( 'green', null ) );
        $output->getFormatter()
               ->setStyle( 'redirect', new OutputFormatterStyle( 'yellow', null ) );
        $output->getFormatter()
               ->setStyle( 'notfound', new OutputFormatterStyle( 'red', null  ) );

        $output->writeln( '<title>Checking association links</title>' );

        // Get an array of association data
        $associations = $this->getContainer()->get( 'doctrine' )->getEntityManager()
                             ->createQuery( 'SELECT a.abbreviation, a.link FROM BluelineAssociationsBundle:Association a ORDER BY a.abbreviation' )
                             ->getArrayResult();
        foreach ($associations as $association) {
            $output->write( ' '.$association['abbreviation'].str_repeat( ' ', 8-strlen($association['abbreviation']) ) );
            $ch = curl_init( $association['link'] );
            curl_setopt( $ch, CURLOPT_NOBODY, true );
            curl_exec( $ch );
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close( $ch );
            if ($retcode == 200) {
                $output->writeln( "<found>".$retcode."\t".$association['link'].'</found>' );
            } elseif ($retcode == 301 || $retcode == 302) {
                $output->writeln( "<redirect>".$retcode."\t".$association['link'].'</redirect>' );
            } else {
                $output->writeln( "<notfound>".$retcode."\t".$association['link'].'</notfound>' );
            }
        }
    }
}