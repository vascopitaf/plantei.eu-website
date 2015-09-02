import Ember from 'ember';

export default Ember.Controller.extend({
  /*actions: {
    chooselang: function(lang) {
      var i18n = this.container.lookup('service:i18n');
      i18n.set('locale', lang);
      //this.container.route.refresh();
      this.container.lookup('view:toplevel').rerender();
      this.container.lookup('view:toplevel').refresh();
      //this.transitionTo('index');
      console.log(this.container.lookup('view:toplevel'));
      //console.log(i18n.get('locales'));
      // MEANS NOTHING Ember.lookup('service:i18n').set('locale', lang);
      return false;
    },
  },*/
  //model: function() { 
  //    var langs = [{'lang': 'pt', 'active': false}, {'lang':'en', 'active':true}];
  //    console.log(langs);
  //    return langs;
  //},

});
