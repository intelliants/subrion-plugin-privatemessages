<div id="message-compose-box" class="quick-email-box">
	<h2>{lang key='send_email'}</h2>
	<form method="post" action="{$smarty.const.IA_URL}profile/messages/" id="email-form">
		<div>
			<label for="m-to">{lang key='to'}:</label>
			<input type="text" class="span2" id="m-to" value="{$addressee.fullname|default:$addresss.username}" readonly>
			<div class="clear"></div>
		</div>
		<div>
			<label for="m-subject">{lang key='subject'}:</label>
			<input type="text" class="text text-field" id="m-subject" value="">
			<div class="clear"></div>
		</div>
		<div>
			<label for="m-text">{lang key='message'}</label>
			<div class="clear"></div>
			<textarea id="m-text" class=""></textarea>
		</div>
		<input type="submit" class="btn btn-primary" style="height: 28px; width: 100px;" value="{lang key='send'}">
		<span style="color: red; display: none; margin-left: 16px;" class="message"></span>
	</form>
</div>

{ia_add_js}

$('#email-form').submit(function(e)
{
	if (!$('#m-subject').val())
	{
		if (!confirm({lang key='subject_is_empty'}))
		{
			return false;
		}
	}
	e.preventDefault();
	$.ajax({
		type: $(this).attr('method'),
		url: $(this).attr('action'),
		data: {
			action: 'email',
			addressee: {$addressee.id},
			body: $('#m-text').val(),
			subject: $('#m-subject').val(),
		},
		success: function(data) {
			if (data.type == 'success')
			{
				return true;
			}
			else
			{
				$('.message', '#email-form')
					.text(data.message)
					.fadeIn('slow', function()
					{
						setTimeout(function()
						{
							$('.message', '#email-form').text('').fadeOut('slow');
						}, 6000);
					});
			}
		},
		dataType: 'json'
	});
});

{/ia_add_js}