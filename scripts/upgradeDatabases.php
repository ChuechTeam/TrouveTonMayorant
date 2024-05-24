<?php

require_once __DIR__ . "/../src/modules/userDB.php";
require_once __DIR__ . "/../src/modules/conversationDB.php";
require_once __DIR__ . "/../src/modules/moderationDB.php";
require_once __DIR__ . "/../src/modules/viewDB.php";

/*
 * To launch the script, run either, in the project directory:
 * - ./upgrade.sh
 * - php scripts/upgradeDatabases.php
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

printf("Initiating view database upgrade...\n");
ViewDB\upgradeAll();
printf("View database upgrade complete!\n");