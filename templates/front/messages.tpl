<div class="b-pm__messages__check clearfix">
	Select:
	<span onclick="select_all(); return true;">{lang key='all'}</span>
	<span onclick="select_none(); return true;">{lang key='none'}</span>
	<span onclick="select_read(); return true;">{lang key='read'}</span>
	<span onclick="select_unread(); return true;">{lang key='unread'}</span>
</div>

{if isset($pm_messages)}
	<form method="post" id="messages_form" action="{$smarty.const.IA_URL}profile/messages/?folder={$active_folder}" class="b-pm__messages__list">
		{preventCsrf}
		<table class="table table-condensed table-striped">
			<tr>
				<th style="width: 20px;">
					<input type="checkbox" onclick="checkAll(document.getElementById('messages_form'), this.checked); return true;" class="checkbox">
				</th>
				<th style="width: 20%;">
					{if isset($smarty.get.folder) and $smarty.get.folder == 2}{lang key="to"}{else}{lang key="from"}{/if}
				</th>
				{if ($active_folder != 1) AND ($active_folder != 2)}
					<th style="width:20%">{lang key="to"}</th>
				{/if}
				<th class="message">{lang key='subject'}</th>
				<th style="width: 15%;">{lang key='date'}</th>
			</tr>
			{if $pm_messages}
				{foreach from=$pm_messages item=value key=key}
					{if is_array($value) && count($value) > 0}
						<tr class="message{if $value.new} unread{/if}">
							<td style="width:20px">
								<input type="checkbox" name="messages[]" value="{$value.id}" class="{if $value.new}unread{else}read{/if}" class="checkbox">
							</td>
							<td style="width:20%; cursor:pointer;" onclick="window.location.href='profile/messages/?folder={$active_folder}&amp;mid={$value.id}'">
								{if $active_folder == 2}
									{ia_url type='link' item='members' data=$value.to_username text=$value.to_fullname}
									{else}
									{ia_url type='link' item='members' data=$value.from_username text=$value.from_fullname}
								{/if}
							</td>
							{if ($active_folder != 1) AND ($active_folder != 2)}
								<td style="cursor:pointer;" onclick="window.location.href='profile/messages/?folder={$active_folder}&amp;mid={$value.id}'">{$value.to_username}</td>
							{/if}
							<td class="message" style="cursor:pointer;" onclick="window.location.href='profile/messages/?folder={$active_folder}&amp;mid={$value.id}'">
								{if $value.new}<i class="icon-envelope"></i>&nbsp;{/if}
								{$value.subject}
							</td>
							<td style="width: 20%; font-size: 0.75em; white-space: nowrap;cursor:pointer;" onclick="window.location.href='profile/messages/?folder={$active_folder}&amp;mid={$value.id}'">{$value.date_sent|date_format:"%b %e, %Y %l:%m %p"}</td>
						</tr>
					{/if}
				{/foreach}
			{else}
				<tr>
					<td colspan="{if ($active_folder != 1) AND ($active_folder != 2)}5{else}4{/if}">
						<p class="text-center" style="min-height: 100px;">{lang key='no_messages'}</p>
					</td>
				</tr>
			{/if}
		</table>

		<div class="b-pm__messages__list__actions clearfix">
			<input type="hidden" name="action" id="action" value="0">
			<input type="hidden" name="action_param" id="action_param" value="0">

			{if 3 == $active_folder}
				<input type="button" name="delete_forever" value="{lang key='delete_forever'}" class="btn btn-danger btn-small" onclick="delete_messages_forever(); return true;">
			{else}
				<input type="button" name="delete" value="{lang key='delete'}" class="btn btn-danger btn-small" onclick="delete_messages(); return true;">
			{/if}

			{if 2 != $active_folder}
				<select onchange="mark_change(this); return true;">
					<option>{lang key='mark_as'}</option>
					<option value="mark_as_read">{lang key='read'}</option>
					<option value="mark_as_unread">{lang key='unread'}</option>
				</select>

				<select onchange="move_to_change(this); return true;">
					<option value="0">{lang key='move_to'}</option>
					{foreach from=$folders item=folder}
						<option value="{$folder.id}">{$folder.title}</option>
					{/foreach}
					{*<option value="-1">[ {lang key='add_folder'} ]</option>*}
				</select>
			{/if}

			{if isset($pagination)}
				{navigation aTotal=$pagination.total aTemplate=$pagination.url aItemsPerPage=$pagination.limit}
			{/if}
		</div>
	</form>
{else}
	<div class="alert alert-info">{lang key='no_messages'}</div>
{/if}
