define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    /**
     * Worksheet Pagination view constructor
     */
    var WorksheetPagination = function(options) {
        this.el = {
            header: '[data-tornado-view="page-header"]',
            content: '[data-tornado-view="page-content"]',
            totalPages: '[data-paging-state="total"]',
            currentPage: '[data-paging-state="current"]',
            previousPage: '[data-paging-action="previous"]',
            nextPage: '[data-paging-action="next"]',
            pagingAction: '[data-paging-action]'
        };

        this.classes = {
            paginationDisabled: 'pagination-direction--disabled'
        };

        this.events = {
            namespace: '.worksheetpagination',
            click: 'click.worksheetpagination',
            changePage: 'changePage.worksheetPagination'
        };

        this.options = _.assign({
            perPage: 10,
            totalPages: 1,
            currentPage: 1,
            chartLength: 1
        }, options);
    };

    WorksheetPagination.prototype.bindEvents = function() {
        var _this = this;

        $(this.el.header).on('click' + this.events.namespace, this.el.nextPage, function(ev) {
            ev.preventDefault();

            this.displayPage(this.options.currentPage + 1);
        }.bind(this));

        $(this.el.header).on('click' + this.events.namespace, this.el.previousPage, function(ev) {
            ev.preventDefault();

            this.displayPage(this.options.currentPage - 1);
        }.bind(this));

        return this;
    };

    WorksheetPagination.prototype.unbindEvents = function() {
        $(this.el.header).off(this.events.namespace);

        return this;
    };

    WorksheetPagination.prototype.paginateCharts = function(charts) {
        if (this.options.perPage >= charts.length) {
            return charts;
        }

        return charts.slice(
            this.options.perPage * this.options.currentPage - this.options.perPage,
            this.options.perPage * this.options.currentPage
        );
    },

    WorksheetPagination.prototype.displayPage = function(page, refresh) {
        // Prevent changing a page when there's activity on the worksheet (ie: refresh)
        if ($(this.el.content).find('[data-blocker="block"]').length > 0) {
            return;
        }

        // only render if the page has actually changed, this stops the chart
        // from being rendered more than once
        if (refresh || this.options.currentPage !== page) {
            this.options.currentPage = page;
            $(this.el.header).trigger(this.events.changePage);
            this.render();
        }
    },

    WorksheetPagination.prototype.render = function() {
        var count = this.options.chartLength || 1; // at least 1 to avoid div by 0

        this.options.totalPages = Math.ceil(count / this.options.perPage);
        this.options.currentPage = Math.max(1, Math.min(this.options.currentPage, this.options.totalPages));

        $(this.el.totalPages).html(this.options.totalPages);
        $(this.el.currentPage).html(this.options.currentPage);

        if (this.options.currentPage === 1) {
            $(this.el.previousPage).addClass(this.classes.paginationDisabled);
        } else {
            $(this.el.previousPage).removeClass(this.classes.paginationDisabled);
        }

        if (this.options.currentPage >= this.options.totalPages) {
            $(this.el.nextPage).addClass(this.classes.paginationDisabled);
        } else {
            $(this.el.nextPage).removeClass(this.classes.paginationDisabled);
        }

        return this;
    };

    return WorksheetPagination;
});
