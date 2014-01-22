/**
 * Beats Entity
 */
(function ($) {

  Beats.Entity = can.Construct.extend({
    id: 'id',
    const: {
    }
  }, {
    _: null,
    data: null,

    init: function (data) {
      var self = this
      self._super.apply(self, arguments)
      self.data = data
      self._ = {}
    },

    getID: function () {
      return this.data[this.construct.id]
    }

  })

  return Beats.Entity

})(jQuery);
