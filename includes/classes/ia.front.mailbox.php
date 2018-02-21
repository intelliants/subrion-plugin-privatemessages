<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

class iaMailbox extends abstractCore
{
    const ITEMS_PER_PAGE = 10;

    const INBOX = 1;
    const SENT_MAIL = 2;
    const TRASH = 3;

    protected static $_table = 'messages';
    protected static $_messagesTable = 'messages_folders';
    protected static $_ignoreTable = 'messages_ignore_list';

    public function addMessage($params)
    {
        return $this->iaDb->insert($params, array('date_sent' => iaDb::FUNCTION_NOW), self::getTable());
    }

    public function getMessages($memberId, $folderId, $start = 0, $limit = 0)
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS t1.*, t2.`username` from_username, t3.`username` to_username ";
        $sql .= ", t2.`fullname` from_fullname, t3.`fullname` to_fullname ";
        $sql .= "FROM `" . $this->iaDb->prefix . self::$_table . "` t1 ";
        $sql .= "LEFT JOIN `" . iaUsers::getTable(true) . "` t2 ";
        $sql .= "ON t1.`from_member_id` = t2.`id` ";
        $sql .= "LEFT JOIN `" . iaUsers::getTable(true) . "` t3 ";
        $sql .= "ON t1.`to_member_id` = t3.`id` ";
        $sql .= "WHERE t1.`member_id` = '{$memberId}' ";
        $sql .= "AND t1.`folder_id` = '{$folderId}'";
        $sql .= "ORDER BY t1.`date_sent` DESC";

        if ($limit) {
            $sql .= " LIMIT {$start},{$limit}";
        }

        return $this->iaDb->getAll($sql);
    }

    public function isMessageOwner($memberId, $messageId)
    {
        $sql = 'SELECT COUNT(*) ';
        $sql .= 'FROM `' . self::getTable(true) . '`';
        $sql .= 'WHERE `id` = ' . (int)$messageId . ' ';
        $sql .= 'AND `member_id` = ' . (int)$memberId;

        return (bool)$this->iaDb->getOne($sql);
    }

    public function getMessageById($messageId)
    {
        $sql = <<<SQL
SELECT t1.*, t2.`username` `from_username`, t2.`fullname` `from_name`, 
  t3.`username` `to_username`, t3.`fullname` `to_name`, 
  IF(t4.`member_id` IS NULL, 0, 1) `addressee_ignored` 
  FROM `:prefix:table_messages` t1 
LEFT JOIN `:prefix:table_users` t2 
  ON t1.`from_member_id` = t2.`id` 
LEFT JOIN `:prefix:table_users` t3 
  ON t1.`to_member_id` = t3.`id` 
LEFT JOIN `:prefix:table_ignore` t4 
  ON (t1.`from_member_id` = t4.`blocked_member_id` AND t4.`member_id` = :user) 
WHERE t1.`id` = :message 
LIMIT 1
SQL;
        $sql = iaDb::printf($sql, array(
            'prefix' => $this->iaDb->prefix,
            'table_messages' => self::getTable(),
            'table_users' => iaUsers::getTable(),
            'table_ignore' => self::$_ignoreTable,
            'user' => iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0,
            'message' => (int)$messageId
        ));

        return $this->iaDb->getRow($sql);
    }

    public function markMessagesAsRead($aMessages, $aMember = 0)
    {
        if ($aMessages) {
            $sql = "UPDATE `" . self::getTable(true) . "` ";
            $sql .= 'SET `new` = 0 ';
            $sql .= 'WHERE `id` IN (';
            foreach ($aMessages as $id) {
                $sql .= "'{$id}',";
            }
            // Remove trailing comma
            $sql = substr($sql, 0, -1);
            $sql .= ')';
            if ($aMember) {
                $sql .= "AND `member_id` = '{$aMember}'";
            }

            return $this->iaDb->query($sql);
        }
    }

    public function markMessagesAsUnread($aMessages, $memberId = null)
    {
        if ($aMessages) {
            $sql = "UPDATE `" . self::getTable(true) . "` ";
            $sql .= 'SET `new` = 1 ';
            $sql .= 'WHERE `id` IN (';
            foreach ($aMessages as $id) {
                $sql .= "'{$id}',";
            }
            // Remove trailing comma
            $sql = substr($sql, 0, -1);
            $sql .= ')';
            if ($memberId) {
                $sql .= 'AND `member_id` = ' . (int)$memberId;
            }

            return $this->iaDb->query($sql);
        }
    }

    public function getFolders($userId)
    {
        $sql = <<<SQL
SELECT f.*, COUNT(m.`id`) `messages`, COUNT(1 = m.`new` || NULL) `unread_messages`
  FROM `:prefix:table_folders` f
LEFT JOIN `:table_messages` m 
  ON (f.`id` = m.`folder_id` AND m.`member_id` = :user_id) 
WHERE f.`member_id` = :user_id || f.`id` IN (:folders)
GROUP BY f.`id`
SQL;
        $sql = iaDb::printf($sql, array(
            'prefix' => $this->iaDb->prefix,
            'table_folders' => self::$_messagesTable,
            'table_messages' => self::getTable(true),
            'user_id' => $userId,
            'folders' => implode(',', array(self::INBOX, self::SENT_MAIL, self::TRASH))
        ));

        return $this->iaDb->getAll($sql);
    }

    public function addCustomFolder($userId, $title)
    {
        $values = array(
            'title' => $title,
            'member_id' => (int)$userId,
            'common' => 0
        );

        return $this->iaDb->insert($values, null, self::$_messagesTable);
    }

    public function getNumMessages($memberId, $folderId = null, $onlyNew = null)
    {
        $stmt = '`member_id` = :user';
        if ($folderId) {
            $stmt .= ' AND `folder_id` = :folder';
        }
        if ($onlyNew !== null) {
            $stmt .= ' AND `new` = :type';
        }

        $stmt = iaDb::printf($stmt, array(
            'user' => (int)$memberId,
            'folder' => (int)$folderId,
            'type' => (int)$onlyNew
        ));

        return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, $stmt, self::getTable());
    }

    public function getNumInboxMessages($memberId)
    {
        return $this->getNumMessages($memberId, self::INBOX);
    }

    public function getNumUnreadMessages($memberId, $folderId = self::INBOX)
    {
        return $this->getNumMessages($memberId, $folderId, true);
    }

    public function getNumInboxUnreadMessages($memberId)
    {
        return $this->getNumUnreadMessages($memberId, self::INBOX);
    }

    public function folderExists($name, $memberId)
    {
        $sql =
            'SELECT 1 ' .
            'FROM `:prefix:table` ' .
            "WHERE `title` = ':title' " .
            ' AND `member_id` IN (0, :member)';
        $sql = iaDb::printf($sql, array(
            'prefix' => $this->iaDb->prefix,
            'table' => self::$_messagesTable,
            'title' => iaSanitize::sql($name),
            'member' => $memberId
        ));

        return (bool)$this->iaDb->getOne($sql);
    }

    public function renameFolder($folderId, $memberId, $name)
    {
        $stmt = iaDb::printf('`id` = :folder AND `member_id` = :member', array(
            'folder' => (int)$folderId,
            'member' => (int)$memberId
        ));
        $values = array(
            'title' => $name
        );
        return $this->iaDb->update($values, $stmt, null, self::$_messagesTable);
    }

    public function deleteFolder($aFolder, $aMember)
    {
        // Delete messages
        $sql = "DELETE FROM `" . self::getTable(true) . "`";
        $sql .= "WHERE `folder_id` = '{$aFolder}'";
        $sql .= "AND `member_id` = '{$aMember}'";
        $this->iaDb->query($sql);

        // Delete folder
        $sql = "DELETE FROM `{$this->iaDb->prefix}messages_folders`";
        $sql .= "WHERE `id` = '{$aFolder}'";
        $sql .= "AND `member_id` = '{$aMember}'";
        $this->iaDb->query($sql);
    }

    /**
     * Delete all messages from trash by member id
     *
     * @param int $aMember member id
     *
     * @return void
     */
    public function emptyTrash($memberId)
    {
        $this->iaDb->delete('`folder_id` = :folder AND `from_member_id` = :member', self::getTable(),
            array('folder' => self::TRASH, 'member' => $memberId));
    }

    /**
     * Move messages to a different folder
     *
     * @param array $aMessages list of messages to be moved
     * @param int $aFolder destination folder id
     * @param int $aMember member id
     *
     * @return void|bool
     */
    public function moveMessagesToFolder($aMessages, $aFolder, $aMember)
    {
        if ($aMessages) {
            $check = false;
            if ($aFolder <= 3) {
                $check = true;
            } else {
                // Check if member owns the folder
                $sql = "SELECT COUNT(*) ";
                $sql .= "FROM `{$this->iaDb->prefix}messages_folders`";
                $sql .= "WHERE `member_id` = '{$aMember}'";
                if ($this->iaDb->getOne($sql)) {
                    $check = true;
                }
            }
            if ($check) {
                // member _is_ the owner
                $sql = "UPDATE `" . $this->iaDb->prefix . self::$_table . "`";
                $sql .= "SET `folder_id` = '{$aFolder}'";
                $sql .= "WHERE `member_id` = '{$aMember}'";
                $sql .= "AND `id` IN (";
                foreach ($aMessages as $id) {
                    $id = (int)$id;
                    $sql .= "'{$id}',";
                }
                // Remove trailing comma
                $sql = substr($sql, 0, -1);
                $sql .= ')';

                return $this->iaDb->query($sql);
            }
        }
        return false;
    }

    /**
     * Move messages to Trash folder
     *
     * @param array $aMessages list of messages to be deleted
     * @param int $aMember member id
     *
     * @return void
     */
    public function moveMessagesToTrash($aMessages, $memberId)
    {
        $sql = "UPDATE `" . $this->iaDb->prefix . self::$_table . "`";
        $sql .= 'SET `folder_id` = ' . self::TRASH . ' ';
        $sql .= "WHERE `member_id` = '{$memberId}' ";
        $sql .= "AND `id` IN (";
        foreach ($aMessages as $id) {
            $sql .= "'{$id}',";
        }
        // Remove trailing comma
        $sql = substr($sql, 0, -1);
        $sql .= ')';

        return $this->iaDb->query($sql);
    }

    /**
     * Delete messages by member id
     *
     * @param array $aMessages list of messages to be deleted
     * @param int $aMember member id
     *
     * @return void
     */
    public function deleteMessages($aMessages, $memberId)
    {
        $sql = "DELETE FROM `" . $this->iaDb->prefix . self::$_table . "` ";
        $sql .= "WHERE `member_id` = '{$memberId}'";
        $sql .= "AND `id` IN (";
        foreach ($aMessages as $id) {
            $sql .= "'{$id}',";
        }
        $sql = substr($sql, 0, -1);
        $sql .= ')';

        $this->iaDb->query($sql);
    }

    /**
     * Return a number of new messages
     *
     * @param int $aMember member id
     * @param int $aFolder folder id
     *
     * @return int
     */
    public function getNumNewMessages($aMember, $aFolder = 0)
    {
        $sql = "SELECT COUNT(*)";
        $sql .= "FROM `" . $this->iaDb->prefix . self::$_table . "`";
        $sql .= "WHERE `member_id` = '{$aMember}'";
        $sql .= 'AND `new` = 1 ';
        if ($aFolder) {
            $sql .= "AND `folder_id`='{$aFolder}'";
        }

        return $this->iaDb->getOne($sql);
    }

    public function getIgnoreList($userId)
    {
        $sql =
            'SELECT * ' .
            'FROM `:prefix:table_users` ' .
            'WHERE `id` IN (SELECT `blocked_member_id` FROM `:prefix:table_ignore` WHERE `member_id` = :user) ';
        $sql = iaDb::printf($sql, array(
            'prefix' => $this->iaDb->prefix,
            'table_users' => iaUsers::getTable(),
            'table_ignore' => self::$_ignoreTable,
            'user' => (int)$userId
        ));

        return $this->iaDb->getAll($sql);
    }

    public function addToIgnoreList($userId, $blockedUserId)
    {
        $values = array(
            'member_id' => (int)$userId,
            'blocked_member_id' => (int)$blockedUserId
        );
        return $this->iaDb->insert($values, null, self::$_ignoreTable);
    }

    public function removeFromIgnoreList($userId, $userIdToBeRemoved)
    {
        return $this->iaDb->delete('`member_id` = :user AND `blocked_member_id` = :blocked_user', self::$_ignoreTable,
            array('user' => (int)$userId, 'blocked_user' => (int)$userIdToBeRemoved));
    }

    public function isUserIgnored($userId, $blockedUserId)
    {
        return $this->iaDb->exists(
            '`member_id` = :user AND `blocked_member_id` = :blocked_user',
            array(
                'user' => (int)$userId,
                'blocked_user' => (int)$blockedUserId
            ),
            self::$_ignoreTable
        );
    }

    public function sendMail($params, $users_info)
    {
        $sender_url = IA_URL . 'member/' . $users_info['username'] . '.html';

        $iaMailer = $this->iaCore->factory('mailer');

        if (!$iaMailer->loadTemplate('pm_notification')) {
            return false;
        }

        $iaMailer->addAddress($users_info['recipient_email']);
        $iaMailer->setReplacements([
            'pm_subject' => $params['subject'],
            'sender' => $users_info['username'],
            'senderUrl' => $sender_url,
            'siteUrl' => IA_URL,
            'siteName' => $this->iaCore->get('site')
        ]);

        return $iaMailer->send();
    }
}
