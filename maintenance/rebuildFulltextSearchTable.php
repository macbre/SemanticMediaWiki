<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\ApplicationFactory;
use SMW\StoreFactory;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class RebuildFulltextSearchTable extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Rebuild the fulltext search index (only works with SQLStore)';
		parent::__construct();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !defined( 'SMW_VERSION' ) ) {
			$this->output( "You need to have SMW enabled in order to use this maintenance script!\n\n" );
			exit;
		}

		$this->reportMessage(
			"\nThe script rebuilds the search index from property tables that\n" .
			"support a fulltext search. Any change of the index rules (altered\n".
			"stopwords, new stemmer etc.) and/or a newly added or altered table\n".
			"requires to run this script again to ensure that the index complies\n".
			"with the rules set forth by the DB or Sanitizer.\n\n" .
			"Depending on the size of tables selected, it may take a moment\n".
			"before the index rebuild is completed.\n---\n"
		);

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$fulltextSearchTableUpdater = $fulltextSearchTableFactory->newFulltextSearchTableUpdater(
			StoreFactory::getStore( '\SMW\SQLStore\SQLStore' )
		);

		// Need to instantiate an extra object here since we cannot make this class itself
		// into a MessageReporter since the maintenance script does not load the interface in time.
		$reporter = MessageReporterFactory::getInstance()->newObservableMessageReporter();
		$reporter->registerReporterCallback( array( $this, 'reportMessage' ) );

		$fulltextSearchTableUpdater->setMessageReporter( $reporter );
		$fulltextSearchTableUpdater->doRebuild();
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\RebuildFulltextSearchTable';
require_once ( RUN_MAINTENANCE_IF_MAIN );
