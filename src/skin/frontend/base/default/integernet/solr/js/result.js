var SolrResult = Class.create();
SolrResult.prototype = {

    isFirstCall: true,
    
    initialize: function() {
        this.updateLinks();
    },

    updateResults: function (ajaxUrl) {

        var self = this;
        var contentElement = $$('.col-main')[0];
        new Ajax.Updater(contentElement, ajaxUrl, {
            insertion: 'bottom',
            onSuccess: function (response) {
                var lastChild = $$(".col-main > :last-child");
                while (!lastChild[0].hasClassName('block-layered-nav')) {
                    lastChild[0].remove();
                    lastChild = $$(".col-main > :last-child");
                }
            },
            onComplete: function (response) {

                self.updateLinks(ajaxUrl);
            }
        });
    },
    
    updateLinks: function (ajaxUrl) {
        this.updateLinkURLs(ajaxUrl);
        this.updateLinkObservers();
    },

    updateLinkURLs: function (url) {
        if (!url) {
            return;
        }
        var newParameters = this.getParametersAsArray(url);
        var originalParameters = this.getParametersAsArray(window.location.href);
        if (newParameters.length > originalParameters.length) {
            for (var index = 0; index < newParameters.length; index++) {
                var parameter = newParameters[index];
                if (originalParameters.indexOf(parameter) == -1) {
                    this.addParamToFilterUrls(parameter);
                }
            }
        } else {

        }
    },

    updateLinkObservers: function () {
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
    },

    getParametersAsArray: function(url) {
        var parametersPart = url.substr(url.indexOf('?') + 1);
        return parametersPart.split('&');
    },
    
    addParamToFilterUrls: function (newParameter) {
        var self = this;
        $$('.block-layered-nav a').each(function (link) {
            self.addParamToLink(newParameter, link);
        });
    },

    addParamToLink: function (newParameter, link) {
        var newParameterParts = newParameter.split('=');
        var newParameterKey = newParameterParts[0];
        var newParameterValue = newParameterParts[1];

        var linkUrl = link.href;
        var linkParams = this.getParametersAsArray(linkUrl);

        if (linkUrl.indexOf('&' + newParameterKey + '=') != -1 || linkUrl.indexOf('?' + newParameterKey + '=') != -1) {
            for (var index = 0; index < linkParams.length; index++) {
                var linkParameter = linkParams[index];
                var linkParameterParts = linkParameter.split('=');
                var linkParameterKey = linkParameterParts[0];
                var linkParameterValue = linkParameterParts[1];
                if (linkParameterKey == newParameterKey) {
                    if (linkParameterValue == newParameterValue) {
                        linkUrl = linkUrl.replace('&' + newParameter, '').replace('?' + newParameter, '');
                    } else {
                        
                    }
                }
            }
        } else {
            if (linkParams.indexOf(newParameter) != -1) {
                linkUrl = linkUrl.replace('&' + newParameter, '').replace('?' + newParameter, '');
            } else {
                if (linkUrl.indexOf('?')) {
                    linkUrl = linkUrl + '&' + newParameter;
                } else {
                    linkUrl = linkUrl + '?' + newParameter;
                }
            }
        }
        link.href = linkUrl;
    }

};


document.observe("dom:loaded", function() {
    var solrResult = new SolrResult();
});