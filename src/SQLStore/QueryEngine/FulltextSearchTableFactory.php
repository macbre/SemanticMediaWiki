<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\SQLStore;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\GenericFulltextSearchTableUpdater;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class FulltextSearchTableFactory {

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 *
	 * @return ValueMatchConditionBuilder
	 */
	public function newValueMatchConditionBuilderByType( SQLStore $store ) {

		$type = $store->getConnection( 'mw.db' )->getType();

		switch ( $type ) {
			case 'mysql':
				return new MySQLValueMatchConditionBuilder(
					$this->newGenericFulltextSearchTableUpdater( $store )
				);
				break;
		}

		return new ValueMatchConditionBuilder();
	}

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 *
	 * @return FulltextSearchTableUpdater
	 */
	public function newFulltextSearchTableUpdater( SQLStore $store ) {
		return $this->newGenericFulltextSearchTableUpdater( $store );
	}

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 *
	 * @return FulltextSearchTable
	 */
	public function newGenericFulltextSearchTableUpdater( SQLStore $store ) {
		return new GenericFulltextSearchTableUpdater( $store );
	}

}
