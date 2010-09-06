<?php
/**
 * @file
 * @ingroup UnifiedSearchLanguages
 * 
 * @author: Kai K�hn
 * 
 * Created on: 27.01.2009
 *
 */
class US_LanguageEn {
    
    public $us_contentMessages = array('us_skos_preferedLabel' => 'Label',
									    'us_skos_altLabel' => 'Also known as',
									    'us_skos_hiddenLabel' => 'Rarely labeled as',
									    'us_skos_broader' => 'Broader term',
									    'us_skos_narrower' => 'Narrower term',
									    'us_skos_description' => 'Definition',
									    'us_skos_example' => 'Example',
									    'us_skos_term' => 'Term');
    
    public $us_userMessages = array (
        'us_search' => 'Enhanced Retrieval',
        'us_tolerance'=> 'Tolerance',
        'us_page_does_not_exist' => 'This page does not exist. $1',
        'us_similar_page_does_exist' => 'There is a page with a similar name: $1',
        'us_clicktocreate' => 'Click here to create the page.',
        'us_refinesearch' => 'Refine search',
        'us_browse_next' => 'next',
        'us_browse_prev' => 'prev',
        'us_results' => '<b>Results</b>',
        'us_noresults' => 'No results',
        'us_search_tooltip_refine' => 'Refine for $1',
        'us_noresults_text' => 'There are <b>no</b> fulltext results which match your search terms: <b>$1</b>  
                   <br><br>Proposals: <ul>
                   <li>Try other search terms.</li>
                   <li>Try search terms which are more general.</li>
                   <li>Make sure that the spelling is correct.</li></ul>',
        'us_resultinfo' => '<b>$1</b> - <b>$2</b> of <b>$3</b> for <b>$4</b>',
        'us_page' => 'Page',
        'us_searchfield' => 'Search for',
        'us_lastchanged'=> 'Last changed',
        'us_searchbutton' => 'Search',
        'us_isincat' => 'is in category',
        'us_article' => 'Article',
        'us_all' => 'No filter',
        'us_entries_per_page' => 'Results per page',
    
        'us_totalresults' => 'Total results',
       
        'us_didyoumean' => 'Did you mean',
        'us_showdescription' => 'Show description',
    
        'us_tolerantsearch' => 'tolerant',
	    'us_semitolerantsearch' => 'semi-tolerant',
	    'us_exactsearch' => 'exact',
    
        'unifiedsearchstatistics' => 'Unified Search Statistics',
        'us_statistics_docu' => 'Statistical information about search matches',
        'us_search_asc'=> 'Ascending',
        'us_search_desc' => 'Descending',
        'us_search_term'=>'Search term',
        'us_search_hits'=> 'Hits',
        'us_search_tries' => 'Tries',
        'us_go_button' => 'Go',
	    'us_sort_for'=>'Sort for',
	    'us_order_for'=>'Order',
		'us_termsappear' => 'These terms appear in this page',

        'us_includerules' => 'Include rules'
        
    );
    
    public $us_pathsearchMessages = array(
		'us_pathsearch_tab_fulltext' => 'Fulltext Search',
		'us_pathsearch_tab_path' => 'Path Search',
		'us_pathsearch_no_results' => 'There are <b>no</b> paths which match your search terms: <b>$1</b>',
		'us_pathsearch_show_all_results' => 'Show all results for this path ($1) ...',
		'us_pathsearch_no_instances' => 'No results were found for the current path',
		'us_pathsearch_error_in_path' => 'There is an error in this path',
		'us_pathsearch_result_popup_header' => 'All results for this path',
	);
    
}
