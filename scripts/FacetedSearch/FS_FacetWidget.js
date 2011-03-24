/*  Copyright 2011, ontoprise GmbH
 *  This file is part of the FacetedSearch-Extension.
 *
 *   The FacetedSearch-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The FacetedSearch-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * @file
 * @ingroup FacetedSearchScripts
 * @author: Thomas Schweitzer
 */

if (typeof FacetedSearch == "undefined") {
//	Define the FacetedSearch module	
	var FacetedSearch = { 
			classes : {}
	};
}

/**
 * @class FacetWidget
 * This class handles the facet fields.
 * 
 */
FacetedSearch.classes.FacetWidget = AjaxSolr.AbstractFacetWidget.extend({
	
	facetTheme: 'facet',
	removeSelectedFacet: true,
	
	setFacetTheme: function (facetTheme) {
		this.facetTheme = facetTheme;
	},
	setRemoveSelectedFacet: function (removeFacet) {
		this.removeSelectedFacet = removeFacet;
	},
	
	afterRequest: function () {
		if (this.noRender) {
			return;
		}
		
		var $ = jQuery;
		
		if (this.fields === undefined) {
			this.fields = [this.field];
		}
		
		var fq = this.manager.store.values('fq');

		var maxCount = 0;
		var objectedItems = [];
		for (var i = 0; i < this.fields.length; i++) {
			var field = this.fields[i];
			if (this.manager.response.facet_counts.facet_fields[field] === undefined) {
				continue;
			}
			for (var facet in this.manager.response.facet_counts.facet_fields[field]) {
				var count = parseInt(this.manager.response.facet_counts.facet_fields[field][facet]);
				if (count > maxCount) {
					maxCount = count;
				}
				if (this.removeSelectedFacet) {
					var fullName = field + ':' + facet;
					if ($.inArray(fullName, fq) >= 0) {
						continue;
					}
				}
				objectedItems.push({
					field: field,
					facet: facet,
					count: count
				});
			}
		}

		if (objectedItems.length == 0) {
			$(this.target).html(AjaxSolr.theme('no_items_found'));
			return;
		}
		
		objectedItems.sort(function(a, b) {
			return a.count > b.count ? -1 : 1;
		});
		
		// show facets using grouping
		var GROUP_SIZE = 10;
		var self = this;
		$(this.target).empty();
		for (var i = 0, l = objectedItems.length; i < l; i++) {
			if (i % GROUP_SIZE == 0) {
				var ntarget = $('<div>');
				if (i != 0) {
					$(ntarget).hide();
				}
				$(this.target).append(ntarget);
			}
			var facet = objectedItems[i].facet;
			var target;
			if (objectedItems[i].field == this.field) {
				target = self;
			} else {
				target = FacetedSearch.singleton.FacetedSearchInstance.getRelationWidget();
			}
			$(ntarget)
				.append(AjaxSolr.theme(this.facetTheme, facet, objectedItems[i].count, target.clickHandler(facet), FacetedSearch.classes.ClusterWidget.showPropertyDetailsHandler, false))
				.append('<br/>');
		}
		if (objectedItems.length > GROUP_SIZE) {
			$(this.target).append(AjaxSolr.theme('moreLessLink'));
		}
	},
	
	init: function () {
		var $ = jQuery;
		$('a.xfsFMore').live('click', function() {
			var morePresent = true;
			if ($(this).parent().children('div:hidden').filter(':first').show().end().length <= 1) {
				// Hide the link "more" and the following separator "|"
				$(this).hide();
				$(this).next().hide();
				morePresent = false;
			}
			if ($(this).parent().children('div:visible').length > 1) {
				// Show the link "less" and the preceding separator "|"
				var less = $(this).parent().children('a.xfsFLess');
				less.show();
				if (morePresent) {
					less.prev().show();
				}
			}
			return false;
		});
		$('a.xfsFLess').live('click', function() {
			var lessPresent = true;
			if ($(this).parent().children('div:visible').filter(':last').hide().end().length <= 2) {
				// Hide the link "less" and the preceding separator "|"
				$(this).hide();
				$(this).prev().hide();
				lessPresent = false;
			}
			if ($(this).parent().children('div:hidden').length >= 1) {
				$(this).parent().children('a.xfsFMore').show();
				if (lessPresent) {
					$(this).prev().show();
				}
			}
			return false;
		});
	}
});

