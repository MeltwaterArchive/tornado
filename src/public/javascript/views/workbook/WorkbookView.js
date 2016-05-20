define([
	'underscore',
	'backbone',
	'router',
	'modallica',
	'mustache',
	'bootstrap/ToolTip',
	'collections/workbook',
	'controllers/worksheet',
	'models/EventTrackerModel',
	'models/WorksheetModel',
	'views/worksheet/CreateWorksheetView',
	'text!templates/workbook/workbook.html'
], function (
	_, 
	Backbone,
	Router,
	Modallica,
	Mustache,
	Tooltip,
	OldWorkbookCollection,
	OldWorksheetController,
	EventTrackerModel,
	WorksheetModel,
	CreateWorksheetView,
	WorkbookViewTemplate
) {

	var WorkbookView = Backbone.View.extend({

		el: '.page header',

		template: _.template(WorkbookViewTemplate),

		events: {
			'scroll': 'scrollWorksheets',
			'click .scroll-worksheet .left': 'scrollLeft',
			'click .scroll-worksheet .right': 'scrollRight',
			'click a': 'navigate',
			'click .actions .toggle': 'toggleActions',
			'click .add-worksheet': 'createWorksheet'
		},

		scroll: {
			// we have a 1px offset to hide the left border on the first item
			position: 0,
			item: 0
		},

		// have we already scroll to the correct tab
		scrolled: false,

		initialize: function () {
			// listen to the worksheet collection for changes to the worksheet
			if (this.model && this.model.get('worksheets')) {
				this.model.get('worksheets').on('change', this.render.bind(this));
				this.model.get('worksheets').on('destroy', this.render.bind(this));
			}

			/**
			 * This doesn't make any sense but i don't have the time to rewrite
			 * it. We need to move away from using the modallica templating
			 * system if we are just overriding it locally.
			 */
			$(document).on(
				'worksheet-edit-modal:ready.modallica', 
				this.renderModalEditTemplate.bind(this)
			);
			$(document).on(
				'worksheet-options-modal:ready.modallica', 
				this.renderModalOptionsTemplate.bind(this)
			);
			$(window).resize(this.scrollToSelected.bind(this));

			// edit the worksheet modal save button
			$('body').on(
				'click.listworksheet', 
				'.modallica--worksheet-edit-modal [data-modallica-action="submit"]', 
				this.editWorksheet.bind(this)
			);
			// delete the worksheet modal confirm button
			$('body').on(
				'click.listworksheet', 
				'.modallica--worksheet-edit-modal [data-modallica-action="confirm"]', 
				this.deleteWorksheet.bind(this)
			);
			// save the display options click
			$('body').on(
				'click.listworksheet', 
				'.modallica--worksheet-options-modal [data-modallica-action="submit"]', 
				this.applyDisplayOptions.bind(this)
			);

			// handle this here because the worksheetView doesn't know about the
			// collection
			$('body').on(
				'click.no-worksheet__action',
				'button.no-worksheet__action',
				this.createWorksheet.bind(this)
			);
		},

		/**
		 * Render the Workbook View
		 */
		render: function () {
			
			var content = this.template(this.model.attributes);
			this.$el.html(content);
			// when we click on the context menu
			this.$el.find('.actions li').on('click', this.actionClick.bind(this));
			// if we have the tooltip, remove it
			if (this.tooltip) {
				this.toggleActions();
			}

			setTimeout(this.scrollToSelected.bind(this), 500);

			return this.el;
		},

		/**
		 * Navigate
		 *
		 * When we click on the worksheet we want to navigate to the correct one
		 * 
		 * @param  {Event} e Click event
		 */
		navigate: function (e) {
			var element = $(e.target),
				href = element.attr('href');

			// stop this event from doing anything
			e.preventDefault();

			// remove the selected item
			this.model.get('worksheets').findWhere({
				'selected': true
			}).set({'selected': false}, {'silent': true});
			this.model.get('worksheets').findWhere({
				'id': element.attr('data-id')
			}).set({'selected': true}, {'silent': true});

			element.parent().addClass('loading');

			// update the router
			Router.navigateTo(href);
		},

		/**
		 * Toggle Actions
		 *
		 * Show/hide the actions context menu for the worksheets
		 *
		 * @todo this should be done inside the worksheet view
		 * @param  {Event} e Click Event
		 */
		toggleActions: function (e) {

			var element = e ? $(e.target) : false;	
			// if we have the tooltip, remove it
			if (this.tooltip) {
				// put back the element
				$(this.tooltip.tooltip).find('ul').insertAfter(this.$el.find('.actions .toggle'));
				//this.$el.find('.actions .toggle').insertAfter($(this.tooltip.tooltip).find('ul'));
				this.tooltip.remove();
				delete this.tooltip;
				return;
			}
			// stop this event from bubbling
			e.stopPropagation();
	
			// hide the dropdown when the user clicks on anything but the menu
			var click = function (e) {
				var element = $(e.target);
				if (this.tooltip && !$(this.tooltip.tooltip).find(element).length) {
					// hide the tooltip
					this.toggleActions(e);
				} else if (this.tooltip) {
					// otherwise wait for another click
					$(document).one('click', click);
				}
			}.bind(this);

			// create a tooltip
			this.tooltip = new Tooltip(element.next()[0], element.parents('li')[0], {
				'position': 'bottom',
				'classNames': ['worksheet-context']
			});
			// add the click event to hide the tooltip
			$(document).one('click', click);
		},

		/**
		 * Action Click
		 *
		 * When we click on an action route to the correct place
		 *
		 * @todo this should be in invoking methods in the worksheet view
		 * @param  {Event} e Click event
		 */
		actionClick: function (e) {
			var element = $(e.target),
				action = element.attr('data-worksheet-action');
			// stop from propogating up to the modillica code
			e.stopPropagation();

			switch (action) {
				case 'options':
				case 'edit':
					Modallica.show(element);
					break;
				case 'export':
					this.exportWorksheet();
					break;
				case 'duplicate':
					this.duplicateWorksheet();
					break;
			}
		},

		/**
		 * Create Worksheet
		 *
		 * This will open the create worksheet modal
		 */
		createWorksheet: function () {

			var worksheet = this.model.get('worksheets').add({
				project_id: this.model.get('project_id'),
				workbook_id: this.model.get('id')
			});

			var cw = new CreateWorksheetView(worksheet, this.model);
			cw.render();
		},

		/**
		 * Scroll the worksheets
		 *
		 * Takes an optional animate parameter which will scroll with animation
		 * to the next worksheet. This defaults to `false`.
		 * 
		 * @param  {boolean} animate Animate to the next worksheet
		 */
		scrollWorksheets: function (animate) {
			var ul = this.$el.find('ul');
			// are we animating?
			if (animate) {
				ul.animate({'left': -this.scroll.position + 'px'}, 50);
				return;
			}
			// nope just move
			ul.css({'left': -this.scroll.position + 'px'});
		},

		/**
		 * Scroll Left
		 *
		 * Scroll all the worksheets to the left
		 */
		scrollLeft: function (e) {
			e.preventDefault();
			this.calculateScroll(this.scroll.item - 1);
		},

		/**
		 * Scroll Right
		 *
		 * @see scrollLeft
		 * @return {[type]} [description]
		 */
		scrollRight: function (e) {
			e.preventDefault();
			this.calculateScroll(this.scroll.item + 1);
		},

		calculateScroll: function (index) {

			var wrapperWidth = this.$el.find('.wrapper').width(),
				worksheets = this.$el.find('li.worksheet'),
				leftButton = this.$el.find('.left'),
				rightButton = this.$el.find('.right'),
				totalWidth = 0,
				tabOffset = 0,
				direction = 1,
				firstVisible, 
				lastVisible;

			if (this.scroll.item > index) {
				direction = -1;
			}

			if ((direction > 0 && rightButton.hasClass('disabled')) ||
				(direction < 0 && leftButton.hasClass('disabled'))) {
				return;
			}

			// find the total offset
			for (var i = 0; i < worksheets.length; i++) {
				var width = $(worksheets[i]).outerWidth();
				totalWidth += width;

				// is this one the first visible?
				if (this.scroll.position - totalWidth < 0 && firstVisible === undefined) {
					firstVisible = i;
				}
				// find the last one visible
				if (totalWidth - this.scroll.position >= 0 &&
					totalWidth - this.scroll.position <= wrapperWidth) {
					lastVisible = i;
				}
			}

			if (direction > 0) {
				// going right find the tab offset of the first one
				for (var i = 0; i <= firstVisible; i++) {
					tabOffset += $(worksheets[i]).outerWidth();
				}
			} else {
				for (var i = 0; i < lastVisible; i++) {
					tabOffset += $(worksheets[i]).outerWidth();
				}
				tabOffset -= wrapperWidth;
			}

			// make sure the index doesn't go out of scope
			index = index >= worksheets.length-1 ? worksheets.length-1 : index;
			index = index < 0 ? 0 : index;

			// set the current item we are scrolling to the the index
			this.scroll.item = index;
			// set the offset
			this.scroll.position = tabOffset;

			// disable any buttons if we are at the end (or enable otherwise)
			leftButton.removeClass('disabled');
			rightButton.removeClass('disabled');

			// set the offset
			this.disableButtons(tabOffset, totalWidth, wrapperWidth);

			this.scrollWorksheets(true);
		},

		/**
		 * Scroll To Selected
		 *
		 * This will scroll to the currently selected tab. If the tabs location
		 * is wider than the visible scroll bar it will scroll until it's on the
		 * left. Otherwise it won't scroll and will select it in place.
		 */
		scrollToSelected: function (e) {

			var selected = this.model.get('worksheets').findWhere({'selected': true}),
				index = this.model.get('worksheets').indexOf(selected),
				wrapperWidth = this.$el.find('.wrapper').width(),
				worksheets = this.$el.find('li.worksheet'),
				leftButton = this.$el.find('.left'),
				rightButton = this.$el.find('.right'),
				tabOffset = 1,
				totalWidth = 0;

			leftButton.removeClass('disabled');
			rightButton.removeClass('disabled');

			// find the total offset we are away
			for (var i = 0; i <= worksheets.length; i++) {
				var width = $(worksheets[i]).outerWidth();
				totalWidth += width;
				if (i <= index) {
					tabOffset += width;
				}
			}
			// set the current item we are scrolling to the the index
			this.scroll.item = index;

			// if the tab is visible
			if (tabOffset > this.scroll.position && tabOffset < this.scroll.position + wrapperWidth && this.scroll.item !== this.model.get('worksheets').length-1) {
				// its already visible so don't move anything
				return;
			}
			// set the offset
			this.scroll.position = tabOffset;
			// disable the buttons
			this.disableButtons(tabOffset, totalWidth, wrapperWidth);
			// scroll the worksheet
			this.scrollWorksheets(e ? false : true);
		},

		disableButtons: function (tabOffset, totalWidth, wrapperWidth) {
			// set the offset
			var leftButton = this.$el.find('.left'),
				rightButton = this.$el.find('.right');
			
			// if we go too far left
			if (this.scroll.position <= 1) {
				leftButton.addClass('disabled');
				this.scroll.position = 1;
			}
			// if we go too far right
			if (this.scroll.position >= totalWidth - wrapperWidth) {
				rightButton.addClass('disabled');
				this.scroll.position = totalWidth - wrapperWidth;
			}
			// if we are too small we don't need to scroll
			if (totalWidth < wrapperWidth) {
				leftButton.addClass('disabled');
				rightButton.addClass('disabled');
				this.scroll.position = 1;
			}
		},

		/**
		 * Remove
		 *
		 * We want to override the standard Backbone remove because we want to
		 * keep our element so we can reattach new workbook views to it
		 */
		remove: function () {
			this.undelegateEvents();
			this.$el.innerHTML = '';
		},

		/**
		 * Everthing below needs to be moved into the Worksheet view or be 
		 * actions of the worksheet model
		 *
		 * ---------------------------------------------------------------------
		 */

		/**
		 * Render Edit Template
		 *
		 * This will upgrade the modal created by modillica with the extra
		 * information we require to render it
		 *
		 * @todo This shouldn't be here
		 *
		 * P.S. I hate these selectors, they are massive!
		 */
		renderModalEditTemplate: function() {
			var template = $('[data-tornado-template="worksheet-edit-modal-name-input"]').html();
			template = Mustache.render(
				template, 
				this.model.get('worksheets').findWhere({
					'selected': true
				}).attributes
			);
			$('.modallica--worksheet-edit-modal [data-form-field="name"]').html(template);
		},

		/**
		 * Render the Modal Options Template
		 *
		 * @todo This shouldn't be here
		 */
		renderModalOptionsTemplate: function () {
			var $el = $('.modallica--worksheet-options-modal'),
				worksheet = OldWorkbookCollection.getWorksheetById(this.model.get('worksheets').findWhere({
					'selected':true
				}).get('id')),
				worksheetOptions = worksheet.display_options;

			$el.find('select[name="sort-dimensions"]').selectize();

			if (worksheet.chart_type === 'timeseries') {
				$el.find('[data-form="sort"]').hide();
			}

			if (!worksheet.secondary_recording_id && !worksheet.baseline_dataset_id) {
				$el.find('[data-form="outliers"]').hide();
			}

			//if ()

			$el.find('[name="outliers"]').attr('checked', worksheetOptions.outliers);
			$el.find('select[name="sort-dimensions"]')[0].selectize.setValue(worksheetOptions.sort);
		},

		/**
		 * Apply the display options to the worksheet
		 *
		 * @todo This shouldn't be here
		 */
		applyDisplayOptions: function() {
			var $el = $('.modallica--worksheet-options-modal'),
				sortDimensions = $el.find('[name="sort-dimensions"]').val(),
				displayOutliers = ($el.find('[name="outliers"]:checked').length) ? true : false,
				worksheet = this.model.get('worksheets').findWhere({'selected':true});

			OldWorksheetController.view.dimensionSortBy = sortDimensions[0];
			OldWorksheetController.view.dimensionSortOrder = sortDimensions[1];

			worksheet.updateOptions({
				'sort': sortDimensions,
				'outliers': displayOutliers
			}, function () {
				// update the worksheet
				OldWorkbookCollection.updateWorksheet(worksheet.attributes);
				// rerender
				OldWorksheetController.view.setOutliers(displayOutliers);
				OldWorksheetController.view.renderCharts();
				Modallica.hide();
			}.bind(this));
		},

		/**
		 * Edits a worksheet (rename)
		 *
		 * @todo This shouldn't be here
		 *
		 * @return {Object} View instance
		 */
		editWorksheet: function() {
			var worksheet = this.model.get('worksheets').findWhere({'selected': true}),
				name = $('#edit-worksheet-name').val();

			worksheet.save({
				'name': name
			}, {
				patch: true,
				success: function (response) {
					Modallica.hide();
				},
				error: function (error) {
					throw new Error('! [Worksheet view ~ action: EDIT] ' + error.status + ': ' + error.statusText);
				}
			});
		},

		/**
		 * Deletes a worksheet
		 *
		 * @todo This shouldn't be here
		 *
		 * @return {Object} View instance
		 */
		deleteWorksheet: function() {

			var worksheet = this.model.get('worksheets').findWhere({
					'selected':true
				}),
				index = this.model.get('worksheets').indexOf(worksheet);

			// remove the worksheet
			worksheet.destroy({
				success: function (response) {

					var next = index === 0 ? 
						this.model.get('worksheets').at(index+1) : 
						this.model.get('worksheets').at(index-1);

					// remove from the old collection
					OldWorkbookCollection.removeWorksheetFromWorkbook(this.model.attributes, worksheet.attributes);
					// hide the popup
					Modallica.hide();

					if (!next) {
						Router.navigateTo(
							'/projects/' + 
							this.model.get('project_id') + 
							'/create-worksheet/' + 
							this.model.get('id')
						);
						return;
					}

					Router.navigateTo(
						'/projects/' + 
						this.model.get('project_id') + 
						'/worksheet/' + 
						next.get('id')
					);

				}.bind(this),
				error: function (error) {
					throw new Error('! [Worksheet view ~ action: DELETE] ' + error.status + ': ' + error.statusText);
				}
			});
		},

		/**
		 * Exports a worksheet
		 *
		 * @todo This shouldn't be here
		 */
		exportWorksheet: function() {
			var worksheet = this.model.get('worksheets').findWhere({'selected': true});
			EventTrackerModel.record('Export Worksheet', {
				'project_id': this.model.get('project_id'),
				'worksheet_id': worksheet.get('id')
			});
			window.location = '/api/project/' + this.model.get('project_id') + '/worksheet/' + worksheet.get('id') + '/export';
			return this;
		},

		/**
		 * Duplicate Worksheet
		 *
		 * This will first duplicate the worksheet and then run an analyze
		 * query on the date
		 */
		duplicateWorksheet: function () {

			var worksheet = this.model.get('worksheets').findWhere({'selected': true});

			// create the worksheet
			$.post('/api/project/' + worksheet.get('project_id') + '/worksheet/' + worksheet.get('id') + '/duplicate', {
			}).done(function (response) {
				// add to the new implementation
				var newWorksheet = this.model.get('worksheets').add(response.data.worksheet);

				// add to the old collection, the routers are still using the old
				// collections so if we want to go to that page via the router
				// we will to make sure it's added
				var workbook = OldWorkbookCollection.getModel(this.model.get('id'));
				OldWorkbookCollection.addWorksheetToWorkbook(workbook, response.data.worksheet);
				OldWorkbookCollection.updateWorksheet(response.data.worksheet);

				Router.navigateTo('/projects/' + this.model.get('project_id') + '/worksheet/' + newWorksheet.get('id'));
			}.bind(this));
        }
	});

	return WorkbookView;
});