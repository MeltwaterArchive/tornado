define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    /**
     * LocalStorage constructor
     *
     * @singleton
     */
    var LocalStorage = function () {};

    /**
     * Create an item in Local Storage
     *
     * @param  {String} itemName
     * @param  {Mixed} itemValue
     */
    LocalStorage.prototype.createItem = function (itemName, itemValue) {

        // stringify objects
        if (typeof itemValue === 'object') {
            itemValue = JSON.stringify(itemValue);
        }

        itemValue = itemValue || JSON.stringify({});

        localStorage.setItem(itemName, itemValue);

        return this;
    };

    /**
     * Retrieve a localStorage item
     *
     * @param  {String} itemName Item name
     * @return {Mixed}           Item value
     */
    LocalStorage.prototype.getItem = function (itemName) {
        return JSON.parse(localStorage.getItem(itemName));
    };

    /**
     * Save an item to Local Storage
     *
     * @todo remove this function since it's just a copy of create item
     *
     * @param  {String} itemName
     * @param  {Mixed} itemValue
     */
    LocalStorage.prototype.saveItem = function (itemName, itemValue) {
        this.createItem(itemName, itemValue);
    };

    return new LocalStorage();
});