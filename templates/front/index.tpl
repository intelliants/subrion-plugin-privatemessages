<div class="tabbable p-messages">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#tab-messages"><span><span class="fa fa-envelope-o"></span> {lang key='messages'}</span></a></li>
        <li><a data-toggle="tab" href="#tab-folders"><span><span class="fa fa-folder-o"></span> {lang key='folders'}</span></a></li>
    </ul>

    <div class="tab-content b-pm">
        <div id="tab-messages" class="tab-pane clearfix active">
            <ul class="b-pm__nav">
                <li class="b-pm__nav__compose{if isset($smarty.get.action)} active{/if}"><a href="{$smarty.const.IA_URL}profile/messages/?action=compose">{lang key='compose'}</a></li>

                {foreach $folders as $value}
                    <li class="{if $value.unread_messages}b-pm__nav__unread{/if}{if $active_folder == $value.id} active{/if}">
                        <a href="#" onclick="folder_click('{$value.id}'); return false;">{if $value.unread_messages}{if $value.common}{lang key=$value.title}{else}{$value.title}{/if} ({$value.unread_messages}){else}{if $value.common}{lang key=$value.title}{else}{$value.title}{/if}{/if}</a>
                    </li>
                {/foreach}
            </ul>

            <div class="b-pm__messages">
                {if isset($smarty.get.action) && 'compose' == $smarty.get.action}
                    <form method="post" class="b-pm__messages__compose form-horizontal" name="compose" action="{$smarty.const.IA_URL}profile/messages/?action=compose">
                        {preventCsrf}
                        <div class="form-group">
                            <label for="to" class="control-label col-md-3">{lang key='to'}:</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" autocomplete="off" name="username" id="to" size="20" value="{$recip.username}" placeholder="{lang key='username'}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subject" class="control-label col-md-3">{lang key='subject'}:</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="subject" id="subject" size="40" value="{if isset($smarty.post.subject)}{$smarty.post.subject}{/if}" maxlength="50" placeholder="{lang key='subject'}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message" class="control-label col-md-3">{lang key='message'}:</label>
                            <div class="col-md-9">
                                <textarea name="body" id="message" rows="10" class="input-block-level form-control">{if isset($smarty.post.body)}{$smarty.post.body}{/if}</textarea>
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
                                <b>{if $active_folder == 2}{$pm_message.to_name}{else}{ia_url type='link' item='members' data=['username' => $pm_message.from_username] text=$pm_message.from_name}{/if}</b>
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
                                <button onclick="reply_click(); return true;" id="reply_button" class="btn btn-primary">{lang key='reply'}</button>

                                <div class="b-pm__message__reply__body" id="reply_content">
                                    <form method="post" action="{$smarty.const.IA_URL}profile/messages/?action=compose" class="form-horizontal">
                                        {preventCsrf}
                                        <div id="edit_subject_link" style="margin-bottom: 10px;">
                                            <a href="javascript:void(0)" onclick="editSubjectClick(); return false;">{lang key='edit_subject'}</a>
                                        </div>
                                        <div style="display: none; margin-bottom: 10px;" id="edit_subject" class="form-group">
                                            <label for="subject" class="col-md-1 control-label">{lang key='subject'}:</label>
                                            <div class="col-md-7">
                                                <input type="text" name="subject" class="form-control" size="40" maxlength="50" style="float:none;" id="subject">
                                            </div>
                                        </div>
                                        <input type="hidden" value="Re: {$pm_message.subject}" id="subject_copy">
                                        <textarea class="input-block-level form-control" rows="10" cols="50" name="body" id="reply_message"></textarea>
                                        <div style="display: none;">
                                            <textarea id="reply_message_copy" cols="15" rows="5">{$pm_message.body}</textarea>
                                        </div>
                                        <div style="margin-top:15px">
                                            <input type="hidden" name="username" value="{$pm_message.from_username}">
                                            <input type="submit" name="send" value="{lang key='send'}" class="btn btn-primary" id="reply">
                                            <input type="button" value="{lang key='cancel'}" class="btn btn-danger" onclick="discard_reply(); return true;">
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    {/if}
                {else}
                    {include 'extra:privatemessages/messages'}
                {/if}
            </div>
        </div>

        <div id="tab-folders" class="tab-pane">
            <div class="b-pm__messages">
                <div class="b-pm__messages__list">
                    <table class="table table-condensed">
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
                            {foreach $folders as $value}
                                <tr id="folder_{$value.id}">
                                    <td>
                                        <a href="{$smarty.const.IA_URL}profile/messages/?folder={$value.id}">{if $value.common}{lang key=$value.title}{else}{$value.title}{/if}</a>
                                    </td>
                                    <td class="text-right">
                                        {if !$value.common}
                                            <span class="folder-actions">
                                                <button onclick="rename_folder('{$value.id}', '{$value.title}'); return true;" class="btn btn-xs btn-info">{lang key='rename'}</button>
                                                <button onclick="rmdir('{$value.id}', '{$value.messages}'); return true;" class="btn btn-xs btn-danger">{lang key='delete'}</button>
                                            </span>
                                        {/if}
                                    </td>
                                    <td class="text-right">{$value.messages}</td>
                                    <td class="text-right">{if $value.unread_messages}<strong>{$value.unread_messages}</strong>{else}{$value.unread_messages}{/if}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>

                    <div class="b-pm__messages__list__actions clearfix form-inline">
                        <input type="text" class="col-md-2 form-control" id="js-folder-name" size="15" maxlength="15" placeholder="{lang key='add_folder'}">
                        <button onclick="mkdir()" class="btn btn-success"><span class="fa fa-plus"></span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{ia_add_media files="js:_IA_URL_modules/privatemessages/js/front/mailbox, css:_IA_URL_modules/privatemessages/templates/front/css/style"}