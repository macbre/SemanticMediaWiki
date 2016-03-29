<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\Query\Language\ValueDescription;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SQLiteValueMatchConditionBuilder extends ValueMatchConditionBuilder {

	/**
	 * @var FulltextSearchTableUpdater
	 */
	private $fulltextSearchTableUpdater;

	/**
	 * @since 2.4
	 *
	 * @var FulltextSearchTableUpdater
	 */
	public function __construct( FulltextSearchTableUpdater $fulltextSearchTableUpdater ) {
		$this->fulltextSearchTableUpdater = $fulltextSearchTableUpdater;
	}

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return $this->fulltextSearchTableUpdater->isEnabled();
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getTableName() {
		return $this->fulltextSearchTableUpdater->getTableName();
	}

	/**
	 * @since 2.4
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function hasFulltextSearchSupport( ValueDescription $description ) {

		if ( !$this->isEnabled() ) {
			return false;
		}

		$comparator = $description->getComparator();

		if ( $description->getDataItem() instanceof DIBlob &&
			( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) ) {
			return mb_strlen( $description->getDataItem()->getString() ) > $this->fulltextSearchTableUpdater->getMinTokenSize();
		}

		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @param ValueDescription $description
	 * @param string $temporaryTable
	 *
	 * @return string
	 */
	public function getWhereCondition( ValueDescription $description, $temporaryTable = '' ) {

		$value = $description->getDataItem()->getString();

		// A leading or trailing minus sign indicates that this word must not
		// be present in any of the rows that are returned.
		// InnoDB only supports leading minus signs.
		if ( $description->getComparator() === SMW_CMP_NLKE ) {
			$value = '-' . $value;
		}

		// Somethin like [[Has text::!~database]] will cause a
		// "malformed MATCH expression" due to "An FTS query may not consist
		// entirely of terms or term-prefix queries with unary "-" operators
		// attached to them." and doing "NOT database" will result in an empty
		// result set

		$temporaryTable = $temporaryTable !== '' ? $temporaryTable . '.' : '';
		$column = $temporaryTable . $this->fulltextSearchTableUpdater->getIndexField();

		$property = $description->getProperty();
		$propertyCondition = '';

		// Full text is collected in one table therefore limit match process by
		// adding the PID as an additional condition
		if ( $property !== null ) {
			$propertyCondition = 'AND ' . $temporaryTable . 'p_id=' . $this->fulltextSearchTableUpdater->addQuotes(
				$this->fulltextSearchTableUpdater->getPropertyID( $property )
			);
		}

		return $column . " MATCH " . $this->fulltextSearchTableUpdater->addQuotes( $value ) . " $propertyCondition";
	}

}
