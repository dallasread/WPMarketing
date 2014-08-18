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
				<div class="wpmarketing_widget" data-style="{{style}}" data-action="{{action}}" data-position="{{position}}" data-theme="{{theme}}" data-sticky="{{sticky}}">
					<h3>{{ title }}</h3>
					<h4>{{ description }}</h4>
			"""
			
			form_actions = ["download", "subscription", "callback", "appointment", "contact"]
			
			if jQuery.inArray(data.action, form_actions) != -1
				structure += """
					<form action="{{data.url}}" method="{{data.method}}">
						{{#each data.fields}}
							<div class="wpmarketing_field">
								<label>{{name}}<br>
									<input type="{{type}}" name="{{key}}" placeholder="{{name}}">
								</label>
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
			"""
				
			template = Handlebars.compile structure
			html = template(data)
			$(html).appendTo "body"

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
		
		$(document).on "click", ".field", ->
			# alert "jhi"

		WPMarketing.parseEvents()
	
	) jQuery