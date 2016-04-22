document.observe("dom:loaded", function() {
    $$('.filter-show-more a').each(function(moreLink) {
        moreLink.observe('click', function(e) {
            Event.stop(e);
            if (moreLink.hasClassName('activated')) {
                moreLink.up(1).select('.filter-list-item-hidden').each(function(hiddenListItem) {
                    hiddenListItem.hide();
                });
                moreLink.removeClassName('activated');
            } else {
                moreLink.up(1).select('.filter-list-item-hidden').each(function(hiddenListItem) {
                    hiddenListItem.show();
                });
                moreLink.addClassName('activated');
            }
            var alternativeText = moreLink.readAttribute('data-alternative-text');
            moreLink.writeAttribute('data-alternative-text', moreLink.innerHTML);
            moreLink.innerHTML = alternativeText;
        });
    });
});