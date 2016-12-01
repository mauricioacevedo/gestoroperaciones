(function (root, factory) {
    define([
        "jquery",
        "converse-api",
        "underscore",
        "utils",
        "mock",
        "test_utils"
        ], factory);
} (this, function ($, converse_api, _, utils, mock, test_utils) {
    "use strict";
    var $iq = converse_api.env.$iq,
        Strophe = converse_api.env.Strophe;

    describe("A chat room", function () {

        afterEach(function () {
            converse_api.user.logout();
            converse_api.listen.not();
            test_utils.clearBrowserStorage();
        });

        it("can be bookmarked", mock.initConverse(function (converse) {
            var sent_stanza, IQ_id;
            var sendIQ = converse.connection.sendIQ;
            spyOn(converse.connection, 'sendIQ').andCallFake(function (iq, callback, errback) {
                sent_stanza = iq;
                IQ_id = sendIQ.bind(this)(iq, callback, errback);
            });
            spyOn(converse.connection, 'getUniqueId').andCallThrough();

            test_utils.openChatRoom(converse, 'theplay', 'conference.shakespeare.lit', 'JC');
            var jid = 'theplay@conference.shakespeare.lit';
            var view = converse.chatboxviews.get(jid);
            spyOn(view, 'renderBookmarkForm').andCallThrough();
            spyOn(view, 'cancelConfiguration').andCallThrough();

            var $bookmark = view.$el.find('.icon-pushpin');
            $bookmark.click();
            expect(view.renderBookmarkForm).toHaveBeenCalled();

            view.$el.find('.button-cancel').click();
            expect(view.cancelConfiguration).toHaveBeenCalled();
            expect($bookmark.hasClass('on-button'), false);

            $bookmark.click();
            expect(view.renderBookmarkForm).toHaveBeenCalled();

            /* Client uploads data:
             * --------------------
             *  <iq from='juliet@capulet.lit/balcony' type='set' id='pip1'>
             *      <pubsub xmlns='http://jabber.org/protocol/pubsub'>
             *          <publish node='storage:bookmarks'>
             *              <item id='current'>
             *                  <storage xmlns='storage:bookmarks'>
             *                      <conference name='The Play&apos;s the Thing'
             *                                  autojoin='true'
             *                                  jid='theplay@conference.shakespeare.lit'>
             *                          <nick>JC</nick>
             *                      </conference>
             *                  </storage>
             *              </item>
             *          </publish>
             *          <publish-options>
             *              <x xmlns='jabber:x:data' type='submit'>
             *                  <field var='FORM_TYPE' type='hidden'>
             *                      <value>http://jabber.org/protocol/pubsub#publish-options</value>
             *                  </field>
             *                  <field var='pubsub#persist_items'>
             *                      <value>true</value>
             *                  </field>
             *                  <field var='pubsub#access_model'>
             *                      <value>whitelist</value>
             *                  </field>
             *              </x>
             *          </publish-options>
             *      </pubsub>
             *  </iq>
             */
            expect(view.model.get('bookmarked')).toBeFalsy();
            var $form = view.$el.find('.chatroom-form');
            $form.find('input[name="name"]').val('Play&apos;s the Thing');
            $form.find('input[name="autojoin"]').prop('checked', true);
            $form.find('input[name="nick"]').val('JC');
            $form.submit();
            expect(view.model.get('bookmarked')).toBeTruthy();
            expect($bookmark.hasClass('on-button'), true);

            expect(sent_stanza.toLocaleString()).toBe(
                "<iq type='set' from='dummy@localhost/resource' xmlns='jabber:client' id='"+IQ_id+"'>"+
                    "<pubsub xmlns='http://jabber.org/protocol/pubsub'>"+
                        "<publish node='storage:bookmarks'>"+
                            "<item id='current'>"+
                                "<storage xmlns='storage:bookmarks'>"+
                                    "<conference name='Play&amp;apos;s the Thing' autojoin='true' jid='theplay@conference.shakespeare.lit'>"+
                                        "<nick>JC</nick>"+
                                    "</conference>"+
                                "</storage>"+
                            "</item>"+
                        "</publish>"+
                        "<publish-options>"+
                            "<x xmlns='jabber:x:data' type='submit'>"+
                                "<field var='FORM_TYPE' type='hidden'>"+
                                    "<value>http://jabber.org/protocol/pubsub#publish-options</value>"+
                                "</field>"+
                                "<field var='pubsub#persist_items'>"+
                                    "<value>true</value>"+
                                "</field>"+
                                "<field var='pubsub#access_model'>"+
                                    "<value>whitelist</value>"+
                                "</field>"+
                            "</x>"+
                        "</publish-options>"+
                    "</pubsub>"+
                "</iq>"
            );

            /* Server acknowledges successful storage
             *
             * <iq to='juliet@capulet.lit/balcony' type='result' id='pip1'/>
             */
            var stanza = $iq({
                'to':converse.connection.jid,
                'type':'result',
                'id':IQ_id
            });
            converse.connection._dataRecv(test_utils.createRequest(stanza));
            // We ignore this IQ stanza... (unless it's an error stanza), so
            // nothing to test for here.
        }));

        it("will be automatically opened if 'autojoin' is set on the bookmark", mock.initConverse(function (converse) {
            var jid = 'lounge@localhost';
            converse.bookmarks.create({
                'jid': jid,
                'autojoin': false,
                'name':  'The Lounge',
                'nick': ' Othello'
            });
            expect(_.isUndefined(converse.chatboxviews.get(jid))).toBeTruthy();

            jid = 'theplay@conference.shakespeare.lit';
            converse.bookmarks.create({
                'jid': jid,
                'autojoin': true,
                'name':  'The Play',
                'nick': ' Othello'
            });
            expect(_.isUndefined(converse.chatboxviews.get(jid))).toBeFalsy();
        }));

        describe("when bookmarked", function () {

            afterEach(function () {
                converse_api.user.logout();
                converse_api.listen.not();
                test_utils.clearBrowserStorage();
            });

            it("displays that it's bookmarked through its bookmark icon", mock.initConverse(function (converse) {
                runs(function () {
                    test_utils.openChatRoom(converse, 'lounge', 'localhost', 'dummy');
                });
                waits(100);
                runs(function () {
                    var view = converse.chatboxviews.get('lounge@localhost');
                    var $bookmark_icon = view.$('.icon-pushpin');
                    expect($bookmark_icon.hasClass('button-on')).toBeFalsy();
                    view.model.set('bookmarked', true);
                    expect($bookmark_icon.hasClass('button-on')).toBeTruthy();
                    view.model.set('bookmarked', false);
                    expect($bookmark_icon.hasClass('button-on')).toBeFalsy();
                });
            }));

            it("can be unbookmarked", mock.initConverse(function (converse) {
                var view, sent_stanza, IQ_id;
                var sendIQ = converse.connection.sendIQ;
                spyOn(converse.connection, 'sendIQ').andCallFake(function (iq, callback, errback) {
                    sent_stanza = iq;
                    IQ_id = sendIQ.bind(this)(iq, callback, errback);
                });
                spyOn(converse.connection, 'getUniqueId').andCallThrough();

                runs(function () {
                    test_utils.openChatRoom(converse, 'theplay', 'conference.shakespeare.lit', 'JC');
                });
                waits(100);
                runs(function () {
                    var jid = 'theplay@conference.shakespeare.lit';
                    view = converse.chatboxviews.get(jid);
                    spyOn(view, 'toggleBookmark').andCallThrough();
                    spyOn(converse.bookmarks, 'sendBookmarkStanza').andCallThrough();
                    view.delegateEvents();
                    converse.bookmarks.create({
                        'jid': view.model.get('jid'),
                        'autojoin': false,
                        'name':  'The Play',
                        'nick': ' Othello'
                    });
                    expect(converse.bookmarks.length).toBe(1);
                });
                waits(100);
                runs(function () {
                    expect(view.model.get('bookmarked')).toBeTruthy();
                    var $bookmark_icon = view.$('.icon-pushpin');
                    expect($bookmark_icon.hasClass('button-on')).toBeTruthy();
                    $bookmark_icon.click();
                    expect(view.toggleBookmark).toHaveBeenCalled();
                    expect($bookmark_icon.hasClass('button-on')).toBeFalsy();
                    expect(converse.bookmarks.length).toBe(0);
                    // Check that an IQ stanza is sent out, containing no
                    // conferences to bookmark (since we removed the one and
                    // only bookmark).
                    expect(sent_stanza.toLocaleString()).toBe(
                        "<iq type='set' from='dummy@localhost/resource' xmlns='jabber:client' id='"+IQ_id+"'>"+
                            "<pubsub xmlns='http://jabber.org/protocol/pubsub'>"+
                                "<publish node='storage:bookmarks'>"+
                                    "<item id='current'>"+
                                        "<storage xmlns='storage:bookmarks'/>"+
                                    "</item>"+
                                "</publish>"+
                                "<publish-options>"+
                                    "<x xmlns='jabber:x:data' type='submit'>"+
                                        "<field var='FORM_TYPE' type='hidden'>"+
                                            "<value>http://jabber.org/protocol/pubsub#publish-options</value>"+
                                        "</field>"+
                                        "<field var='pubsub#persist_items'>"+
                                            "<value>true</value>"+
                                        "</field>"+
                                        "<field var='pubsub#access_model'>"+
                                            "<value>whitelist</value>"+
                                        "</field>"+
                                    "</x>"+
                                "</publish-options>"+
                            "</pubsub>"+
                        "</iq>"
                    );
                });
            }));
        });

        describe("and when autojoin is set", function () {
            afterEach(function () {
                converse_api.user.logout();
                converse_api.listen.not();
                test_utils.clearBrowserStorage();
            });

            it("will be be opened and joined automatically upon login", mock.initConverse(function (converse) {
                spyOn(converse_api.rooms, 'open');
                var jid = 'theplay@conference.shakespeare.lit';
                var model = converse.bookmarks.create({
                    'jid': jid,
                    'autojoin': false,
                    'name':  'The Play',
                    'nick': ''
                });
                expect(converse_api.rooms.open).not.toHaveBeenCalled();
                converse.bookmarks.remove(model);

                converse.bookmarks.create({
                    'jid': jid,
                    'autojoin': true,
                    'name':  'Hamlet',
                    'nick': ''
                });
                expect(converse_api.rooms.open).toHaveBeenCalled();
            }));
        });
    });

    describe("Bookmarks", function () {

        afterEach(function () {
            converse_api.user.logout();
            converse_api.listen.not();
            test_utils.clearBrowserStorage();
        });

        it("can be pushed from the XMPP server", mock.initConverse(function (converse) {
            // TODO
            /* The stored data is automatically pushed to all of the user's
             * connected resources.
             *
             * Publisher receives event notification
             * -------------------------------------
             * <message from='juliet@capulet.lit'
             *         to='juliet@capulet.lit/balcony'
             *         type='headline'
             *         id='rnfoo1'>
             * <event xmlns='http://jabber.org/protocol/pubsub#event'>
             *     <items node='storage:bookmarks'>
             *     <item id='current'>
             *         <storage xmlns='storage:bookmarks'>
             *         <conference name='The Play&apos;s the Thing'
             *                     autojoin='true'
             *                     jid='theplay@conference.shakespeare.lit'>
             *             <nick>JC</nick>
             *         </conference>
             *         </storage>
             *     </item>
             *     </items>
             * </event>
             * </message>

             * <message from='juliet@capulet.lit'
             *         to='juliet@capulet.lit/chamber'
             *         type='headline'
             *         id='rnfoo2'>
             * <event xmlns='http://jabber.org/protocol/pubsub#event'>
             *     <items node='storage:bookmarks'>
             *     <item id='current'>
             *         <storage xmlns='storage:bookmarks'>
             *         <conference name='The Play&apos;s the Thing'
             *                     autojoin='true'
             *                     jid='theplay@conference.shakespeare.lit'>
             *             <nick>JC</nick>
             *         </conference>
             *         </storage>
             *     </item>
             *     </items>
             * </event>
             * </message>
             */
        }));

        it("can be retrieved from the XMPP server", mock.initConverse(function (converse) {
            var sent_stanza, IQ_id,
                sendIQ = converse.connection.sendIQ;
            spyOn(converse.connection, 'sendIQ').andCallFake(function (iq, callback, errback) {
                sent_stanza = iq;
                IQ_id = sendIQ.bind(this)(iq, callback, errback);
            });
            converse.emit('chatBoxesFetched');

            /* Client requests all items
             * -------------------------
             *
             *  <iq from='juliet@capulet.lit/randomID' type='get' id='retrieve1'>
             *  <pubsub xmlns='http://jabber.org/protocol/pubsub'>
             *      <items node='storage:bookmarks'/>
             *  </pubsub>
             *  </iq>
             */
            expect(sent_stanza.toLocaleString()).toBe(
                "<iq from='dummy@localhost/resource' type='get' xmlns='jabber:client' id='"+IQ_id+"'>"+
                "<pubsub xmlns='http://jabber.org/protocol/pubsub'>"+
                    "<items node='storage:bookmarks'/>"+
                "</pubsub>"+
                "</iq>"
            );

            /*
             * Server returns all items
             * ------------------------
             * <iq type='result'
             *     to='juliet@capulet.lit/randomID'
             *     id='retrieve1'>
             * <pubsub xmlns='http://jabber.org/protocol/pubsub'>
             *     <items node='storage:bookmarks'>
             *     <item id='current'>
             *         <storage xmlns='storage:bookmarks'>
             *         <conference name='The Play&apos;s the Thing'
             *                     autojoin='true'
             *                     jid='theplay@conference.shakespeare.lit'>
             *             <nick>JC</nick>
             *         </conference>
             *         </storage>
             *     </item>
             *     </items>
             * </pubsub>
             * </iq>
             */
            expect(converse.bookmarks.models.length).toBe(0);
            var stanza = $iq({'to': converse.connection.jid, 'type':'result', 'id':IQ_id})
                .c('pubsub', {'xmlns': Strophe.NS.PUBSUB})
                    .c('items', {'node': 'storage:bookmarks'})
                        .c('item', {'id': 'current'})
                            .c('storage', {'xmlns': 'storage:bookmarks'})
                                .c('conference', {
                                    'name': 'The Play&apos;s the Thing',
                                    'autojoin': 'true',
                                    'jid': 'theplay@conference.shakespeare.lit'
                                }).c('nick').t('JC').up().up()
                                .c('conference', {
                                    'name': 'Another room',
                                    'autojoin': 'false',
                                    'jid': 'another@conference.shakespeare.lit'
                                }).c('nick').t('JC').up().up();
            converse.connection._dataRecv(test_utils.createRequest(stanza));
            expect(converse.bookmarks.models.length).toBe(2);
            expect(converse.bookmarks.findWhere({'jid': 'theplay@conference.shakespeare.lit'}).get('autojoin')).toBe(true);
            expect(converse.bookmarks.findWhere({'jid': 'another@conference.shakespeare.lit'}).get('autojoin')).toBe(false);
        }));

        describe("The rooms panel", function () {
            afterEach(function () {
                converse_api.user.logout();
                converse_api.listen.not();
                test_utils.clearBrowserStorage();
            });

            it("shows a list of bookmarks", mock.initConverse(function (converse) {
                var IQ_id;
                var sendIQ = converse.connection.sendIQ;
                spyOn(converse.connection, 'sendIQ').andCallFake(function (iq, callback, errback) {
                    IQ_id = sendIQ.bind(this)(iq, callback, errback);
                });
                converse.chatboxviews.get('controlbox').$('#chatrooms dl.bookmarks').html('');
                converse.emit('chatBoxesFetched');
                var stanza = $iq({'to': converse.connection.jid, 'type':'result', 'id':IQ_id})
                    .c('pubsub', {'xmlns': Strophe.NS.PUBSUB})
                        .c('items', {'node': 'storage:bookmarks'})
                            .c('item', {'id': 'current'})
                                .c('storage', {'xmlns': 'storage:bookmarks'})
                                    .c('conference', {
                                        'name': 'The Play&apos;s the Thing',
                                        'autojoin': 'false',
                                        'jid': 'theplay@conference.shakespeare.lit'
                                    }).c('nick').t('JC').up().up()
                                    .c('conference', {
                                        'name': 'Bookmark with a very very long name that will be shortened',
                                        'autojoin': 'false',
                                        'jid': 'theplay@conference.shakespeare.lit'
                                    }).c('nick').t('JC').up().up()
                                    .c('conference', {
                                        'name': 'Another room',
                                        'autojoin': 'false',
                                        'jid': 'another@conference.shakespeare.lit'
                                    }).c('nick').t('JC').up().up();
                converse.connection._dataRecv(test_utils.createRequest(stanza));
                expect($('#chatrooms dl.bookmarks dd').length).toBe(3);
            }));

            it("remembers the toggle state of the bookmarks list", mock.initConverse(function (converse) {
                runs(function () {
                    converse.bookmarks.create({
                        'jid': 'theplay@conference.shakespeare.lit',
                        'autojoin': false,
                        'name':  'The Play',
                        'nick': ''
                    });
                    converse.emit('chatBoxesFetched');
                    test_utils.openControlBox().openRoomsPanel(converse);
                });
                waits(100);
                runs(function () {
                    expect($('#chatrooms dl.bookmarks dd:visible').length).toBe(1);
                    expect(converse.bookmarksview.list_model.get('toggle-state')).toBe(converse.OPENED);
                    $('#chatrooms .bookmarks-toggle').click();
                    expect($('#chatrooms dl.bookmarks dd:visible').length).toBe(0);
                    expect(converse.bookmarksview.list_model.get('toggle-state')).toBe(converse.CLOSED);
                    $('#chatrooms .bookmarks-toggle').click();
                    expect($('#chatrooms dl.bookmarks dd:visible').length).toBe(1);
                    expect(converse.bookmarksview.list_model.get('toggle-state')).toBe(converse.OPENED);
                });
            }));
        });
    });
}));
