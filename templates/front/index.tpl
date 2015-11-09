<div class="tabbable">
	<ul class="nav nav-tabs">
		<li class="active"><a data-toggle="tab" href="#tab-messages"><span><i class="icon-envelope"></i> {lang key='messages'}</span></a></li>
		<li><a data-toggle="tab" href="#tab-folders"><span><i class="icon-folder-close"></i> {lang key='folders'}</span></a></li>
	</ul>

	<div class="tab-content b-pm">
		<div id="tab-messages" class="tab-pane clearfix active">
			<ul class="b-pm__nav">
				<li class="b-pm__nav__compose{if isset($smarty.get.action)} active{/if}"><a href="{$smarty.const.IA_URL}profile/messages/?action=compose">{lang key='compose'}</a></li>

				{foreach from=$folders item=value key=key}
					<li class="{if $value.unread_messages}b-pm__nav__unread{/if}{if $active_folder == $value.id} active{/if}">
						<a href="#" onclick="folder_click('{$value.id}'); return false;">{if $value.unread_messages}{$value.title} ({$value.unread_messages}){else}{$value.title}{/if}</a>
					</li>
				{/foreach}
			</ul>

			<div class="b-pm__messages">
				{if isset($smarty.get.action) && 'compose' == $smarty.get.action}
					<form method="post" class="b-pm__messages__compose form-horizontal" name="compose" action="{$smarty.const.IA_URL}profile/messages/?action=compose">
						{preventCsrf}
						<div class="control-group">
							<label for="to" class="control-label">{lang key='to'}:</label>
							<div class="controls">
								<input type="text" autocomplete="off" name="username" id="to" size="20" value="{$recip.username}" placeholder="{lang key='username'}">
							</div>
						</div>

						<div class="control-group">
							<label for="subject" class="control-label">{lang key='subject'}:</label>
							<div class="controls">
								<input type="text" class="text" name="subject" id="subject" size="40" value="{if isset($smarty.post.subject)}{$smarty.post.subject}{/if}" maxlength="50" placeholder="{lang key='subject'}">
							</div>
						</div>

						<div class="control-group">
							<label for="message" class="control-label">{lang key='message'}:</label>
							<div class="controls">
								<textarea name="body" id="message" rows="10" class="input-block-level">{if isset($smarty.post.body)}{$smarty.post.body}{/if}</textarea>
							</div>
						</div>

						<div class="b-pm__messages__list__actions clearfix">
							<input type="submit" name="send" value="{lang key='send'}" class="btn btn-primary">
						</div>
					</form>
				{elseif isset($show_message)}
					{if isset($pm_message)}
						<div class="b-pm__message" id="message">
							<div class="b-pm__message__header">
								{if $active_folder == 2}{lang key='to'}: {else}{lang key='from'}: {/if}
								<b>{if $active_folder == 2}{$pm_message.to_name}{else}{ia_url type='link' item='members' data=$pm_message.from_username text=$pm_message.from_name}{/if}</b>
							</div>
							<div class="b-pm__message__body">
								<h4>{$pm_message.subject}</h4>
								{$pm_message.body|nl2br}
							</div>
						</div>
					{/if}

					{if isset($smarty.get.mid) && ($smarty.get.folder != 2)}
						<div class="b-pm__message__reply">
							<div class="b-pm__messages__list__actions">
								<button onclick="reply_click(); return true;" class="btn btn-primary">{lang key='reply'}</button>

								<div class="b-pm__message__reply__body" id="reply_content">
									<form method="post" action="{$smarty.const.IA_URL}profile/messages/?action=compose">
										{preventCsrf}
										<div id="edit_subject_link" style="margin-bottom: 10px;">
											<a href="javascript:void(0)" onclick="editSubjectClick(); return false;">{lang key='edit_subject'}</a>
										</div>
										<div style="display: none; margin-bottom: 10px;" id="edit_subject">
											{lang key='subject'}: <input type="text" name="subject" size="40" maxlength="50" style="float:none;" id="subject">
										</div>
										<input type="hidden" value="Re: {$pm_message.subject}" id="subject_copy">
										<textarea rows="10" class="input-block-level" cols="50" name="body" id="reply_message"></textarea>
										<div style="display: none;">
											<textarea id="reply_message_copy" cols="15" rows="5">{$pm_message.body}</textarea>
										</div>
										<div>
											<input type="hidden" name="username" value="{$pm_message.from_username}">
											<input type="submit" name="send" value="{lang key='send'}" class="btn btn-primary">
											<input type="button" value="{lang key='cancel'}" class="btn btn-danger" onclick="discard_reply(); return true;">
										</div>
									</form>
								</div>
							</div>
						</div>
					{/if}
				{else}
					{include file="{$smarty.const.IA_PLUGINS}privatemessages/templates/front/messages.tpl"}
				{/if}
			</div>
		</div>

		<div id="tab-folders" class="tab-pane">
			<div class="b-pm__messages">
				<div class="b-pm__messages__list">
					<table class="table table-condensed table-striped">
						<thead>
							<tr>
								<th width="50%">{lang key='name'}</th>
								<th width="40%"></th>
								<th>{lang key='amount'}</th>
								<th>{lang key='unread'}</th>
							</tr>
						</thead>
				
						<tfoot>
							<tr>
								<td>{lang key='total'}</td>
								<td></td>
								<td class="text-right">{$num}</td>
								<td class="text-right">{$num_unread}</td>
							</tr>
						</tfoot>
				
						<tbody>
							{if $folders}
								{foreach from=$folders item=value key=key}
									<tr id="folder_{$value.id}">
										<td>
											<a href="profile/messages/?folder={$value.id}">{$value.title}</a>
										</td>
										<td class="text-right">
											{if !$value.common}
											<span class="folder-actions">
												<button onclick="rename_folder('{$value.id}', '{$value.title}'); return true;" class="btn btn-mini btn-info">{lang key='rename'}</button>
												<button onclick="rmdir('{$value.id}', '{$value.messages}'); return true;" class="btn btn-mini btn-danger">{lang key='delete'}</button>
											</span>
											{/if}
										</td>
										<td class="text-right">{$value.messages}</td>
										<td class="text-right">{if $value.unread_messages}<strong>{$value.unread_messages}</strong>{else}{$value.unread_messages}{/if}</td>
									</tr>
								{/foreach}
							{/if}
						</tbody>
					</table>
				
					<div class="b-pm__messages__list__actions clearfix">
						<input type="text" class="span2" id="js-folder-name" size="15" maxlength="15" placeholder="{lang key='add_folder'}">
						<button onclick="mkdir()" class="btn btn-success"><i class="icon-plus-sign"></i></button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{ia_add_media files="js:_IA_URL_plugins/privatemessages/js/front/mailbox, css:_IA_URL_plugins/privatemessages/templates/front/css/style"}