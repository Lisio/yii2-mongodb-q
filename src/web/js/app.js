var app = {
    router: {},
    controller: {},
    widgets: {},
    data: {}
};

$(document).ready(function() {
    var doc = $('body')
      , controller = doc.data('controller').split('-').map(function(v, k) {
            return k ? v.charAt(0).toUpperCase() + v.slice(1) : v;
        }).join('')
      , action = 'action' + doc.data('action').split('-').map(function(v, k) {
            return v.charAt(0).toUpperCase() + v.slice(1);
        }).join('');

    if (app.controller[controller] && app.controller[controller][action]) {
        app.controller[controller][action]();
    }

    $('[data-toggle="tooltip"]').tooltip();
});
