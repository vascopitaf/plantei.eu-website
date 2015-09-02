import Ember from 'ember';

export default Ember.Controller.extend({
	reset: function() {
		this.setProperties({
			username: "",
			password: "",
			errorMessage: ""
		});
	},
	login: function() {
		var self = this, data = this.getProperties('username', 'password');
		self.set('errorMessage', null);
		
		self.set('errorMessage', 'Error Message: ' + data.username + data.password);
		this.get('session').set('isAuthenticated', true);
		this.transitionTo('index');
	}
});
