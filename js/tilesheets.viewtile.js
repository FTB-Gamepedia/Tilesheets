/**
 * Forcefully inserts a Special:WhatUsesThisTile/EntryID into the toolbox section of the sidebar.
 */
function addWLHToToolbox() {
    var node = document.getElementById('p-tb')
        .getElementsByTagName('div')[0]
        .getElementsByTagName('ul')[0];

    var aNode = document.createElement('a');
    var liNode = document.createElement('li');

    aNode.appendChild(document.createTextNode(mw.message('tilesheet-tile-viewer-wlh')));

    const title = mw.config.get('wgTitle').split('/');

    aNode.setAttribute('href',
        mw.config.get('wgServer') +
        mw.config.get('wgArticlePath').replace('$1', 'Special:WhatUsesThisTile/') +
        title[title.length - 1]
    );
    liNode.appendChild(aNode);
    liNode.className = 'plainlinks';
    node.appendChild(liNode);
}

$.when(mw.loader.using(['mediawiki.api.messages', 'mediawiki.jqueryMsg']), $.ready)
    .then(function() {
        return new mw.Api().loadMessagesIfMissing(['tilesheet-tile-viewer-wlh']);
    })
    .then(addWLHToToolbox);