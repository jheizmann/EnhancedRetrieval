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
 * @ingroup FS_Special
 *
 * A special page for doing a faceted search on the semantic data in the wiki.
 *
 * @author Thomas Schweitzer
 */

if (!defined('MEDIAWIKI')) die();


global $IP;
require_once( $IP . "/includes/SpecialPage.php" );
$dir = dirname(__FILE__).'/';
require_once( $dir."../includes/FacetedSearch/FS_Settings.php" );


/*
 * Standard class that is responsible for the creation of the Special Page
 */
class FSFacetedSearchSpecial extends SpecialPage {
	
	//--- Constants ---
	
	const SPECIAL_PAGE_HTML = '
{{fs_ext_Top}}
<div id="wrapper"> 
	<div class="facets">
		<div>
			<span class="xfsComponentHeader">{{fs_selected}}</span>
			<div id="selection">
			</div>
		</div>
		<hr class="xfsSeparatorLine">
		<span class="xfsComponentHeader">{{fs_available_facets}}</span>
		<div>
			<span class="xfsFacetHeader">{{fs_categories}}</span>
			<div id="field_categories">
			</div>
		</div> 
		<div>
			<span class="xfsFacetHeader">{{fs_properties}}</span>
			<div id="field_properties">
			</div>
		</div> 
	</div>
	<div class="results" id="results">
		<div id="field_namespaces" class="xfsNamespaces">
		</div>
		<div class="search">
			<input type="text" id="query" name="query" value="{{searchTerm}}" />
			<input type="button" id="search_button" name="search" value="{{fs_search}}" />
			<span class="xfsSortOrder">
				{{fs_sort_by}}
				<select id="search_order" name="search_order" size="1">
					<option value="relevance">{{fs_relevance}}</option>
					<option value="newest" selected="selected">{{fs_newest_date_first}}</option>
					<option value="oldest">{{fs_oldest_date_first}}</option>
					<option value="ascending">{{fs_title_ascending}}</option>
					<option value="descending">{{fs_title_descending}}</option>
				</select>
			</span>
			<div id="create_article"/>
		</div>
		<hr class="xfsSeparatorLine">
		<div id="navigation">
			<div id="pager-header"></div>
		</div>
		<div id="docs">
			{{fs_search_results}}
		</div>
		<div id="xfsFooter">
			<ul id="pager"></ul>
		</div>
	</div>
</div>
<div class="xfsCurrentSearchLink">
	<hr class="xfsSeparatorLine">
	<span id="current_search_link"></span>
	{{fs_ext_BottomMenu}}
</div>
{{fs_ext_Bottom}}
';

    public function __construct() {
//        parent::__construct('FacetedSearch');
		parent::__construct('Search');
		global $wgHooks;
		$wgHooks['MakeGlobalVariablesScript'][] = "FSFacetedSearchSpecial::addJavaScriptVariables";
    }

    /**
     * Overloaded function that is responsible for the creation of the Special Page
     */
    public function execute($par) {
	
		global $wgOut, $wgRequest;
		
		$wgOut->setPageTitle(wfMsg('fs_title'));
		
		$search = str_replace( "\n", " ", $wgRequest->getText( 'search', '' ) );
		if ($search === wfMsg('smw_search_this_wiki')) {
			// If the help text of the search field is passed, assume an empty 
			// search string
			$search = '';
		}
		
		$restrict = $wgRequest->getText( 'restrict', '' );
		$specialPageTitle = $wgRequest->getText( 'title', '' );
		$t = Title::newFromText( $search );

		$fulltext = $wgRequest->getVal( 'fulltext', '' );
		$fulltext_x = $wgRequest->getVal( 'fulltext_x', '' );
		if ($fulltext == NULL && $fulltext_x == NULL) {
			
			# If the string cannot be used to create a title
			if(!is_null( $t ) ){

				# If there's an exact or very near match, jump right there.
				$t = SearchEngine::getNearMatch( $search );
				if( !is_null( $t ) ) {
					$wgOut->redirect( $t->getFullURL() );
					return;
				}

				# If just the case is wrong, jump right there.
//				$t = USStore::getStore()->getSingleTitle($search);
//				if (!is_null( $t ) ) {
//					$wgOut->redirect( $t->getFullURL() );
//					return;
//				}
			}
		}
        
		// Insert the search term into the input field of the UI
		$html = self::SPECIAL_PAGE_HTML;
		$html = str_replace('{{searchTerm}}', htmlspecialchars($search), $html);
		
		$html = $this->addExtensions($html);
		
		$wgOut->addHTML($this->replaceLanguageStrings($html));
		$wgOut->addModules('ext.facetedSearch.special');
    }
    
    /**
     * The HTML structure of Faceted Search offers sections for other extensions
     * that can inject their HTML. 
     * These sections are named {{fs_ext_X}} where X is variable e.g. {{fs_ext_Top}}.
     * For each such section a hook with the name FacetedSearchExtensionX is called
     * e.g. FacetedSearchExtensionTop.
     * Functions that are registered for this hook must have the following
     * signature
     * 
     * function fn(&$html)
     * 
     * The parameter $html contains the HTML that was assembled so far and the
     * function can augment it.
     * 
     * Finally the {{fs_ext_X}} section is replaced by the HTML.
     * 
     * After the HTML was collected, the hook FacetedSearchExtensionAddResources
     * is called where extensions should add their resources like scripts and 
     * styles. This function has no parameters i.e.
     * 
     * function fn()
     * 
     * @param {String} $pageHTML
     * 		The HTML of the whole page where the extensions are injected.
     * 
     * @return {String}
     * 		The modified HTML
     */
    public function addExtensions($pageHTML) {
    	if (preg_match_all("/{{fs_ext_(.*)}}/", $pageHTML, $matches, PREG_SET_ORDER)) {
    		// Collect the html from all extensions
    		foreach ($matches as $extensionPoint) {
    			$extp = $extensionPoint[0];
    			$hook = 'FacetedSearchExtension'.$extensionPoint[1];
    			$html = '';
    			wfRunHooks($hook, array(&$html));
    			
    			// Do the replacement in the HTML structure
    			$pageHTML = str_replace($extp, $html, $pageHTML);
    		}
    		
    		// Let the extensions add their resources
    		wfRunHooks('FacetedSearchExtensionAddResources', array());
    		
    	}
    	return $pageHTML;
    }

	/**
	 * Add a global JavaScript variable for the SOLR URL.
	 * @param $vars
	 * 		This array of global variables is enhanced with "wgFSSolrURL"
	 * 		and "wgFSCreateNewPageLink"
	 */
	public static function addJavaScriptVariables(&$vars) {
		global $fsgFacetedSearchConfig, $fsgCreateNewPageLink;
		$servlet = array_key_exists('proxyServlet', $fsgFacetedSearchConfig)
					? $fsgFacetedSearchConfig['proxyServlet']
					: '/solr/select';
		$port = array_key_exists('proxyPort', $fsgFacetedSearchConfig)
					? $fsgFacetedSearchConfig['proxyPort']
					: false;
					
		$solrURL = $fsgFacetedSearchConfig['proxyHost'];
		if ($port) {
			$solrURL .= ':' . $port;
		}
		
		$vars['wgFSSolrURL'] = $solrURL;
		$vars['wgFSSolrServlet'] = $servlet;
		$vars['wgFSCreateNewPageLink'] = $fsgCreateNewPageLink;
		
		return true;
	}
    
	/**
	 * Language dependent identifiers in $text that have the format {{identifier}}
	 * are replaced by the string that corresponds to the identifier.
	 * 
	 * @param string $text
	 * 		Text with language identifiers
	 * @return string
	 * 		Text with replaced language identifiers.
	 */
	private static function replaceLanguageStrings($text) {
		// Find all identifiers
		$numMatches = preg_match_all("/(\{\{(.*?)\}\})/", $text, $identifiers);
		if ($numMatches === 0) {
			return $text;
		}

		// Get all language strings
		$langStrings = array();
		foreach ($identifiers[2] as $id) {
			$langStrings[] = wfMsg($id);
		}
		
		// Replace all language identifiers
		$text = str_replace($identifiers[1], $langStrings, $text);
		return $text;
	}
    
}

