(function() {
  this.WPM || (this.WPM = []);

  this.WPMarketing || (this.WPMarketing = []);

  Swag.registerHelpers(Handlebars);

  if (typeof jQuery === "undefined") {
    console.log("WPMarketing depends on jQuery and it is not defined.");
  } else {
    (function($) {
      WPMarketing.debug || (WPMarketing.debug = false);
      WPMarketing.mode || (WPMarketing.mode = "get");
      WPMarketing.log = function(data) {
        if (WPMarketing.debug) {
          return console.log(data);
        }
      };
      WPMarketing.detectPushes = function() {
        return WPM.push = function(args) {
          var a;
          a = Array.prototype.push.call(this, args);
          WPMarketing.log("WPMarketing has received a(n) " + args[0] + " event.");
          setTimeout(WPMarketing.parseEvents, 20);
          return a;
        };
      };
      WPMarketing.addWidget = function(data) {
        var form_actions, html, structure, template;
        structure = "<div class=\"wpmarketing_widget {{#unless data.overlay.clickable}}wpmarketing_unclickable{{/unless}} {{#unless data.overlay.escapeable}}wpmarketing_unescapeable{{/unless}} {{#if mobile.hide}}wpmarketing_mobile_hide{{/if}} {{#if tablet.hide}}wpmarketing_tablet_hide{{/if}} {{#if desktop.hide}}wpmarketing_desktop_hide{{/if}}\" data-style=\"{{style}}\" data-action=\"{{action}}\" data-position=\"{{position}}\" data-theme=\"{{theme}}\" data-sticky=\"{{sticky}}\">\n	<div class=\"wpmarketing_widget_container\">\n		<h3>{{ title }}</h3>\n		<h4>{{ description }}</h4>\n		<div class=\"wpmarketing_content\">";
        form_actions = ["download", "subscription", "callback", "appointment", "contact"];
        if (jQuery.inArray(data.action, form_actions) !== -1) {
          structure += "<form action=\"{{data.url}}\" method=\"{{data.method}}\">\n	{{#each data.fields}}\n		<div class=\"wpmarketing_field\">\n			<label for=\"{{key}}\">{{name}}</label>\n			<input type=\"{{type}}\" name=\"{{key}}\" placeholder=\"{{placeholder}}\">\n		</div>\n	{{/each}}\n\n	<div class=\"wpmarketing_action_field\">\n		<button type=\"submit\">\n			{{{ data.button }}}\n		</button>\n	</div>\n</form>";
        }
        structure += "	</div>\n</div>";
        template = Handlebars.compile(structure);
        html = template(data);
        if (data.style === "inline" && typeof data.container !== "undefined") {
          return $(data.container).html(html);
        } else {
          return $(html).appendTo("body");
        }
      };
      WPMarketing.parseEvents = function() {
        var event, _i, _len;
        _i = 0;
        _len = WPM.length;
        while (_i < _len) {
          event = WPM[_i];
          event = WPM.shift();
          if (event[0] === "track") {
            WPMarketing.track(event[1], event[2]);
            WPMarketing.log("WPMarketing has tracked \"" + event[1] + "\".");
          } else if (event[0] === "widget") {
            WPMarketing.addWidget(event[1]);
            WPMarketing.log("WPMarketing has added a \"" + event[1].style + "\" widget.");
          } else if (event[0] === "debug") {
            WPMarketing.log("WPMarketing debug mode is set to " + event[1] + ".");
            WPMarketing.debug = event[1];
          }
          _i++;
        }
        return WPMarketing.detectPushes();
      };
      WPMarketing.parseEvents();
      $(document).on("click", ".wpmarketing_widget[data-style='dialog']:not(.wpmarketing_unclickable)", function(e) {
        if ($(e.target).hasClass("wpmarketing_widget")) {
          return $(this).hide();
        }
      });
      return $(document).keyup(function(e) {
        var code;
        code = (e.keyCode ? e.keyCode : e.which);
        if (code === 27) {
          return $(".wpmarketing_widget[data-style='dialog']:not(.wpmarketing_unescapeable)").hide();
        }
      });
    })(jQuery);
  }

}).call(this);
