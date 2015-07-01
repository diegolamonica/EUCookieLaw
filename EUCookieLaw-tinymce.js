(function() {
	tinymce.PluginManager.add('eucookielaw', function( editor, url ) {
		editor.addButton( 'eucookielaw', {
			title: 'EUCookieLaw',
			icon: 'eucookielaw',
			type: 'menubutton',
			menu: [
				{
					text: 'Add reconsider button',
					value: 'value 1',
					onclick: function() {
						editor.windowManager.open( {
							title: 'Add reconsider button',
							body: [{
								type: 'textbox',
								name: 'label',
								label: 'Label'
							}],
							onsubmit: function( e ) {
								editor.insertContent( '[EUCookieLawReconsider label="' + e.data.label + '"]');
							}
						});
					}
				},
				{
					text: 'Block section',
					value: 'value 2',
					onclick: function( e ) {
						console.log(e);
						editor.insertContent('[EUCookieLawBlock]' + tinyMCE.activeEditor.selection.getContent() + '[/EUCookieLawBlock]');
					}
				}
			]

		});

		var replaceBlockShortcodes = function(content){
				return content.replace(
					/\[EUCookieLawBlock]([\s\S]*?)\[\/EUCookieLawBlock]/g,
					'<div class="eucookielaw-blocked-contents">$1</div>');
			},
			restoreBlockShortcodes = function(content){
				return content.replace( /<div class="eucookielaw-blocked-contents">(.*?)<\/div>/g, '[EUCookieLawBlock]$1[/EUCookieLawBlock]');

			},
			replaceButtonShortcode = function(content){
				return content.replace(
					/\[EUCookieLawReconsider( label="(.*?)")?]/g,
					'<a class="eucookielaw-button-item">$2</a>');
			},
			restoreButtonShortcode = function(content){
				return content.replace( /<a class="eucookielaw-button-item">(.*?)<\/a>/g, '[EUCookieLawReconsider label="$1"]');

			};

		editor.on( 'BeforeSetContent', function( event ) {
			console.log("here");
			event.content = replaceBlockShortcodes(event.content);
			event.content = replaceButtonShortcode(event.content);

		});

		editor.on( 'PostProcess', function( event ) {
			console.log("there");
			if (event.get) {
				console.log("then there");
				event.content = restoreBlockShortcodes(event.content);
				event.content = restoreButtonShortcode(event.content);
			}
		});

	});


})();