@WPM or (@WPM = [])
@WPMarketing or (@WPMarketing = [])

Swag.registerHelpers(Handlebars);

if typeof jQuery == "undefined"
	console.log "WPMarketing depends on jQuery and it is not defined."
else
	(($) ->
		
		WPMarketing.debug ||= false
		WPMarketing.mode ||= "get"

		WPMarketing.log = (data) ->
			console.log data if WPMarketing.debug

		WPMarketing.detectPushes = ->
			WPM.push = (args) ->
				a = Array::push.call(this, args)
				WPMarketing.log "WPMarketing has received a(n) " + args[0] + " event."
				setTimeout WPMarketing.parseEvents, 20
				a

		WPMarketing.addWidget = (data) ->
			structure = """
				<div class="wpmarketing_widget {{#unless data.overlay.clickable}}wpmarketing_unclickable{{/unless}} {{#unless data.overlay.escapeable}}wpmarketing_unescapeable{{/unless}} {{#if mobile.hide}}wpmarketing_mobile_hide{{/if}} {{#if tablet.hide}}wpmarketing_tablet_hide{{/if}} {{#if desktop.hide}}wpmarketing_desktop_hide{{/if}}" data-style="{{style}}" data-action="{{action}}" data-position="{{position}}" data-theme="{{theme}}" data-sticky="{{sticky}}">
					<div class="wpmarketing_widget_container">
						<h3>{{ title }}</h3>
						<h4>{{ description }}</h4>
						<div class="wpmarketing_content">
			"""
			
			form_actions = ["download", "subscription", "callback", "appointment", "contact"]
			
			if jQuery.inArray(data.action, form_actions) != -1
				structure += """
					<form action="{{data.url}}" method="{{data.method}}">
						{{#each data.fields}}
							<div class="wpmarketing_field">
								<label for="{{key}}">{{name}}</label>
								<input type="{{type}}" name="{{key}}" placeholder="{{placeholder}}">
							</div>
						{{/each}}
					
						<div class="wpmarketing_action_field">
							<button type="submit">
								{{{ data.button }}}
							</button>
						</div>
					</form>
				"""
			
			structure += """
					</div>
				</div>
			"""
				
			template = Handlebars.compile structure
			html = template(data)
			
			if data.style == "inline" && typeof data.container != "undefined"
				$(data.container).html html
			else
				$(html).appendTo "body"
			
			# if data.style == "bar"
			# 	$("body").css "margin-top", $(".wpmarketing_widget:last").outerHeight()

		WPMarketing.parseEvents = ->
			_i = 0
			_len = WPM.length

			while _i < _len
				event = WPM[_i]
				event = WPM.shift()
				if event[0] is "track"
					WPMarketing.track event[1], event[2]
					WPMarketing.log "WPMarketing has tracked \"" + event[1] + "\"."
				else if event[0] is "widget"
					WPMarketing.addWidget event[1]
					WPMarketing.log "WPMarketing has added a \"" + event[1].style + "\" widget."
				else if event[0] is "debug"
					WPMarketing.log "WPMarketing debug mode is set to " + event[1] + "."
					WPMarketing.debug = event[1]
				_i++
			WPMarketing.detectPushes()

		WPMarketing.parseEvents()
		
		$(document).on "click", ".wpmarketing_widget[data-style='dialog']:not(.wpmarketing_unclickable)", (e) ->
			$(this).hide() if $(e.target).hasClass("wpmarketing_widget")
		
		$(document).keyup (e) ->
			code = ((if e.keyCode then e.keyCode else e.which))
			$(".wpmarketing_widget[data-style='dialog']:not(.wpmarketing_unescapeable)").hide() if code == 27
	
	) jQuery