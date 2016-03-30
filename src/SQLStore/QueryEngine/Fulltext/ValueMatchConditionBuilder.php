<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\Query\Language\ValueDescription;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueMatchConditionBuilder {

	/**
	 * @since 2.4
	 *
	 * @return boolean
	 */
	public function isEnabled() {
		return false;
	}

	/**
	 * @since 2.4
	 *
	 * @return string
	 */
	public function getTableName() {
		return '';
	}

	/**
	 * @since 2.4
	 *
	 * @param ValueDescription $description
	 *
	 * @return boolean
	 */
	public function canApplyFulltextSearchMatchCondition( ValueDescription $description ) {
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
		return '';
	}

}
