var SolrTabs = Class.create();
SolrTabs.prototype = {

    contentElements: null,
    container: null,

    initialize: function() {
        var self = this;
        this.container = $('solr_tabs_container');
        this.container.select('.solr-tab a').each(function(tabLinkItem) {
            tabLinkItem.observe('click', function(event) {
                event.preventDefault();
                self.activateTab(tabLinkItem.id);
            })
        });
    },

    activateTab: function (activeTabLinkId) {
        this.container.select('.solr-tab a').each(function(tabLinkItem) {
            tabLinkItem.removeClassName('active');
        });
        $(activeTabLinkId).addClassName('active');
        var allContentElements = this.getContentElements();
        Object.keys(allContentElements).forEach(function(tabLinkId) {
            var contentElements = allContentElements[tabLinkId];
            if (contentElements) {
                if (!Array.isArray(contentElements)) {
                    contentElements = [contentElements];
                }
                if (tabLinkId == activeTabLinkId) {
                    contentElements.each(function(contentElement) {
                        contentElement.show();
                    });
                } else {
                    contentElements.each(function(contentElement) {
                        contentElement.hide();
                    });
                }
            }
        });
    },

    getContentElements: function() {
        return {
            'solr_tab_link_cms': $('solr_tab_content_cms'),
            'solr_tab_link_categories': $('solr_tab_content_categories'),
            'solr_tab_link_products': $$('.note-msg, .category-products', '.block-layered-nav')
        }
    }
};