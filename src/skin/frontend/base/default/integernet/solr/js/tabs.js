var SolrTabs = Class.create();
SolrTabs.prototype = {

    contentElements: null,
    container: null,

    initialize: function() {
        var self = this;
        this.container = $('solr_tabs_container');
        this.container.select('.solr-tab a').each(function(tabLinkItem) {
            tabLinkItem.observe('click', function(item) {
                self.activateTab(tabLinkItem.id);
            })
        });
    },

    activateTab: function (activeTabLinkId) {
        this.container.select('.solr-tab a').each(function(tabLinkItem) {
            tabLinkItem.removeClassName('active');
        });
        $(activeTabLinkId).addClassName('active');
        var contentElements = this.getContentElements();
        console.log(contentElements);
        Object.keys(contentElements).forEach(function(tabLinkId) {
            var contentElement = contentElements[tabLinkId];
            if (contentElement) {
                if (tabLinkId == activeTabLinkId) {
                    contentElement.show();
                } else {
                    contentElement.hide();
                }
            }
        });
    },

    getContentElements: function() {
        return {
            'solr_tab_link_cms': $('solr_tab_content_cms'),
            'solr_tab_link_categories': $('solr_tab_content_categories'),
            'solr_tab_link_products': $$('.note-msg, .category-products')[0]
        }
    }
};