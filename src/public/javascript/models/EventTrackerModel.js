define([], function () {
	var EventTrackerModel = {};

	EventTrackerModel.addClickEvent = function (selector, name) {
		if (typeof _kmq !== 'undefined') {
			_kmq.push(['trackClick', selector, name]);
		}
	};

	EventTrackerModel.addSubmitEvent = function (selector, name) {
		if (typeof _kmq !== 'undefined') {
			_kmq.push(['trackSubmit', selector, name]);
		}
	};

	EventTrackerModel.record = function (name, meta) {
		if (typeof _kmq !== 'undefined') {
			_kmq.push(['record', name, meta]);
		}
	};

	return EventTrackerModel;
});