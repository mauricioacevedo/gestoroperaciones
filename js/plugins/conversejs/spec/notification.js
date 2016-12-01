(function (root, factory) {
    define(["mock", "converse-api", "test_utils"], factory);
} (this, function (mock, converse_api, test_utils) {
    "use strict";
    var $msg = converse_api.env.$msg;

    describe("Notifications", function () {
        // Implement the protocol defined in https://xmpp.org/extensions/xep-0313.html#config

        describe("When show_desktop_notifications is set to true", function () {
            describe("And the desktop is not focused", function () {
                describe("an HTML5 Notification", function () {
                    afterEach(function () {
                        converse_api.user.logout();
                        test_utils.clearBrowserStorage();
                    });

                    it("is shown when a new private message is received", mock.initConverse(function (converse) {
                        // TODO: not yet testing show_desktop_notifications setting
                        test_utils.createContacts(converse, 'current');
                        spyOn(converse, 'showMessageNotification');
                        spyOn(converse, 'areDesktopNotificationsEnabled').andReturn(true);

                        var message = 'This message will show a desktop notification';
                        var sender_jid = mock.cur_names[0].replace(/ /g,'.').toLowerCase() + '@localhost',
                            msg = $msg({
                                from: sender_jid,
                                to: converse.connection.jid,
                                type: 'chat',
                                id: (new Date()).getTime()
                            }).c('body').t(message).up()
                            .c('active', {'xmlns': 'http://jabber.org/protocol/chatstates'}).tree();
                        converse.chatboxes.onMessage(msg); // This will emit 'message'
                        expect(converse.areDesktopNotificationsEnabled).toHaveBeenCalled();
                        expect(converse.showMessageNotification).toHaveBeenCalled();
                    }));

                    it("is shown when you are mentioned in a chat room", mock.initConverse(function (converse) {
                        test_utils.createContacts(converse, 'current');
                        test_utils.openAndEnterChatRoom(converse, 'lounge', 'localhost', 'dummy');
                        var view = converse.chatboxviews.get('lounge@localhost');
                        if (!view.$el.find('.chat-area').length) { view.renderChatArea(); }
                        var no_notification = false;
                        if (typeof window.Notification === 'undefined') {
                            no_notification = true;
                            window.Notification = function () {
                                return {
                                    'close': function () {}
                                };
                            };
                        }
                        spyOn(converse, 'showMessageNotification').andCallThrough();
                        spyOn(converse, 'areDesktopNotificationsEnabled').andReturn(true);

                        var message = 'dummy: This message will show a desktop notification';
                        var nick = mock.chatroom_names[0],
                            msg = $msg({
                                from: 'lounge@localhost/'+nick,
                                id: (new Date()).getTime(),
                                to: 'dummy@localhost',
                                type: 'groupchat'
                            }).c('body').t(message).tree();
                        converse.chatboxes.onMessage(msg); // This will emit 'message'
                        expect(converse.areDesktopNotificationsEnabled).toHaveBeenCalled();
                        expect(converse.showMessageNotification).toHaveBeenCalled();
                        if (no_notification) {
                            delete window.Notification;
                        }
                    }));

                    it("is shown when a user changes their chat state", mock.initConverse(function (converse) {
                        // TODO: not yet testing show_desktop_notifications setting
                        test_utils.createContacts(converse, 'current');
                        spyOn(converse, 'areDesktopNotificationsEnabled').andReturn(true);
                        spyOn(converse, 'showChatStateNotification');
                        var jid = mock.cur_names[2].replace(/ /g,'.').toLowerCase() + '@localhost';
                        converse.roster.get(jid).set('chat_status', 'busy'); // This will emit 'contactStatusChanged'
                        expect(converse.areDesktopNotificationsEnabled).toHaveBeenCalled();
                        expect(converse.showChatStateNotification).toHaveBeenCalled();
                    }));
                });
            });

            describe("When a new contact request is received", function () {
                it("an HTML5 Notification is received", mock.initConverse(function (converse) {
                    spyOn(converse, 'areDesktopNotificationsEnabled').andReturn(true);
                    spyOn(converse, 'showContactRequestNotification');
                    converse.emit('contactRequest', {'fullname': 'Peter Parker', 'jid': 'peter@parker.com'});
                    expect(converse.areDesktopNotificationsEnabled).toHaveBeenCalled();
                    expect(converse.showContactRequestNotification).toHaveBeenCalled();
                }));
            });
        });

        describe("When play_sounds is set to true", function () {
            describe("A notification sound", function () {

                afterEach(function () {
                    converse_api.user.logout();
                    test_utils.clearBrowserStorage();
                });

                it("is played when the current user is mentioned in a chat room", mock.initConverse(function (converse) {
                    test_utils.createContacts(converse, 'current');
                    test_utils.openAndEnterChatRoom(converse, 'lounge', 'localhost', 'dummy');
                    converse.play_sounds = true;
                    spyOn(converse, 'playSoundNotification');
                    var view = converse.chatboxviews.get('lounge@localhost');
                    if (!view.$el.find('.chat-area').length) { view.renderChatArea(); }
                    var text = 'This message will play a sound because it mentions dummy';
                    var message = $msg({
                        from: 'lounge@localhost/otheruser',
                        id: '1',
                        to: 'dummy@localhost',
                        type: 'groupchat'
                    }).c('body').t(text);
                    view.onChatRoomMessage(message.nodeTree);
                    expect(converse.playSoundNotification).toHaveBeenCalled();

                    text = "This message won't play a sound";
                    message = $msg({
                        from: 'lounge@localhost/otheruser',
                        id: '2',
                        to: 'dummy@localhost',
                        type: 'groupchat'
                    }).c('body').t(text);
                    view.onChatRoomMessage(message.nodeTree);
                    expect(converse.playSoundNotification, 1);
                    converse.play_sounds = false;

                    text = "This message won't play a sound because it is sent by dummy";
                    message = $msg({
                        from: 'lounge@localhost/dummy',
                        id: '3',
                        to: 'dummy@localhost',
                        type: 'groupchat'
                    }).c('body').t(text);
                    view.onChatRoomMessage(message.nodeTree);
                    expect(converse.playSoundNotification, 1);
                    converse.play_sounds = false;
                }));
            });
        });
    });
}));
