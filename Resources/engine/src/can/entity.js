/**
 * Beats Entity
 */
(function ($) {

  Beats.Entity = can.Construct.extend({
    id: 'id',
    constant: {
    }
  }, {
    /**
     * Raw data
     * @var object
     */
    $: null,
    
    /**
     * Lazy loaded
     * @var object
     */
    _: null,

    init: function (data) {
      var self = this
      self._super.apply(self, arguments)
      self.$ = data
      self._ = {}

      $.each(data, function (key, val) {
        if (self[key] === undefined) {
          self[key] = self.proxy(function () {
            return this.$[key]
          })
        }
      })
    },

    getID: function () {
      return this.$[this.constructor.id]
    }

  })

  return Beats.Entity

})(jQuery);
