angular.module('converse', []).service('converse', function() {
    // We create three promises, which will be resolved at various times
    var loaded_deferred = new $.Deferred(), // Converse.js has been loaded
        connected_deferred = new $.Deferred(), // An XMPP connection has been established
        roster_deferred = new $.Deferred(); // The contacts roster has been fetched.

    var loaded_promise = loaded_deferred.promise(),
        connected_promise = connected_deferred.promise(),
        roster_promise = roster_deferred.promise();

    // This is the API of the service.
    var service = {
        'waitUntilLoaded': _.constant(loaded_promise),
        'initialize': function initConverse(options) {
            this.waitUntilLoaded().done(_.partial(this.api.initialize, options));
        },
        'waitUntilConnected': _.constant(connected_promise),
        'waitUntilRosterFetched': _.constant(roster_promise),
    };

    // Here we define the core components of converse.js that will be
    // loaded and used.
    define("converse", [
        "converse-api",
        // START: Removable components
        // --------------------
        // Any of the following components may be removed if they're not needed.
        "locales",               // Translations for converse.js. This line can be removed
                                 // to remove *all* translations, or you can modify the
                                 // file src/locales.js to include only those
                                 // translations that you care about.

        "converse-chatview",     // Renders standalone chat boxes for single user chat
        "converse-controlbox",   // The control box
        "converse-bookmarks",    // XEP-0048 Bookmarks
        "converse-mam",          // XEP-0313 Message Archive Management
        "converse-muc",          // XEP-0045 Multi-user chat
        "converse-vcard",        // XEP-0054 VCard-temp
        "converse-otr",          // Off-the-record encryption for one-on-one messages
        "converse-register",     // XEP-0077 In-band registration
        "converse-ping",         // XEP-0199 XMPP Ping
        "converse-notification", // HTML5 Notifications
        "converse-minimize",     // Allows chat boxes to be minimized
        "converse-dragresize",   // Allows chat boxes to be resized by dragging them
        "converse-headline",     // Support for headline messages
        // END: Removable components

    ], function(converse_api) {
        service.api = converse_api;
        return deferred.resolve();
    });
    require(["converse"]);
    return service;
});
