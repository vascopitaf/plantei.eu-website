import Ember from 'ember';

export default Ember.Route.extend({
	setupController: function(controller, context) {
		controller.reset();
	}
});
