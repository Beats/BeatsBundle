/**
 * Beats Router
 */
(function ($) {

  // LATER: Load /routes.js
  var _routes = routes
  delete routes

  var _cache = {
    fsal: {}
  }

  Beats.Router = can.Construct.extend({

    _routes: {},

    _fileBase: '/file',

    fsal: function (model, id, name, preset, absolute, scope) {
      var args = [model, id, name]
        , cacheID = args.join('_')
        , cache = _cache.fsal[cacheID]
      if (!cache) {
        _cache.fsal[cacheID] = cache = {
          id: cacheID,
          args: args,
          href: this.href.apply(this, args),
          ajax: null,
          preset: preset || false,
          deferred: $.Deferred(),
          scope: scope || this,
          complete: function (success) {
            this.args.unshift(success ? this.href : this.preset)
            if (success) {
              this.deferred.resolveWith(this.scope, this.args)
            } else {
              this.deferred.rejectWith(this.scope, this.args)
            }
            return this.args
          }
        }
        cache.ajax = $.ajax({
          url: cache.href,
          type: 'HEAD',
          global: false,
          _cache: cache,
          error: function (data) {
            this._cache.complete(false);
          },
          success: function (data) {
            this._cache.complete(true);
          }
        })
      }
      return cache.deferred;
    },

    href: function (model, id, name, absolute) {
      var path = this._fileBase + '/' + model + '_' + id + '/' + name
      if (absolute) {
        var origin = window.location.origin || window.location.protocol + '//' + window.location.hostname
        return origin + path;
      }
      return path;
    },

    slugify: function (text) {
      return text
        .toLowerCase()
        .replace(/[^\w ]+/g, '')
        .replace(/ +/g, '-')

    },

    setup : function(base, fullName, staticProps, protoProps){
      var self = this
      $.each(_routes, function (idx, route) {
        self._routes[idx] = null
      })
      can.Construct.setup.apply(self, arguments)
    },

//    init: function () {
//      var self = this
//      $.each(_routes, function (idx, route) {
//        self._routes[idx] = null
//      })
//    },

    get: function (name) {
      var self = this
      if (name in self._routes) {
        if (!self._routes[name]) {
          if (!_routes[name]) {
            throw Beats.Error(self, 'Route definition not found: ' + name)
          }
          self._routes[name] = new Beats.Route(name, _routes[name])
        }
        return self._routes[name]
      }
      throw Beats.Error(self, 'Route not found: ' + name)
    },

    url: function (name, params, absolute) {
      return this.get(name).url(params, absolute)
    },

    args: function (name, params, absolute, sync) {
      return this.get(name).args(params, absolute, sync)
    },

    ajax: function (name, params, absolute, sync) {
      return this.get(name).ajax(params, absolute, sync)
    },

    call: function (name, params, scope, absolute, sync) {
      return this.get(name).call(params, scope, absolute, sync)
    }

  }, {
    init: function () {
      throw Beats.Error(this, "This is a singleton. Do NOT instantiate!")
    }
  })

  var __ = {
    empty: function (value) {
      return value === true || value === false || value === ''
    },
    encode: function (value) {
      if (value === null || value === undefined) {
        return ''
      }
      return encodeURIComponent(value).replace(/%2F/g, '/')
    },
    build: function (instance, path, url, data) {
      var self = this, optional = false
      $.each(path.tokens, function (idx, token) {
        var type = token[0], fn = __.token[type]
        if ($.isFunction(fn)) {
          fn.call(self, instance, url, data, optional, token)
        } else {
          throw Beats.Error(instance, 'Unsupported route token type: ' + type)
        }
      })
      return optional
    },
    token: {
      text: function (instance, url, data, optional, tokens) {
        url.unshift(tokens[1])
        optional = false
      },
      variable: function (instance, url, data, optional, tokens) {
        var delimiter = tokens[1], pattern = tokens[2], key = tokens[3]
          , val, def, rex = __.parseRegEx(pattern)
          , hasDefault = instance.hasDefault(key)
          , hasParam = key in data

        if (hasParam) {
          val = data[key]
        }
        if (hasDefault) {
          def = instance.getDefault(key)
        }

        if (optional) {
          if (hasParam && hasDefault && (val == def)) {
            delete data[key]
          }
        } else {
          if (hasParam) {
            delete data[key]
          } else if (hasDefault) {
            val = def
          } else {
            throw Beats.Error(instance, 'Missing parameter "' + key + '" for route: ' + instance.name)
          }

          optional = false

          if (!__.empty(val)) {
            if (rex && !rex.test(val)) {
              throw Beats.Error(instance, 'Invalid parameter "' + key + '":"' + val + '" for route: ' + instance.name)
            }
            url.unshift(__.encode(val))
            url.unshift(delimiter)
          }
        }
      }
    },

    parsePCRE: function (pcre) {
      if (pcre && pcre.length) {
        var delimiter = pcre[0]
          , i = pcre.lastIndexOf(delimiter)
          , pattern = pcre.substring(1, i)
          , modifiers = pcre.substr(i + 1)
        try {
          return new RegExp(pattern, modifiers)
        } catch (ex) {
          console.error(ex, pattern)
        }
      }
      return false
    },
    parseRegEx: function (pattern, modifiers) {
      if (pattern && pattern.length) {
        try {
          return new RegExp(pattern, modifiers)
        } catch (ex) {
          console.error(ex)
        }
      }
      return false
    },
    parseCompiled: function (opts) {
      var vars = {}, tokens = []
      if (opts) {
        vars = opts.vars || {}
        tokens = opts.tokens || []
      }
      return {
        vars: vars, tokens: tokens
      }
    },

    cleanPath: function (url, data) {
      var path = url.length ? url.join('') : '/'

      // LATER: cleanup '.' and '..' paths

      return path
    }
  }

  can.Construct.extend('Beats.Route', {


    parse: function (response, status, xhr) {
      // Parse the response created in AbstractController::ajaxMessage
      if (response.success) {
        return response.data
      }
      throw new Error(response.message || 'An error occurred. Please try again!')
    },

    resolve: function (ajax, scope) {
      var dfd = $.Deferred()
      ajax.done(function (response, status, xhr) {
        try {
          dfd.resolveWith(scope, [Beats.Route.parse.apply(scope, arguments), true])
        } catch (ex) {
          dfd.rejectWith(scope, [ex, false]);
        }
      }).fail(function (xhr, status, error) {
          dfd.rejectWith(scope, [new Error(status), false]);
        })
      return dfd
    }

  }, {
    name: null,

    _controller: null,
    _format: 'json',

    _methods: ['GET'],
    _schemes: ['http'],
    _hostname: window.location.hostname,

    _defaults: {},
    _required: {},

    _path: null,
    _host: null,

    init: function (name, options) {
      var self = this
      self.name = name

      self._methods = options.methods || self._methods
      self._schemes = options.schemes || self._schemes

      self._defaults = options.defaults || {}
      self._required = options.required || {}

      self._controller = options.controller
      self._hostname = options.hostname || self._hostname
      self._format = options.format || self._format

      self._path = __.parseCompiled(options.path)
      self._host = __.parseCompiled(options.host)
    },

    getName: function () {
      return this.name
    },

    getMethod: function () {
      return this._methods.length ? this._methods[0] : 'GET'
    },

    getPathVars: function () {
      return this._path.vars
    },

    hasRequired: function (key) {
      return key in this._required
    },

    getRequired: function (key) {
      if (!key) {
        return this._required
      } else {
        return this.hasRequired(key) ? this._required[key] : null
      }
    },

    hasDefault: function (key) {
      return key in this._defaults
    },

    getDefault: function (key) {
      if (!key) {
        return this._defaults
      } else {
        return this.hasDefault(key) ? this._defaults[key] : null
      }
    },

    args: function (params, absolute, sync) {
      var self = this, path = [], host = [], data = {}

      if (!params) {
        params = {}
      } else if ($.isFunction(params)) {
        params = params.apply(this)
      }

      if ($.type(params) != 'object') {
        throw Beats.Error(this, 'Parameters must be an object')
      }
      $.extend(data, params)

      __.build(self, self._path, path, data)

      path = __.cleanPath(path, data)

      var scheme, port
      if (self.hasRequired('_scheme')) {
        scheme = self.getRequired('_scheme').toLowerCase()
        if (scheme != window.location.protocol) {
          absolute = true
        }
      }
      if (self.hasRequired('_port')) {
        port = self.getRequired('_port').toLowerCase()
        if (port != window.location.port) {
          absolute = true
        }
      }

      if (absolute) {
        if (self._host.tokens.length) {
          __.build(self, self._host, host, data)
        } else {
          host = [self._hostname]
        }
        if (!scheme) {
          scheme = window.location.protocol.replace(/:/g, '')
        }
        if (!port) {
          port = window.location.port
        }

        host.unshift(scheme, '://')
        if (port && port.length) {
          host.push(':', port)
        }
        host.push(path)
        path = host.join('')
      }

      return {
        url: path, type: self.getMethod(), data: data, dataType: self._format, async: !sync
      }
    },

    ajax: function (params, absolute, sync) {
      var self = this
      return $.ajax(self.args(params, absolute, sync))
    },

    call: function (params, scope, absolute, sync) {
      var self = this
      return Beats.Route.resolve(self.ajax(params, absolute, sync), scope || self)
    },

    url: function (params, absolute, ignoreQueryString) {
      var args = this.args(params, absolute)
      if (args.method != 'GET' && ignoreQueryString) {
        return args.url
      }
      var query = $.param(args.data)
      if (query.length) {
        return [args.url, query].join('?')
      }
      return args.url
    }

  })

  return Beats.Router

})(jQuery)
