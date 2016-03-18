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
        } else if (newParameters.length < originalParameters.length) {
            for (var index = 0; index < originalParameters.length; index++) {
                var parameter = originalParameters[index];
                if (newParameters.indexOf(parameter) == -1) {
                    this.removeParamFromFilterUrls(parameter);
                }
            }
        } else {
            for (var index = 0; index < newParameters.length; index++) {
                var newParameter = newParameters[index];
                var newParameterParts = newParameter.split('=');
                var newParameterKey = newParameterParts[0];
                for (var originalIndex = 0; originalIndex < originalParameters.length; originalIndex++) {
                    var originalParameter = originalParameters[originalIndex];
                    var originalParameterParts = originalParameter.split('=');
                    var originalParameterKey = originalParameterParts[0];

                    if (originalParameterKey == newParameterKey) {
                        this.removeParamFromFilterUrls(newParameter);
                    }
                }
            }
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
    
    addParamToFilterUrls: function (parameterToAdd) {
        var self = this;
        $$('.block-layered-nav a').each(function (link) {
            self.toggleParameterInLink(parameterToAdd, link);
        });
    },

    removeParamFromFilterUrls: function (parameterToRemove) {
        var self = this;
        $$('.block-layered-nav a').each(function (link) {
            self.toggleParameterInLink(parameterToRemove, link, true);
        });
    },

    replaceParam: function (oldUrl, oldParameter, newParameter) {
        var newUrl = oldUrl
            .replace('&' + oldParameter + '&', '&' + newParameter)
            .replace('?' + oldParameter + '&', '?' + newParameter);
        if (newUrl == oldUrl) {
             newUrl = oldUrl
                .replace('&' + oldParameter, newParameter)
                .replace('?' + oldParameter, newParameter);
        }
        return newUrl

    },

    toggleParameterInLink: function (parameterToToggle, link, removeOnly) {
        console.log('parameterToToggle: ' + parameterToToggle);
        console.log('link.href: ' + link.href);
        var parameterToToggleParts = parameterToToggle.split('=');
        var parameterToToggleKey = parameterToToggleParts[0];
        var parameterToToggleValue = parameterToToggleParts[1].toString();

        var linkUrl = link.href;
        var linkParams = this.getParametersAsArray(linkUrl);

        if (linkUrl.indexOf('&' + parameterToToggleKey + '=') != -1 || linkUrl.indexOf('?' + parameterToToggleKey + '=') != -1) {
            for (var index = 0; index < linkParams.length; index++) {
                var linkParameter = linkParams[index];
                var linkParameterParts = linkParameter.split('=');
                var linkParameterKey = linkParameterParts[0];
                var linkParameterValue = linkParameterParts[1].toString();
                if (linkParameterKey == parameterToToggleKey) {
                    if (linkParameterValue == parameterToToggleValue) {
                        console.log(1);
                        linkUrl = this.replaceParam(linkUrl, parameterToToggle, '');
                    } else {
                        var linkParameterValues = linkParameterValue.split(encodeURIComponent(','));
                        console.log(parameterToToggle);
                        console.log(linkParameterValues);
                        if (linkParameterValues.indexOf(parameterToToggleValue) != -1) {
                            delete linkParameterValues[linkParameterValues.indexOf(parameterToToggleValue)];
                            linkParameterValues = linkParameterValues.filter(Number)
                        } else if (!removeOnly) {
                            linkParameterValues.push(parameterToToggleValue);
                        }
                        console.log(linkParameterValues);
                        console.log(' ');
                        linkUrl = this.replaceParam(linkUrl, linkParameter, linkParameterKey + '=' + linkParameterValues.join(encodeURIComponent(',')));
                    }
                }
            }
        } else {
            console.log(2);
            if (linkParams.indexOf(parameterToToggle) != -1) {
                linkUrl = this.replaceParam(linkUrl, parameterToToggle, '');
            } else {
                if (linkUrl.indexOf('?')) {
                    linkUrl = linkUrl + '&' + parameterToToggle;
                } else {
                    linkUrl = linkUrl + '?' + parameterToToggle;
                }
            }
        }
        link.href = linkUrl;
    },

    removeParamFromLink: function (parameterToRemove, link) {
        var parameterToRemoveParts = parameterToRemove.split('=');
        var parameterToRemoveKey = parameterToRemoveParts[0];
        var parameterToRemoveValue = parameterToRemoveParts[1];

        var linkUrl = link.href;
        var linkParams = this.getParametersAsArray(linkUrl);

        if (linkUrl.indexOf('&' + parameterToRemoveKey + '=') != -1 || linkUrl.indexOf('?' + parameterToRemoveKey + '=') != -1) {
            for (var index = 0; index < linkParams.length; index++) {
                var linkParameter = linkParams[index];
                var linkParameterParts = linkParameter.split('=');
                var linkParameterKey = linkParameterParts[0];
                var linkParameterValue = linkParameterParts[1];
                if (linkParameterKey == parameterToRemoveKey) {
                    if (linkParameterValue == parameterToRemoveValue) {
                        linkUrl = this.replaceParam(linkUrl, parameterToRemove, '');
                    } else {
                        var linkParameterValues = linkParameterValue.split(encodeURIComponent(','));
                        if (linkParameterValues.indexOf(parameterToRemoveKey) != -1) {
                            delete linkParameterValues[linkParameterValues.indexOf(parameterToRemoveKey)];
                        } else {
                            linkParameterValues.push(parameterToRemoveValue);
                        }
                        linkUrl = this.replaceParam(linkUrl, linkParameter, linkParameterKey + '=' + linkParameterValues.join(encodeURIComponent(',')));
                    }
                }
            }
        }
        link.href = linkUrl;
    }

};


document.observe("dom:loaded", function() {
    var solrResult = new SolrResult();
});