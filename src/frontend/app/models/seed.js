import DS from 'ember-data';

export default DS.Model.extend({
  name: DS.attr('string'),
  sciName: DS.attr('string'),
  imgUrl: DS.attr('string'),
  desc: DS.attr('string')
});
