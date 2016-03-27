<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\MediaWiki\Database;
use SMW\SQLStore\CompositePropertyTableDiffIterator;
use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\SQLStore;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class FulltextSearchTableUpdater {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Database
	 */
	protected $connection;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var boolean
	 */
	private $isEnabled = false;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $store->getConnection( 'mw.db' );
		$this->table = SQLStore::FT_SEARCH_TABLE;
		$this->messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
		$this->isEnabled = $GLOBALS['smwgEnabledFulltextSearch'];
	}

	/**
	 * @since 2.4
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->isEnabled;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->table;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getIndexField() {
		return 'o_text';
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getMinTokenSize() {
		return $GLOBALS['smwgFulltextSearchMinTokenSize'];
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return integer
	 */
	public function getPropertyID( DIProperty $property ) {
		return $this->store->getObjectIds()->getSMWPropertyID( $property );
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getPropertyTables() {
		return $this->store->getPropertyTables();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->connection->addQuotes( $value );
	}

	/**
	 * @since 2.4
	 */
	public function doRebuild() {
		$this->reportMessage( "\n" . "FullText search indexing is not supported." ."\n\n" );
	}

	/**
	 * @since 2.4
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function addUpdatesFromPropertyTableDiff( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {
		return false;
	}

	/**
	 * @since 2.4
	 */
	public function deleteAll() {
		$this->reportMessage( "\n" . "FullText search indexing is not supported." ."\n\n" );
	}

	protected function reportMessage( $message ) {
		$this->messageReporter->reportMessage( $message );
	}

}
