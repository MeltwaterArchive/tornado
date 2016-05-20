define([
    'jquery', 
    'underscore', 
    'mustache', 
    'selectide', 
    'csdleditor', 
    'rangeslider', 
    'collections/region', 
    'plugins/scrollfoo', 
    'hbs!templates/filters/filters-options',
    'templates/helpers/hasFilterOption',
    'views/base',
    'views/sidebar/TimeframeSidebarView'
], function(
    $, 
    _, 
    Mustache, 
    Selectide, 
    CSDLEditor, 
    RangeSlider, 
    RegionCollection, 
    ScrollFoo, 
    FilterOptionsTpl,
    hasFilterOptions,
    View,
    TimeframeSidebarView
) {
    'use strict';

    /**
     * Renders the filters template
     *
     * @param {data} data Object that contains filters, start, end.
     */
    var FiltersOptionsView = View.extend({
        el: '[data-tornado-view="filters-options"]',
        template: FilterOptionsTpl,

        filterClearEl: '[data-filter-clear]',
        filterClearAttr: 'data-filter-clear',

        keywordsEl: '.page-sidebar__section--keywords',
        keywordsSelectEl: '.filters-select--keywords',
        keywordsSelect: null,

        linksEl: '.page-sidebar__section--links',
        linksSelectEl: '.filters-select--links',
        linksSelect: null,

        countryEl: '.page-sidebar__section--country',
        countrySelectEl: '.filters-select--country',
        countrySelect: null,

        regionEl: '.page-sidebar__section--region',
        regionSelectEl: '.filters-select--region',
        regionSelect: null,

        genderEl: '.filters-content--gender',
        genderSelectEl: '.filters-select--gender',
        genderSelect: null,

        ageEl: '.page-sidebar__section--age',
        ageCheckboxesEl: '.filters-checklist--age .filters-checklist__checkbox',

        timeframeEl: '.page-sidebar__section--timeframe',
        timeframeInput: '[data-filter-timeframe-input]',
        timeframeDisabledClass: 'page-sidebar__section--timeframe--disabled',

        csdlEl: '.page-sidebar__section--csdl',
        csdlInputEl: '[data-filter-csdl-input]',

        bindEvents: function() {
            var _this = this;

            $(this.el).on('click.filtersoptions', this.filterClearEl, function(ev) {
                ev.preventDefault();
                ev.stopPropagation();

                _this.clearFilter($(this).attr(_this.filterClearAttr));
            });

            return this;
        },

        unbindEvents: function() {
            $(this.el).off('.filtersoptions');

            return this;
        },

        getValues: function() {
            var filters = {
                start: Math.floor(parseInt($(this.el).find('[name="timeframe-start"]').val(), 10) / 1000),
                end: Math.floor(parseInt($(this.el).find('[name="timeframe-end"]').val(), 10) / 1000),
                keywords: this.getKeywordsSelection(),
                links: this.getLinksSelection(),
                country: this.getCountrySelection(),
                region: this.getRegionSelection(),
                gender: this.getGenderSelection(),
                age: this.getAgeSelection(),
                csdl: this.getCsdl(),
                timeframe: $(this.el).find('[name="timeframe-name"]').val()
            };
            return filters;
        },

        clearFilter: function(filter) {
            switch (filter) {
                case 'keywords':
                    this.clearKeywordsSelect();
                    break;
                case 'links':
                    this.clearLinksSelect();
                    break;
                case 'country':
                    this.clearCountrySelect();
                    break;
                case 'region':
                    this.clearRegionSelect();
                    break;
                case 'gender':
                    this.clearGenderSelect();
                    break;
                case 'age':
                    this.clearAgeSelect();
                    break;
                case 'timeframe':
                    this.clearTimeframe();
                    break;
                case 'csdl':
                    this.clearCsdlEditor();
                    break;
            }
        },

        initKeywordsSelect: function() {

            if (!hasFilterOptions(this.data.analysis, 'keywords')) {
                return this;
            }

            var _this = this;
            var keywords = this.data.filters.keywords || [];

            // first we need to set the value on the input to make selected keywords available as options
            $(this.keywordsSelectEl).val(keywords.join(','));

            this.keywordsSelect = new Selectide(this.keywordsSelectEl, {
                delimeter: ',',
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    };
                }
            });

            // then we need to select those keywords
            _.each(keywords || [], function(keyword) {
                _this.keywordsSelect.select(keyword);
            });

            return this;
        },

        getKeywordsSelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'keywords')) {
                return null;
            }
            // this is to prevent getting an array of empty string for no selection
            var value = this.keywordsSelect.getValue().trim();
            value = value.length ? value.split(',') : [];
            return value;
        },

        clearKeywordsSelect: function() {
            return this.keywordsSelect.clearSelection();
        },

        initLinksSelect: function() {
            if (!hasFilterOptions(this.data.analysis, 'links')) {
                return this;
            }
            var _this = this;
            var links = this.data.filters.links || [];

            // first we need to set the value on the input to make selected links available as options
            $(this.linksSelectEl).val(links.join(','));

            this.linksSelect = new Selectide(this.linksSelectEl, {
                delimeter: ',',
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    };
                }
            });

            // then we need to select those links
            _.each(links || [], function(link) {
                _this.linksSelect.select(link);
            });

            return this;
        },

        getLinksSelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'links')) {
                return null;
            }
            // this is to prevent getting an array of empty string for no selection
            var value = this.linksSelect.getValue().trim();
            value = value.length ? value.split(',') : [];
            return value;
        },

        clearLinksSelect: function() {
            return this.linksSelect.clearSelection();
        },

        initCountrySelect: function() {
            if (!hasFilterOptions(this.data.analysis, 'country')) {
                return this;
            }
            this.countrySelect = new Selectide(this.countrySelectEl, {
                onItemAdd: function() {
                    this.close();
                    this.blur();
                }
            });

            // get all countries and add them to selectide
            RegionCollection.getCountries().then(function(countries) {
                this.countrySelect.add(_.map(countries, function(country) {
                    return {text: country, value: country};
                }));

                // only when all regions are added, we can apply previous selection
                _.each(this.data.filters.country || [], function(country) {
                    this.countrySelect.select(country);
                }.bind(this));
            }.bind(this));

            return this;
        },

        getCountrySelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'country')) {
                return null;
            }
            return this.countrySelect.getValue();
        },

        clearCountrySelect: function() {
            return this.countrySelect.clearSelection();
        },

        initRegionSelect: function() {
            if (!hasFilterOptions(this.data.analysis, 'region')) {
                return this;
            }
            this.regionSelect = new Selectide(this.regionSelectEl, {
                onItemAdd: function() {
                    this.close();
                    this.blur();
                }
            });

            // get all regions and add them to selectide
            RegionCollection.getRegions().then(function(regions) {
                this.regionSelect.add(_.map(regions, function(region) {
                    return {text: region, value: region};
                }));

                // only when all regions are added, we can apply previous selection
                _.each(this.data.filters.region || [], function(region) {
                    this.regionSelect.select(region);
                }.bind(this));
            }.bind(this));

            return this;
        },

        getRegionSelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'region')) {
                return null;
            }
            return this.regionSelect.getValue();
        },

        clearRegionSelect: function() {
            this.regionSelect.clearSelection();
        },

        initGenderSelect: function() {
            if (!hasFilterOptions(this.data.analysis, 'gender')) {
                return this;
            }
            this.genderSelect = new Selectide(this.genderSelectEl, {
                items: this.data.filters.gender || [],
                onItemAdd: function() {
                    this.close();
                    this.blur();
                },
                options: [
                    {text: 'Female', value: 'female'},
                    {text: 'Male', value: 'male'},
                    {text: 'Unknown', value: 'unknown'}
                ]
            });
            return this;
        },

        getGenderSelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'gender')) {
                return null;
            }
            return this.genderSelect.getValue();
        },

        clearGenderSelect: function() {
            this.genderSelect.clearSelection();
        },

        initAgeSelect: function() {
            if (!hasFilterOptions(this.data.analysis, 'age-range')) {
                return this;
            }
            _.each(this.data.filters.age || [], function(age) {
                $(this.ageCheckboxesEl)
                    .filter('[value="' + age + '"]')
                        .prop('checked', true);
            }.bind(this));
            return this;
        },

        getAgeSelection: function() {
            if (!hasFilterOptions(this.data.analysis, 'age-range')) {
                return null;
            }
            var ages = [];

            $(this.ageCheckboxesEl)
                .filter(':checked')
                    .each(function() {
                        ages.push(this.value);
                    });

            return ages;
        },

        clearAgeSelect: function() {
            $(this.ageCheckboxesEl).prop('checked', false);
        },

        /**
         * Generates a set of options for the time slider based on current worksheet and recording.
         *
         * @return {Object}
         */
        timeSliderOptions: function() {
            // minimum range cannot be older than 32 days
            var pylonLimit = new Date(_.now() - (1000 * 60 * 60 * 24 * 32));
            pylonLimit = new Date(pylonLimit.getFullYear(), pylonLimit.getMonth(), pylonLimit.getDate(), 0, 1);
            var options = {
                min: pylonLimit,
                max: _.now(),
                from: (this.data.filters.start) ? Math.max(this.data.filters.start * 1000, pylonLimit) : null,
                to: this.data.filters.end * 1000 || null,
                step: 1000 * 60 * 60, // step by an hour by default
                maxInterval: null,
                timeframe: this.data.filters.timeframe || ''
            };

            return options;
        },

        initCSDLEditor: function() {
            if(!hasFilterOptions(this.data.analysis, 'csdl')){
                return this;
            }
            /* init csdl editor */
            this.editor = new CSDLEditor.Editor($(this.csdlEl), {
                value: this.data.filters.csdl || '',
                config: {
                    targets: this.data.targets
                }
            });

            // hack to make the csdl editor display current value
            setTimeout(function() {
                this.editor.codeMirror.refresh();
            }.bind(this), 10);

            return this;
        },

        getCsdl: function() {
            return this.editor.value();
        },

        clearCsdlEditor: function() {
            this.editor.codeMirror.setValue('');
        },

        render: function () {
            $(this.el).html(this.template(this.data));
            var tfsv = new TimeframeSidebarView(this.timeSliderOptions());
            tfsv.render();

            this
                .initKeywordsSelect()
                .initLinksSelect()
                .initCountrySelect()
                .initRegionSelect()
                .initGenderSelect()
                .initAgeSelect()
                .initCSDLEditor()
                .finalizeView();
        }
    });

    return FiltersOptionsView;
});
