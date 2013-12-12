/**
 * Beats Browser tool
 */
(function ($) {

  can.Construct.extend('Beats.Browser', {

    isIOS: function () {
      var ua = navigator.userAgent
      return ( ua.match(/(iPad|iPhone|iPod)/g) ? true : false );
    },
    isAndroid: function () {
      var ua = navigator.userAgent
      return ua.toLowerCase().indexOf("android") > -1;
    },
    isChrome: function () {
      var ua = navigator.userAgent
      return ua.toLowerCase().indexOf("chrome") > -1;
    },
    isSafari: function () {
      var ua = navigator.userAgent.toLowerCase()
      return (ua.indexOf('safari') > -1) && (ua.indexOf('chrome') === -1)
    },
    isMobile: function () {
      return Beats.Browser.isIOS() || Beats.Browser.isAndroid()
    },
    exifImageRotation: function (supports) {
      if (supports) {
        return Beats.Browser.isIOS()
      } else {
        return Beats.Browser.isAndroid() || Beats.Browser.isChrome() || Beats.Browser.isSafari()
      }
    }
  }, {
    init: function () {
      throw Beats.Error(this, "This is a singleton. Do NOT instantiate!")
    }
  })

  return Beats.Browser

})(jQuery);
