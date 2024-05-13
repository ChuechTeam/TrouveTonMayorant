<?php

$api = true;
require "../_common.php";
require_once "../_conversation.php";

$convId = $_GET["id"];
$conv = User\findConversation($user["id"], $convId);

if ($conv === null) {
    conversation(null);
    bail(404);
} else {
    conversation($convId, User\level($user["id"]) >= User\LEVEL_ADMIN);
}