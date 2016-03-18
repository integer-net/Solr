var SolrResult = Class.create();
SolrResult.prototype = {

    isFirstCall: true,
    
    initialize: function() {
        this.updateLinks();
    },

    updateResults: function (url) {
        var self = this;
        var contentElement = $$('.col-main')[0];
        new Ajax.Updater(contentElement, url, {
            insertion: 'bottom',
            onSuccess: function (response) {
                var lastChild = $$(".col-main > :last-child");
                while (!lastChild[0].hasClassName('block-layered-nav')) {
                    lastChild[0].remove();
                    lastChild = $$(".col-main > :last-child");
                }
            },
            onComplete: function (response) {
                self.updateLinks();
            }
        });
    }, 
    
    updateLinks: function () {
        var self = this;
        var links;
        if (this.isFirstCall) {
            links = $$('.block-layered-nav a', '.toolbar a', '.toolbar-bottom a');
            this.isFirstCall = false;
        } else {
            links = $$('.toolbar a', '.toolbar-bottom a');
        }

        links.each(function (element) {
            element.observe('click', function (e) {
                Event.stop(e);
                var url = element.href;

                self.updateResults(url);
            });
        });

        var dropdowns = $$('.toolbar select', '.toolbar-bottom select');

        dropdowns.each(function (element) {
            element.onchange = undefined;
            element.observe('change', function (e) {
                
                var url = element.value;

                self.updateResults(url);
            });
        });
    }
};


document.observe("dom:loaded", function() {
    var solrResult = new SolrResult();
});