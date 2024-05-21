<?php

$api = true;
require "../_common.php";
require_once "../_chatMessage.php";
require_once "../../modules/moderationDB.php";

/*
 * POST /member-area/api/reports.php
 * Adds a report for a message in a conversation
 *
 * Input (JSON) :
 * {
 *    "convId": string, // The conversation id
 *    "msgId": int // The message id
 *    "reason": string // The reason for the report
 * }
 *
 * Returns :
 * 200 OK
 *
 * DELETE /member-area/api/reports.php
 * Deletes a report (admin only)
 *
 * Parameters (URL) :
 * ?id : the report id
 *
 * Returns :
 * 200 OK
 */

UserSession\requireLevel(User\LEVEL_SUBSCRIBER);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the JSON
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        bail(400);
    }

    // Do various parameter checks
    $convId = $data["convId"] ?? null;
    if (!is_string($convId)) {
        bail(400);
    }
    
    $msgId = $data["msgId"] ?? null;
    if (!is_int($msgId)) {
        bail(400);
    }

    // Make sure that the reason isn't empty and not just a sequence of spaces
    $reason = trim($data["reason"] ?? "");
    if (empty($reason)) {
        bail(400);
    }
    
    $conv = ConversationDB\find($data["convId"]);
    if ($conv === null) {
        bail(404);
    }
    
    foreach ($conv["messages"] as $msg) {
        if ($msg["id"] === $data["msgId"]) {
            if ($msg["author"] === $user["id"]) {
                bail(403);
            }
            
            ModerationDB\addReport($convId, $msgId, $user["id"], $reason);
            exit;
        }
    }
    
    bail(404);
} else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    // Admin only!
    if (User\level($user["id"]) < User\LEVEL_ADMIN) {
        bail(400);
    }

    $id = $_GET["id"] ?? null;
    if (!is_numeric($id)) {
        bail(400);
    }
    
    if (!ModerationDB\deleteReport(intval($id))) {
        bail(404);
    }
}
else {
    bail(400);
}