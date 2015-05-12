/**
 * Beats Suggestioner
 */
(function ($) {
  var __ = {
    defaults: {
      autocomplete: {
        displayer: function (item) {
          return item ? item : 'N/a';
        },
        optioner: function (item) {
          return item ? item : 'N/a';
        },
        valuer: function (store, item) {
          return item;
        }
      },
      engine: {
        datumTokenizer: Bloodhound.tokenizers.whitespace,
        queryTokenizer: Bloodhound.tokenizers.whitespace
      }
    },
    lookuper: function (engine) {
      return function (term) {
        var dfd = $.Deferred()
          , dfdS = $.Deferred()
          , dfdA = $.Deferred()
          ;
        engine.search(term, function (items) {
          dfdS.resolve(items);
        }, function (items) {
          dfdA.resolve(items)
        });
        $.when(dfdS, dfdA).then(function (itemsS, itemsA) {
          dfd.resolve(false, $.merge(itemsS, itemsA))
        });
        return dfd;
      }
    },
    limitResult: function (items, limit) {
      if (0 < limit && limit < items.length) {
        return items.slice(0, limit);
      }
      return items;
    },
    //parseLimit: function (options) {
    //  return options.hasOwnProperty('_limit') ? options._limit : undefined;
    //},
    //parseWildcard: function (options) {
    //  return options.hasOwnProperty('_wildcard') ? options._wildcard : Beats.Suggestioner.wildcard;
    //},
    remoteParser: function () {
      var opts = {
        limit: this.hasOwnProperty('_limit') ? this._limit : undefined,
        wildcard: this.hasOwnProperty('_wildcard') ? this._wildcard : Beats.Suggestioner.wildcard
      };
      delete this._limit;
      delete this._wildcard;
      return opts;
    },
    normalizers: {
      geocode: function (service, origin, data, name, lat, lng) {
        return {
          service: service,
          origin: origin,
          name: name,
          point: {
            lat: lat,
            lng: lng
          },
          data: data
        }
      }
    },
    autocompletes: {
      geocode: {
        displayer: function (item) {
          return item ? item.name : '';
        },
        optioner: function (item) {
          return item ? item.name : '';
        },
        valuer: function (store, item) {
          try {
            return store ? JSON.stringify(item) : JSON.parse(item);
          } catch (ex) {
            return store ? '' : null;
          }
        }
      }
    },
    engines: {}
  };

  // TODO@ion: Enable external services load
  var _services = {
    google: {
      normalize: function (item, origin, service) {
        return __.normalizers.geocode(service, origin, item,
          item.formatted_address,
          item.geometry.location.lat, item.geometry.location.lng
        );
      },
      remote: function (options, service) {
        var origin = 'maps.googleapis.com/maps/api/geocode';
        var remote = __.remoteParser.call(options);
        return {
          wildcard: remote.wildcard,
          url: 'https://' + origin + '/json?' + $.param(
            $.extend(options, {
              components: $.isPlainObject(options.components) ? $.map(options.components, function (val, key) {
                return key + ':' + val;
              }).join('|') : options.components,
              address: remote.wildcard
            })
          ),
          transform: function (response) {
            if (response.status && response.status == 'OK') {
              return __.limitResult(response.results, remote.limit).map(function (item) {
                return _services[service].normalize(item, origin, service);
              });
            }
            return [];
          }
        }
      },
      engine: {},
      autocomplete: __.autocompletes.geocode
    },
    geonames: {
      normalize: function (item, origin, service) {
        return __.normalizers.geocode(service, origin, item,
          item.toponymName + (item.adminName1 ? ", " + item.adminName1 : "") + ", " + item.countryName,
          item.lat, item.lng
        );
      },
      remote: function (options, service) {
        var origin = 'api.geonames.org/search';
        var remote = __.remoteParser.call(options);
        return {
          wildcard: remote.wildcard,
          url: 'http://' + origin + 'JSON?' + $.param(
            $.extend(options, {
              name_startsWith: remote.wildcard
            })
          ),
          transform: function (response) {
            if ($.isPlainObject(response) && response.hasOwnProperty('geonames')) {
              return __.limitResult(response.geonames, remote.limit).map(function (item) {
                return _services[service].normalize(item, origin, service);
              });
            }
            return [];
          }
        }
      },
      engine: {},
      autocomplete: __.autocompletes.geocode
    }
  };

  Beats.Suggestioner = can.Construct.extend({
    services: Object.keys(_services),

    wildcard: '----',

    _engineOptions: function (name, options) {
      var service = _services[name];
      return $.extend(true, {
        remote: service.remote(options.remote, name)
      }, __.defaults.engine, service.engine, options.engine);
    },

    registerEngine: function (name, options) {
      if (__.engines.hasOwnProperty(name)) {
        return __.engines[name];
      }
      options = options || {};
      var service;
      if (options.hasOwnProperty('service')) {
        service = options.service;
        if (_services.hasOwnProperty(service)) {
          options = this._engineOptions(service, options);
        } else {
          throw Beats.Error(this, "Unsupported service: " + service)
        }
      }
      options.initialize = false;
      var engine = __.engines[name] = new Bloodhound(options);
      engine.service = service;
      return engine;
    },

    getEngine: function (name) {
      if (__.engines.hasOwnProperty(name)) {
        return __.engines[name];
      }
      throw Beats.Error(this, "Suggestion engine not found: " + name);
    },

    getAutocompleteOptions: function (name, options) {
      options = options || {};
      var engine = options.hasOwnProperty('engine') ? this.getEngine(name) : this.registerEngine(name, options.engine);
      var service = engine.service;
      engine.initialize();
      return $.extend({
        lookuper: __.lookuper(engine)
      }, __.defaults.autocomplete, _services[service].autocomplete, options.autocomplete);
    }

  }, {
    init: function () {
      throw Beats.Error(this, "This is a singleton. Do NOT instantiate!")
    }
  });

  return Beats.Suggestioner

})(jQuery);