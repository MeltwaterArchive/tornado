define([
    'backbone',
    'models/PostModel'
], function (Backbone, PostModel) {

    'use strict';

    var PostsCollection = Backbone.Collection.extend({
        model: PostModel,
        comparator: function (post) {
            return -post.get('id');
        }
    });

    return PostsCollection;

});