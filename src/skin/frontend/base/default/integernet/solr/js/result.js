var isFirstCall = true;

var updateLinks = function () {
    if (isFirstCall) {
        var links = $$('.block-layered-nav a', '.toolbar a', '.toolbar-bottom a');
        isFirstCall = false;
    } else {
        links = $$('.toolbar a', '.toolbar-bottom a');
    }

    links.each(function (element) {
        element.observe('click', function (e) {
            Event.stop(e);
            var url = element.href;

            var contentElement = $$('.col-main')[0];

            new Ajax.Updater(contentElement, url, {
                insertion: 'bottom',
                onSuccess: function(response) {
                    var lastChild = $$(".col-main > :last-child");
                    while (!lastChild[0].hasClassName('block-layered-nav')) {
                        lastChild[0].remove();
                        lastChild = $$(".col-main > :last-child");
                    }
                    console.log('calling');
                },
                onComplete: function (response) {
                    updateLinks();
                }
            });
        });
    });
};

document.observe("dom:loaded", function() {
    updateLinks();
});