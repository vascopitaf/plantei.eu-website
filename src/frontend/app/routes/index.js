import Ember from 'ember';
import AuthenticatedRouteMixin from 'simple-auth/mixins/authenticated-route-mixin';

export default Ember.Route.extend(AuthenticatedRouteMixin,{
  model: function() {
    return this.store.find('seed');
  },
  events: {
	  error: function (reason, transition) {
		  if (reason.status === 401) {
			  alert('you must login!');
			  this.transitionTo('login');
		  } else {
			  alert('Something went wrong...');
		  }

	  },
  }

});
