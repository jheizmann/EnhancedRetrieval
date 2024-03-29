<?php
/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @file
 * @ingroup EnhancedRetrievalStorage
 *
 * @author Kai K�hn
 */
require_once('US_Store.php');

/**
 * @author: Kai K�hn
 *
 * Created on: 27.01.2009
 *
 */
class USStoreSQL extends USStore {

	/**
     * Returns the (local) URL of an image attached to a category. 
     * The language constant smw_ac_category_has_icon defines the icon property.
     * 
     * @param Title $categoryTitle
     */
	public function getImageURL($categoryTitle) {
		static $image_urls = array();

		if (is_null($categoryTitle)) return NULL;

		if (array_key_exists($categoryTitle->getPrefixedDBkey(), $image_urls)) {
			return $image_urls[$categoryTitle->getPrefixedDBkey()];
		}
		$catHasIconProperty = SMWPropertyValue::makeUserProperty(wfMsg('smw_ac_category_has_icon'));

		global $smwgDefaultStore;
		if ($smwgDefaultStore == 'SMWTripleStoreQuad') {
			$iconValues = smwfGetStore()->getPropertyValues($categoryTitle, $catHasIconProperty, NULL, '', true);
		} else {
			$iconValues = smwfGetStore()->getPropertyValues($categoryTitle, $catHasIconProperty, NULL, '');
		}
		$iconValue = reset($iconValues); // consider only first
		if ($iconValue === false) return NULL;

		$im_file = wfLocalFile($iconValue->getTitle());
		$url = !is_null($im_file) && $im_file instanceof File ? $im_file->getURL(): NULL;

		if (!is_null($url)) {
			$image_urls[$categoryTitle->getPrefixedDBkey()] = $url;
		}

		return $url;
	}

	function getDirectSubCategories(Title $categoryTitle, $requestoptions = NULL) {
		$result = "";
		$db =& wfGetDB( DB_SLAVE );
		$sql = 'page_namespace=' . NS_CATEGORY .
               ' AND page_is_redirect = 0 AND cl_to =' . $db->addQuotes($categoryTitle->getDBkey()) . ' AND cl_from = page_id'.
		USDBHelper::getSQLConditions($requestoptions,'page_title','page_title');

		$res = $db->select(  array($db->tableName('page'), $db->tableName('categorylinks')),
                            'page_title',
		$sql, 'SMW::getDirectSubCategories', USDBHelper::getSQLOptions($requestoptions,'page_title') );
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->page_title, NS_CATEGORY);
			}
		}
		$db->freeResult($res);
		return $result;
	}

	function getDirectSubProperties(Title $attribute, $requestoptions = NULL) {

		$result = "";
		$db =& wfGetDB( DB_SLAVE );
		$smw_ids = $db->tableName('smw_ids');
		$smw_subs2 = $db->tableName('smw_subp2');
		$page = $db->tableName('page');
		$sqlOptions = USDBHelper::getSQLOptionsAsString($requestoptions);

		$res = $db->query('SELECT o.smw_title AS subject_title FROM '.$smw_ids.' s JOIN '.$smw_subs2.' sub ON s.smw_id = sub.s_id JOIN '.$smw_ids.' o ON o.smw_id = sub.o_id '.
        ' AND s.smw_namespace = '.SMW_NS_PROPERTY. ' AND o.smw_namespace = '.SMW_NS_PROPERTY. ' AND s.smw_title = ' . $db->addQuotes($attribute->getDBkey()).' '.$sqlOptions);

		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->subject_title, SMW_NS_PROPERTY);

			}
		}
		$db->freeResult($res);
		return $result;
	}

	public function getPropertySubjects(array $properties, array $namespace, $requestoptions) {
		$db =& wfGetDB( DB_SLAVE );
		$smw_ids = $db->tableName('smw_ids');
		$smw_atts2 = $db->tableName('smw_atts2');
		$smw_rels2 = $db->tableName('smw_rels2');

		$namespaces = "";
		if ($namespace != NULL) {
			$namespaces .= '(';
			for ($i = 0, $n = count($namespace); $i < $n; $i++) {
				if ($i > 0) $namespaces .= ' OR ';
				$namespaces .= 's.smw_namespace='.$db->addQuotes($namespace[$i]);
			}
			if (count($namespace) == 0) $namespaces .= 'true';
			$namespaces .= ') ';
		} else  {
			$namespaces = 'true';
		}
		$propertyIDConstraint = "FALSE";
		foreach($properties as $p) {
			$p_id = smwfGetStore()->getSMWPropertyID($p);
			$propertyIDConstraint .= ' OR r.p_id = '.$p_id;
		}


		$titleConstraint1 = USDBHelper::getSQLConditions($requestoptions,'r.value_xsd','r.value_xsd');
		$titleConstraint2 = USDBHelper::getSQLConditions($requestoptions,'o.smw_title','o.smw_title');
		$query = '(SELECT s.smw_title AS title, s.smw_namespace AS ns FROM '.
		$smw_ids.' s JOIN '.$smw_atts2.' r ON s.smw_id = s_id WHERE '.$namespaces.' AND ('.$propertyIDConstraint.') '.$titleConstraint1.')'.
		'UNION '.
		'(SELECT s.smw_title AS title, s.smw_namespace AS ns FROM '.$smw_rels2.' r '.
		      'JOIN '.$smw_ids.' s ON r.s_id = s.smw_id JOIN '.$smw_ids.' o ON r.o_id = o.smw_id WHERE ('.$propertyIDConstraint.')  '.$titleConstraint2.') LIMIT 5';

		$res = $db->query($query );
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->title, $row->ns);
			}
		}
		$db->freeResult($res);
		return $result;
	}

	/**
	 * Returns a title if it matches the given term as single title.
	 * Case-insensitive. If the MySQL editdistance lib is installed it
	 * uses Jaro-Winkler metric to determine matches.
	 *
	 * @param string $term
	 * @return Title
	 */
	public function getSingleTitle($term, $ns = NULL) {
		$db =& wfGetDB( DB_SLAVE );
		$page = $db->tableName('page');
		$term = mysql_real_escape_string(str_replace(" ", "_", $term));

		if ($ns !== NULL) {
			$namespaceCond = " AND page_namespace = ".$ns;
		} else {
			$namespaceCond = "";
		}
		global $smwgUseEditDistance;
		if (isset($smwgUseEditDistance) && $smwgUseEditDistance === true) {
			$res = $db->query('SELECT page_title, page_namespace, JAROWINKLER(page_title, '.$db->addQuotes($term).') AS score FROM '.$page.
		                      ' WHERE JAROWINKLER(page_title, '.$db->addQuotes($term).") > 0.80 $namespaceCond ORDER BY score DESC");
		} else {
			$res = $db->query('SELECT page_title, page_namespace FROM '.$page.' WHERE UPPER(page_title) = '.$db->addQuotes($term));
		}
		$numRows = $db->numRows($res);
		if ($numRows > 1) {
			$db->freeResult($res);
			return NULL;
		}
		if ($numRows == 1) {
			$row = $db->fetchObject($res);
			$title = Title::newFromText($row->page_title, $row->page_namespace);
			$db->freeResult($res);
			return $title;
		}
		$db->freeResult($res);
		return NULL;
	}

	/**
	 * Gets all categories the given title is member of.
	 *
	 * @param Title $title
	 * @return array of Title
	 */
	public function getCategories($title) {
		$db =& wfGetDB( DB_SLAVE );
		$page = $db->tableName('page');
		$categorylinks = $db->tableName('categorylinks');

		$res = $db->query('SELECT cl_to FROM '.$page.' JOIN '.$categorylinks.' WHERE cl_from = page_id AND page_title = '.$db->addQuotes($title->getDBkey()). ' AND page_namespace = '.$title->getNamespace());
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->cl_to, NS_CATEGORY);
			}
		}
		$db->freeResult($res);
		return $result;
	}

	/**
	 * Gets all redirects which point to the given title.
	 *
	 * @param Title $title
	 * @return array of Title
	 */
	public function getRedirects($title) {
		$db =& wfGetDB( DB_SLAVE );
		$page = $db->tableName('page');
		$redirects = $db->tableName('redirect');

		$res = $db->query('SELECT rd_title, rd_namespace FROM '.$page.' JOIN '.$redirects.' WHERE rd_from = page_id AND page_title = '.$db->addQuotes($title->getDBkey()). ' AND page_namespace = '.$title->getNamespace());
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->rd_title, $row->rd_namespace);
			}
		}
		$db->freeResult($res);
		return $result;
	}

	/**
	 * Adds (or updates) a new search statistic with given hits.
	 *
	 * @param string $searchTerm
	 * @param int $hits
	 */
	public function addSearchTry($searchTerm, $hits) {
		$db =& wfGetDB( DB_MASTER );
		$smw_searchmatch = $db->tableName('smw_searchmatches');
		$res = $db->selectRow($smw_searchmatch, array('tries'), array('searchterm'=>$searchTerm));
		if ($res !== false) {
			$db->query('UPDATE '.$smw_searchmatch.' SET tries='.($res->tries+1).', hits='.$hits.' WHERE searchterm='.$db->addQuotes(mysql_real_escape_string($searchTerm)));
		} else {
			$db->query('INSERT INTO '.$smw_searchmatch.' VALUES ('.$db->addQuotes($searchTerm).',1,'.$hits.')');
		}
	}

	/**
	 * Returns search statistics
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param 0 or 1 $ascOrDesc
	 * @param 0 or 1 $sortFor where 0 = hits, 1 = tries
	 * @return array($row->searchterm, $row->tries, $row->hits);
	 */
	public function getSearchTries($limit, $offset, $ascOrDesc, $sortFor) {
		$db =& wfGetDB( DB_SLAVE );
		$smw_searchmatch = $db->tableName('smw_searchmatches');

		switch($ascOrDesc) {
			case 0: $ascOrDesc = "ASC";break;
			case 1: $ascOrDesc = "DESC";break;
			default: $ascOrDesc = "ASC";break;
		}

		switch($sortFor) {
			case 0: $sortFor = "hits $ascOrDesc, tries DESC";break;
			case 1: $sortFor = "tries $ascOrDesc, hits DESC";break;
			default: $sortFor = "hits $ascOrDesc, tries DESC";break;
		}

		$res = $db->select($smw_searchmatch, array('searchterm', 'tries', 'hits'), array(), '', array('LIMIT'=>$limit, 'OFFSET'=>$offset, 'ORDER BY'=>$sortFor));
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = array($row->searchterm, $row->tries, $row->hits);
			}
		}
		$db->freeResult($res);
		return $result;
	}

	public function getPageTitles($terms) {
		$db =& wfGetDB( DB_SLAVE );
		$page = $db->tableName('page');
			
		$requestoptions = new SMWRequestOptions();
		$requestoptions->isCaseSensitive = false;
		$requestoptions->limit = 50;
		$requestoptions->disjunctiveStrings = true;
		foreach($terms as $t) {
			if (strlen($t) < 3) continue; // do not add SKOS elements for matches with less than 3 letters .
			$t = str_replace(" ", "_", $t);
			$requestoptions->addStringCondition($t, SMWStringCondition::STRCOND_MID);
		}
		$sql = USDBHelper::getSQLConditions($requestoptions,'page_title','page_title');
		$res = $db->query('SELECT page_title, page_namespace FROM '.$page.' WHERE TRUE '.$sql);
		$result = array();
		if($db->numRows( $res ) > 0) {
			while($row = $db->fetchObject($res)) {
				$result[] = Title::newFromText($row->page_title, $row->page_namespace);
			}
		}
		$db->freeResult($res);
		return $result;
	}
	/**
	 * Setups database for EnhancedRetrieval extension
	 *
	 * @param boolean $verbose
	 */
	public function setup($verbose) {
		if ($verbose) print ("Creating tables for Enhanced retrieval...\n");
		$db =& wfGetDB( DB_MASTER );
		$smw_searchmatch = $db->tableName('smw_searchmatches');
		$db->query('CREATE TABLE IF NOT EXISTS '.$smw_searchmatch.' (searchterm VARCHAR(255), tries INTEGER, hits INTEGER)');
		if ($verbose) print("..done\n");
	}

	function drop($verbose) {
		global $wgDBtype;
		if ($verbose) print ("Deleting all database content and tables generated by Enhanced Retrieval ...\n\n");
		$db =& wfGetDB( DB_MASTER );
		$tables = array('smw_searchmatches');
		foreach ($tables as $table) {
			$name = $db->tableName($table);
			$db->query('DROP TABLE' . ($wgDBtype=='postgres'?'':' IF EXISTS'). $name, 'USStoreSQL::drop');
			if ($verbose) print (" ... dropped table $name.\n");
		}


		return true;
	}
}
