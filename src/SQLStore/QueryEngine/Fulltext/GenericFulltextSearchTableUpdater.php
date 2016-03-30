<?php

namespace SMW\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\CompositePropertyTableDiffIterator;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class GenericFulltextSearchTableUpdater extends FulltextSearchTableUpdater {

	/**
	 * @see SMW::SQLStore::AfterDataUpdateComplete hook
	 *
	 * @since 2.4
	 *
	 * @param CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator
	 */
	public function addUpdatesFromPropertyTableDiff( CompositePropertyTableDiffIterator $compositePropertyTableDiffIterator ) {

		if ( !$this->isEnabled() ) {
			return;
		}

		$diff = $compositePropertyTableDiffIterator->getOrderedDiffByTable();

		$deletes = array();
		$inserts = array();

		foreach ( $diff as $table => $contents ) {
			$pid = '';

			foreach ( $contents as $type => $content ) {

				if ( $type === 'property' ) {
					$pid = $content['p_id'];
					continue;
				}

				$this->doAggregateTableDiffValues( $type, $pid, $content, $deletes, $inserts );
			}
		}

		// Remove any "deletes" first
		foreach ( $deletes as $key => $values ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			$text = $this->read(
				$sid,
				$pid
			);

			if ( $text === false ) {
				continue;
			}

			foreach ( $values as $k => $value ) {
				$text = str_replace( $value, '', $text );
			}

			$this->update( $sid, $pid, $text );
		}

		foreach ( $inserts as $key => $value ) {
			list( $sid, $pid ) = explode( ':', $key, 2 );

			if ( $value === '' ) {
				continue;
			}

			$text = $this->read(
				$sid,
				$pid
			);

			if ( $text === false ) {
				$this->insert( $sid, $pid );
			}

			$this->update( $sid, $pid, $text . ' ' . $value );
		}
	}

	/**
	 * @see RebuildFulltextSearchTable::execute
	 *
	 * @since 2.4
	 */
	public function doRebuild() {

		if ( !$this->isEnabled() ) {
			return $this->reportMessage( "\n" . "FullText search indexing is not enabled or supported." ."\n\n" );
		}

		$this->deleteAll();
		$this->reportMessage( "\n" . "Rebuilding the text index from:" ."\n\n" );

		foreach ( $this->getPropertyTables() as $proptable ) {

			// Only care for Blob tables
			if ( $proptable->getDiType() !== DataItem::TYPE_BLOB ) {
				continue;
			}

			$fetchFields = array( 's_id', 'p_id', 'o_blob', 'o_hash' );
			$table = $proptable->getName();
			$pid = '';

			// Fixed tables don't have a p_id column therefore get it
			// from the ID TABLE
			if ( $proptable->isFixedPropertyTable() ) {
				$fetchFields = array( 's_id', 'o_blob', 'o_hash' );
				$pid = $this->getPropertyID(
					new DIProperty( $proptable->getFixedProperty() )
				);
			}

			$rows = $this->connection->select(
				$table,
				$fetchFields,
				array(),
				__METHOD__
			);

			if ( $rows === false ) {
				return;
			}

			$this->doRebuildTableRows( $table, $pid, $rows );
		}
	}

	/**
	 * @since 2.4
	 */
	public function deleteAll() {
		$this->connection->delete(
			$this->table,
			'*',
			__METHOD__
		);

		$this->reportMessage( "\n" . "The entire '$this->table' was purged." ."\n" );
	}

	private function read( $sid, $pid ) {
		$row = $this->connection->selectRow(
			$this->table,
			array( 'o_text' ),
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
			__METHOD__
		);

		if ( $row === false ) {
			return false;
		}

		return $row->o_text;
	}

	private function doAggregateTableDiffValues( $type, $pid, array $content, array &$deletes, array &$inserts ) {

		foreach ( $content as $key => $values ) {

			if ( !isset( $values['o_blob'] ) && !isset( $values['o_hash'] ) ) {
				continue;
			}

			if ( isset( $values['p_id'] ) ) {
				$pid = $values['p_id'];
			}

			// We need a valid property ID
			if ( $pid === '' || $pid == 0 ) {
				continue;
			}

			// Build a temporary stable key for the diff match
			$key = $values['s_id'] . ':' . $pid;
			$text = '';

			if ( !isset( $inserts[$key] ) ) {
				$inserts[$key] = '';
			}

			if ( !isset( $deletes[$key] ) ) {
				$deletes[$key] = array();
			}

			// If the blob value is empty then the DIHandler has put any text < 72
			// into the hash field
			$text = $values['o_blob'] === null ? $values['o_hash'] : $values['o_blob'];

			// Concatenate the inserts but keep the deletes separate to allow
			// for them to be removed individually
			if ( $type === 'insert' ) {
				$inserts[$key] = trim( $inserts[$key] . ' ' . trim( $text ) );
			} elseif ( $type === 'delete' ) {
				$deletes[$key][] = $text;
			}
		}
	}

	private function doRebuildTableRows( $table, $pid, $rows ) {

		$i = 0;
		$expected = $rows->numRows();

		foreach ( $rows as $row ) {
			$i++;

			$this->reportMessage(
				"\r". sprintf( "%-20s%s", "- {$table}", sprintf("%4.0f%% (%s/%s)",( $i / $expected ) * 100, $i, $expected ) )
			);

			$sid = $row->s_id;
			$pid = !isset( $row->p_id ) ? $pid : $row->p_id;
			$indexableText = $row->o_blob === null ? $row->o_hash : $row->o_blob;

			$text = $this->read( $sid, $pid );

			// Unkown, so let's create the row
			if ( $text === false ) {
				$this->insert( $sid, $pid );
			}

			$this->update( $sid, $pid, trim( $text ) . ' ' . trim( $indexableText ) );
		}

		$this->reportMessage( "\n" );
	}

	private function update( $sid, $pid, $text ) {
		$this->connection->update(
			$this->table,
			array(
				'o_text' => trim( $text ),
				'o_hash' => mb_substr( $text, 0, 32 )
			),
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
			__METHOD__
		);
	}

	private function insert( $sid, $pid ) {
		$this->connection->insert(
			$this->table,
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid,
				'o_text' => ''
			),
			__METHOD__
		);
	}

	private function delete( $sid, $pid ) {
		$this->connection->delete(
			$this->table,
			array(
				's_id' => (int)$sid,
				'p_id' => (int)$pid
			),
			__METHOD__
		);
	}

}
