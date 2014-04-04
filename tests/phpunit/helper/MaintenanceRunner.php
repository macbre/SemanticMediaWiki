<?php

namespace SMW\Test;

use RuntimeException;
use DomainException;

/**
 * Running maintenance scripts via phpunit is not really possible but instead
 * this class allows to execute script related classes that are equivalent to:
 * `php rebuildData.php --< myOptions >`
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class MaintenanceRunner {

	protected $maintenanceClass = null;
	protected $options = array();
	protected $output = null;

	/**
	 * @since 1.9.2
	 *
	 * @return string $maintenanceClass
	 */
	public function __construct( $maintenanceClass, $options = array() ) {
		$this->maintenanceClass = $maintenanceClass;
		$this->options = $options;
	}

	/**
	 * @since 1.9.2
	 *
	 * @param array $options
	 *
	 * @return MaintenanceRunner
	 */
	public function setOptions( array $options ) {
		$this->options = array_merge( $this->options, $options );
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return MaintenanceRunner
	 */
	public function setQuiet() {
		$this->options = array_merge( $this->options, array( 'quiet' => true ) );
		return $this;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return boolean
	 * @throws RuntimeException
	 * @throws DomainException
	 */
	public function run() {

		if ( !class_exists( $this->maintenanceClass ) ) {
			throw new RuntimeException( "Expected a valid {$this->maintenanceClass} class" );
		}

		$maintenance = new $this->maintenanceClass;

		if ( !( $maintenance instanceof \Maintenance ) ) {
			throw new DomainException( "Expected a Maintenance instance" );
		}

		$maintenance->loadParamsAndArgs( $this->maintenanceClass, $this->options );

		ob_start();
		$result = $maintenance->execute();
		$this->output = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * @since 1.9.2
	 *
	 * @return string
	 */
	public function getOutput() {
		return $this->output;
	}

}