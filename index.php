<?php
//##copyright##

$iaMailbox = $iaCore->factoryPlugin(IA_CURRENT_PLUGIN, iaCore::FRONT, 'mailbox');
$iaUtil = $iaCore->factory(iaCore::CORE, 'util');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$output = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

	$action = isset($_GET['action']) ? $_GET['action'] : false;
	switch ($action)
	{
		case 'mkdir':

			$name = $_GET['name'];
			if (preg_match('/^[\w\s]{1,15}$/', $name))
			{
				if (!$iaMailbox->folderExists($name, iaUsers::getIdentity()->id))
				{
					$folder_id = $iaMailbox->addCustomFolder(iaUsers::getIdentity()->id, $name);

					$output['error'] = false;
					$output['message'] = iaLanguage::get('folder_created');
					$output['folder'] = $folder_id;
				}
				else
				{
					$output['message'] = iaLanguage::get('folder_exist');
				}
			}
			else
			{
				$output['message'] = iaLanguage::get('folder_invalid_name');
			}

			break;

		case 'rename':
			$name = $_GET['name'];
			if (preg_match('/^[\w\s]{1,15}$/', $name))
			{
				if (!$iaMailbox->folderExists($name, iaUsers::getIdentity()->id))
				{
					$id = (int)$_GET['folder_id'];
					$iaMailbox->renameFolder($id, iaUsers::getIdentity()->id, $name);

					$output['error'] = false;
					$output['message'] = iaLanguage::get('folder_renamed');
				}
				else
				{
					$output['message'] = iaLanguage::get('folder_exist');
				}
			}
			else
			{
				$output['message'] = iaLanguage::get('failed_to_rename_folder');
			}

			break;

		case 'rmdir':

			$id = (int)$_GET['folder_id'];
			$iaMailbox->deleteFolder($id, iaUsers::getIdentity()->id);

			$output['error'] = false;
			$output['message'] = iaLanguage::get('pm_folder_deleted');

			break;

		default:

			if (isset($_GET['q']) && $_GET['q'])
			{
				$stmt = '(`username` LIKE :name OR `fullname` LIKE :name) AND `status` = :status AND `id` != :id ORDER BY `username` ASC';
				$iaDb->bind($stmt, array('name' => $_GET['q'] . '%', 'status' => iaCore::STATUS_ACTIVE, 'id' => iaUsers::getIdentity()->id));

				unset($output);
				$output = $iaDb->onefield('username', $stmt, 0, 20, iaUsers::getTable());
			}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!iaUsers::hasIdentity())
	{
		return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
	}

	$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : false);

	$type = iaView::SUCCESS;

	switch ($action)
	{
		case 'email':
			if (isset($_POST['addressee']) && is_numeric($_POST['addressee']))
			{
				$result = array(
					'message' => iaLanguage::get('message_is_empty'),
					'type' => iaView::ERROR
				);

				if ($_POST['body'])
				{
					$emailAddress = $iaDb->one('`email`', '`id` = ' . $_POST['addressee'], 'members');

					if (iaValidate::isEmail($emailAddress))
					{
						$iaMailer = $iaCore->factory(iaCore::CORE, 'mailer');

						$iaMailer->AddAddress($emailAddress);
						$iaMailer->Subject = iaSanitize::tags($_POST['subject']);
						$iaMailer->Body = (iaSanitize::tags($_POST['body']));

						if ($iaMailer->Send())
						{
							$result['message'] = '';
							$result['type'] = iaView::SUCCESS;
						}
						else
						{
							$result['message'] = iaLanguage::get('unable_to_send_email');
						}
					}
					else
					{
						$result['message'] = iaLanguage::get('invalid_addressee');
					}
				}

				echo $iaUtil->jsonEncode($result);
				exit;
			}
			if (isset($_GET['user']) && is_numeric($_GET['user']))
			{
				$iaUsers = $iaCore->factory(iaCore::CORE, 'users');
				$addressee = $iaUsers->getInfo((int)$_GET['user']);
				if ($addressee)
				{
					$iaCore->iaSmarty->assign('addressee', $addressee);
					echo $iaCore->iaSmarty->fetch('compose.tpl');
					exit;
				}
			}
			echo 'Invalid request';
			exit;

		case 'compose':
			$recip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
			$recip = $iaDb->row("`id`, `fullname`, `username`", "`id` = '$recip_id'", iaUsers::getTable());
			$iaView->assign('recip', $recip);

			iaBreadcrumb::toEnd(iaLanguage::get('compose'), IA_SELF);

			break;

		case 'mark_as_read':
			$iaMailbox->markMessagesAsRead($_POST['messages'], iaUsers::getIdentity()->id);
			$iaView->setMessages(iaLanguage::get('marked_read'), iaView::SUCCESS);

			break;

		case 'mark_as_unread':
			$iaMailbox->markMessagesAsUnread($_POST['messages'], iaUsers::getIdentity()->id);
			$iaView->setMessages(iaLanguage::get('marked_unread'), iaView::SUCCESS);

			break;

		case 'move_to':

			$iaMailbox->moveMessagesToFolder($_POST['messages'], (int)$_POST['action_param'], iaUsers::getIdentity()->id);
			$iaView->setMessages(iaLanguage::get('message_moved'), iaView::SUCCESS);

			break;

		case 'delete':

			$iaMailbox->moveMessagesToTrash($_POST['messages'], iaUsers::getIdentity()->id);
			$iaView->setMessages(iaLanguage::get('pm_deleted'), iaView::SUCCESS);

			break;

		case 'delete_forever':

			$iaMailbox->deleteMessages($_POST['messages'], iaUsers::getIdentity()->id);
			$iaView->setMessages(iaLanguage::get('pm_deleted_from_trash'), iaView::SUCCESS);

			break;

		case 'ignore-add':

			if (isset($_GET['id']) && (int)$_GET['id'] > 0)
			{
				$iaMailbox->addToIgnoreList(iaUsers::getIdentity()->id, (int)$_GET['id']);
				$iaView->setMessages(iaLanguage::get('user_messages_now_ignored'), iaView::SUCCESS);
			}
			else
			{
				$iaView->setMessages(iaLanguage::get('invalid_parameters'));
			}
			unset($_GET['action']);

			break;

		case 'ignore-remove':
			if (isset($_GET['id']) && (int)$_GET['id'] > 0)
			{
				$iaMailbox->removeFromIgnoreList(iaUsers::getIdentity()->id, $_GET['id']);
				$iaView->setMessages(iaLanguage::get('user_excluded_from_ignore'), iaView::SUCCESS);
			}
			else
			{
				$iaView->setMessages(iaLanguage::get('invalid_parameters'));
			}
			unset($_GET['action']);

			break;

		case 'move_to_new':
			$name = $_GET['action_param'];
			if (preg_match('/^[a-z0-9\s_]{1,15}$/', $name))
			{
				$folder_id = $iaMailbox->addCustomFolder(iaUsers::getIdentity()->id, $name);
				$iaMailbox->moveMessagesToFolder($_POST['messages'], $folder_id, iaUsers::getIdentity()->id);
				$messages[] = str_replace('{1}', 'messages?folder=' . $folder_id, iaLanguage::get('message_moved_to'));
				$iaView->setMessages($messages, iaView::SUCCESS);
			}
			else
			{
				$iaView->setMessages(iaLanguage::get('folder_invalid_name'));
			}

			break;
	}

	if (isset($_POST['send']))
	{
		$addressee = iaSanitize::sql($_POST['username']);

		if (empty($addressee))
		{
			$iaView->setMessages(iaLanguage::get('addressee_is_not_specified'));
		}
		elseif (iaUsers::getIdentity()->username == $addressee)
		{
			$iaView->setMessages(iaLanguage::get('tried_to_send_to_yourself'));
		}
		elseif ($iaDb->exists("`username` = '" . $addressee . "'", null, iaUsers::getTable()))
		{
			$member = $iaDb->row("`id`, `fullname`, `username`", "`username` = '$addressee'", iaUsers::getTable());

			if ($iaMailbox->isUserIgnored($member['id'], iaUsers::getIdentity()->id))
			{
				$iaView->setMessages(iaLanguage::get('user_blocked_messages'), iaView::ALERT);
			}
			else
			{
				$params = array(
					'member_id' => $member['id'],
					'from_member_id' => iaUsers::getIdentity()->id,
					'to_member_id' => $member['id'],
					'subject' => $_POST['subject'],
					'body' => $_POST['body'],
					'folder_id' => iaMailbox::INBOX,
					'new' => 1
				);
				$iaMailbox->addMessage($params);

				// Add to Sent folder
				$params['member_id'] = iaUsers::getIdentity()->id;
				$params['folder_id'] = iaMailbox::SENT_MAIL;
				$params['new'] = 0;
				$iaMailbox->addMessage($params);

				$url_to_change = "member/{$member['username']}.html";
				$link = "<a href=\"{$url_to_change}\">{$member['username']}</a>";

				unset($_GET['action']);

				$iaView->setMessages(str_replace('%NAME%', $link, iaLanguage::get('mail_sent_to')), iaView::SUCCESS);
			}
		}
		else
		{
			unset($_POST['send']);

			$iaView->setMessages(iaLanguage::getf('member_is_not_registered', array('name' => $addressee)));
		}
	}

	$folderId = isset($_GET['folder']) && $_GET['folder'] > 0 ? (int)$_GET['folder'] : (isset($_GET['action']) ? 0 : 1);

	$iaView->assign('active_folder', $folderId);
	$iaView->assign('folders', $iaMailbox->getFolders(iaUsers::getIdentity()->id));

	$mid = isset($_GET['mid']) ? (int)$_GET['mid'] : false;
	if ($mid)
	{
		if ($iaMailbox->isMessageOwner(iaUsers::getIdentity()->id, $mid)) // read message
		{
			$iaMailbox->markMessagesAsRead(array ($mid), iaUsers::getIdentity()->id);

			$iaView->assign('pm_message', $iaMailbox->getMessage($mid));
			$iaView->assign('show_message', true);
		}
	}
	else
	{
		$page = (isset($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;

		$pagination = array(
			'limit' => iaMailbox::ITEMS_PER_PAGE,
			'start' => ($page - 1) * iaMailbox::ITEMS_PER_PAGE,
			'total' => 0,
			'url' => IA_SELF . '?page={page}'
		);

		$messages = $iaMailbox->getMessages(iaUsers::getIdentity()->id, $folderId, $pagination['start'], $pagination['limit']);

		if ($messages)
		{
			$pageUrl = 'profile/messages/?folder=' . $folderId;

			$pagination['total'] = $iaDb->foundRows();
			$pagination['url'] = IA_URL . $pageUrl . '&page={page}';
		}

		$iaView->assign('pagination', $pagination);
		$iaView->assign('pm_messages', $messages);
	}

	$ignoredUsers = $iaMailbox->getIgnoreList(iaUsers::getIdentity()->id);

	$num = $iaMailbox->getNumMessages(iaUsers::getIdentity()->id);
	$num_unread = $iaMailbox->getNumUnreadMessages(iaUsers::getIdentity()->id);

	$iaView->assign('ignored_users', $ignoredUsers);
	$iaView->assign('num', $num);
	$iaView->assign('num_unread', $num_unread);

	$iaView->display();
}