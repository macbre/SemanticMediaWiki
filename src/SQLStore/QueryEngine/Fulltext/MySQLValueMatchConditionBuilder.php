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
class MySQLValueMatchConditionBuilder extends ValueMatchConditionBuilder {

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

		// http://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
		// innodb_ft_min_token_size and innodb_ft_max_token_size are used
		// for InnoDB search indexes. ft_min_word_len and ft_max_word_len
		// are used for MyISAM search indexes

		if ( $description->getDataItem() instanceof DIBlob &&
			( $comparator === SMW_CMP_LIKE || $comparator === SMW_CMP_NLKE ) ) {
			return mb_strlen( $description->getDataItem()->getString() ) > $this->fulltextSearchTableUpdater->getMinTokenSize() ? true : false;
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

		$temporaryTable = $temporaryTable !== '' ? $temporaryTable . '.' : '';
		$column = $temporaryTable . $this->fulltextSearchTableUpdater->getIndexField();

		$property = $description->getProperty();
		$propertyCondition = '';

		// Full text is collected in one table therefore limit match process by
		// adding the PID as an additional condition
		if ( $property !== null ) {
			$propertyCondition = 'AND ' . $temporaryTable . 'p_id=' . $this->fulltextSearchTableUpdater->getPropertyID( $property );
		}

		$querySearchModifier = $this->getQuerySearchModifier(
			$value
		);

		return "MATCH($column) AGAINST (" . $this->fulltextSearchTableUpdater->addQuotes( $value ) . " $querySearchModifier) $propertyCondition";
	}

	/**
	 * @since 2.4
	 *
	 * @param  string &$value
	 *
	 * @return string
	 */
	public function getQuerySearchModifier( &$value ) {

		//  @see http://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
		// "MySQL can perform boolean full-text searches using the IN BOOLEAN
		// MODE modifier. With this modifier, certain characters have special
		// meaning at the beginning or end of words ..."
		if ( strpos( $value, '&BOOL' ) !== false ) {
			$value = str_replace( '&BOOL', '', $value );
			return 'IN BOOLEAN MODE';
		}

		if ( strpos( $value, '&INL' ) !== false ) {
			$value = str_replace( '&INL', '', $value );
			return 'IN NATURAL LANGUAGE MODE';
		}

		if ( strpos( $value, '&QE' ) !== false ) {
			$value = str_replace( '&QE', '', $value );
			return 'WITH QUERY EXPANSION';
		}

		return 'IN BOOLEAN MODE';
	}

}
