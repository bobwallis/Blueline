<?php
namespace Blueline\Extensions\Twig;

class All extends \Twig_Extension {

	public function getFilters() {
		return array(
			'count'   => new \Twig_Filter_Function( 'count' ),
		);
	}

	public function getName() {
		return 'blueline_twig_extension';
	}

}
