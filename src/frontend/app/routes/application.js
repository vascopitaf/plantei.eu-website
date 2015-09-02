import Ember from 'ember';
import ApplicationRouteMixin from 'simple-auth/mixins/application-route-mixin';

export default Ember.Route.extend(ApplicationRouteMixin, {
  actions: {
    chooselang: function(lang) {
      var i18n = this.container.lookup('service:i18n');
      i18n.set('locale', lang);
      this.refresh();
      return false;
    },
    invalidateSession: function(){ 
	    this.get('session').set('isAuthenticated', false);
	    this.transitionTo('login');
    }
  },

  model: function() {
    var i18n = this.container.lookup('service:i18n');
    var langs = i18n.get('locales');
    var dflt = i18n.get('locale');
    //console.log(langs, dflt);
    console.log(this.get('session'));
    var out = [];
    for (var i = 0; i < langs.length; i++){
	    //console.log(langs[i], dflt);
	    if (langs[i] === dflt) {
		    out.push({'lang': langs[i], 'active': true});
	    } else {
		   out.push({'lang': langs[i], 'active': false});
	    }
    }
    return out;
  },

});
