<?php

require_once __DIR__ . "/../src/modules/userDB.php";
require_once __DIR__ . "/../src/modules/conversationDB.php";
require_once __DIR__ . "/../src/modules/moderationDB.php";

/*
 * Pour lancer le script : ./upgrade.sh OU php upgradeDatabases.php
 */

printf("Initiating user database upgrade...\n");
UserDB\load();
printf("User database upgrade complete!\n");

printf("Initiating conversation database upgrade...\n");
ConversationDB\upgradeAll();
printf("Conversation database complete!\n");

printf("Initiating moderation database upgrade...\n");
ModerationDB\load();
printf("Moderation database upgrade complete!\n");