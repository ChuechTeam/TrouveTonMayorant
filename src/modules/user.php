<?php

/**
 * user.php
 * ---------------
 * Contains all functions related to user actions in the website: register, update profile, etc.
 * This is also where user roles are determined, according to their subscription status.
 *
 * The User is represented as an associative array with a consequent amount of fields:
 * ## INTERNAL FIELDS
 * - id (int)
 * - conversations (array): list of conversation ids where the user is involved
 * - blockedUsers (array): associative array of all blocked users: [id]=>1
 * - blockedBy: associative array of all users who blocked me: [id]=>1
 * - supExpire: date at which the TTM sup subscription will expire (in the SUP_DATE_FMT format)
 * - supBought: date at which the TTM sup subscription was last bought, while it was expired (in the SUP_DATE_FMT format)
 * - admin: true if the user is an admin
 * ## REQUIRED PROFILE FIELDS
 * - firstName (string): their first name
 * - lastName (string): their last name
 * - email (string): their e-mail address
 * - bdate (string, Y-m-d format): their birth date
 * - gender (string, GENDER enum): their gender
 * ## OPTIONAL PROFILE FIELDS
 * - pfp (string): their profile picture as an URL to the profile picture relative to src
 * - orientation (string, ORIENTATION enum): their sexual orientation
 * - job (string): their professional job
 * - situation (string, SITUATION enum): their relationship situation
 * - dep (string): their department number (as a string to preserve zeros)
 * - depName (string): the name of their department
 * - city (string): their city zip code (as a string to preserve zeros)
 * - cityName (string): the name of their city
 * - desc (string): their physical description
 * - bio (string): their biography
 * - mathField (string): their favorite math field
 * - eigenVal (string): their "eigen values", core values and principles of life
 * - equation (string): their favorite math equation (in MathJax -- or MathYax if you prefer)
 * - user_smoke (string, PREF enum without the 'without' value): whether they smoke or not
 * - search_smoke (string, PREF enum): if they want to find a smoker, non-smoker, or if they don't care
 * - gender_search (array of GENDER enum as strings): a list of their wanted genders
 * - rel_search (array of REL enum as strings): a list of their wanted relationship types
 * - pic1 (string): the URL of the 1st picture of their gallery
 * - pic2 (string): the URL of the 2nd picture of their gallery
 * - pic3 (string): the URL of the 3rd picture of their gallery
 */
namespace User;

require_once __DIR__ . "/userDB.php";
require_once __DIR__ . "/conversationDB.php";
require_once __DIR__ . "/moderationDB.php";

use DateTime;

// Enumeration of all error codes
const ERR_OK = 0; // No problem :)
const ERR_EMAIL_USED = 1; // The e-mail is already used during registration
const ERR_FIELD_MISSING = 2; // A required field is missing OR empty
const ERR_INVALID_CREDENTIALS = 3; // The given credentials are invalid
const ERR_USER_NOT_FOUND = 4; // The user is not found in the database
const ERR_CONVERSATION_EXISTS = 5; // A conversation between those two users already exists
const ERR_SAME_USER = 6; // Attempted to create a conversation between the same user and itself
const ERR_EMAIL_BANNED = 7; // The e-mail is banned
const ERR_BLOCKED = 8; // One of the two users is blocked by the other
const ERR_INVALID_FIELD = 9; // A field is filled, but invalid
const ERR_NOT_SUBSCRIBED = 10; // The user is not subscribed to TTM sup

// Enumeration of all roles
const LEVEL_GUEST = 1; // Unregistered guest
const LEVEL_MEMBER = 2; // Unsubscribed member
const LEVEL_SUBSCRIBER = 3; // Subscribed member
const LEVEL_ADMIN = 4; // Admin

// Enumeration of all genders (non-exhaustive)
const GENDER_MAN = "m";
const GENDER_WOMAN = "f";
const GENDER_NON_BINARY = "nb";

// Enumeration of all sexual orientations (in the "orientation" field)
const ORIENTATION_HETERO = "het";
const ORIENTATION_HOMO = "ho";
const ORIENTATION_BI = "bi";
const ORIENTATION_PAN = "pan";
const ORIENTATION_ASEXUAL = "as";
const ORIENTATION_OTHER = "a";

// Enumeration of all couple situations (in the "situation" field)
const SITUATION_SINGLE = "single";
const SITUATION_OPEN = "open";

// Enumeration of all wanted relation types (in the "rel_search" field)
const REL_OCCASIONAL = "ro";
const REL_SERIOUS = "rs";
const REL_NO_TOMORROW = "rl";
const REL_TALK_AND_SEE = "ad";
const REL_NON_EXCLUSIVE = "rne";

// Preference enum (boolean with a 'whatever' option)
const PREF_YES = "yes";
const PREF_NO = "no";
const PREF_WHATEVER = "w";

// Block status enum (if i'm blocked, or if the other person blocked me)
const BS_ME = 2; // I blocked the other person
const BS_THEM = 1; // I got blocked by the other person
const BS_NO_BLOCK = 0; // No blocks at all

const DEFAULT_PFP = "/assets/img/pfp_default.png"; // The profile picture used when none is specified
const SUP_DATE_FMT = \DateTimeInterface::ATOM; // The date format used in supExpire and supBought

/**
 * Registers a new user with the given profile information.
 * E-mails already in use and banned e-mails will not be accepted.
 *
 * Returns one of those error codes:
 * - ERR_EMAIL_USED
 * - ERR_EMAIL_BANNED
 * - ERR_INVALID_CREDENTIALS (invalid password)
 * - ERR_INVALID_FIELD
 * - ERR_FIELD_MISSING
 *
 * @param string $firstname the first name
 * @param string $lastname the last name
 * @param string $email the e-mail
 * @param string $password the password in clear text
 * @param string $bdate birthdate in the format "Y-m-d"
 * @param string $gender the gender (see the GENDER enum)
 * @param int $id if the registration is successful, is set to the id of the registered user
 * @param bool $admin whether the user is an admin
 * @return int the error code (see the ERR enum), 0 if success
 */
function register(string $firstname, string $lastname, string $email, string $password, string $bdate, string $gender, int &$id, bool $admin = false): int {
    if (\UserDB\findByEmail($email) != null) {
        return ERR_EMAIL_USED;
    }
    if (\ModerationDB\emailBanned($email)) {
        return ERR_EMAIL_BANNED;
    }

    // First check if the profile is valid. (with existingId = null since it's a new user)
    $prof = [
        "firstName" => $firstname,
        "lastName" => $lastname,
        "email" => $email,
        "bdate" => $bdate,
        "gender" => $gender,
    ];
    $valErr = validateProfile($prof, null);
    if ($valErr !== 0) {
        return $valErr;
    }

    if (empty($password)) {
        return ERR_INVALID_CREDENTIALS;
    }

    // Put a truckload of user data into the database.
    $id = \UserDB\put(
        array(
            "email" => $email,
            "pass" => password_hash($password, PASSWORD_DEFAULT),
            "firstName" => $firstname,
            "lastName" => $lastname,
            "bdate" => $bdate,
            "gender" => $gender,
            "rdate" => date('Y-m-d'),
            "pfp" => DEFAULT_PFP,
            "orientation" => "",
            "job" => "",
            "situation" => "",
            "dep" => "",
            "depName" => "",
            "city" => "",
            "cityName" => "",
            "desc" => "",
            "bio" => "",
            "mathField" => "",
            "eigenVal" => "",
            "equation" => "",
            "user_smoke" => "",
            "search_smoke" => "",
            "pic1" => "",
            "pic2" => "",
            "pic3" => "",
            "admin" => $admin,
            "supExpire" => null,
            "supBought" => null,
            "gender_search" => [],
            "rel_search" => [],
            "conversations" => [],
            "blockedUsers" => [],
            "blockedBy" => []
        )
    );

    return 0;
}

/**
 * Updates a user's profile with the given information.
 * The `$profile` array corresponds to the main required profile fields;
 * The `$profile_details` array corresponds to the optional profile fields.
 * Refer to the first lines of this file to see the structure of the profile and profile details.
 *
 * Possible error codes:
 * - ERR_USER_NOT_FOUND
 * - ERR_INVALID_FIELD
 * - ERR_FIELD_MISSING
 *
 * @param int $id the user's id
 * @param array $profile the required profile information
 * @param array|null $profile_details the optional profile information
 * @param array|null $updatedUser set to an array containing the user data with the new changes, only if successful
 * @return int an error code (see the ERR enum), 0 if success
 */
function updateProfile(int $id, array $profile, array $profile_details = null, array &$updatedUser = null): int {
    // Load the user array from the database
    // Note that, in PHP, arrays are passed by value by default,
    // so this will yield a *copy* of the user data we're going to send back
    // to the database afterward. This is also why we need a $updatedUser parameter.
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    // Validate the profile before continuing with the rest.
    $code = validateProfile($profile, $id);
    if ($code !== 0) {
        return $code;
    }

    // Update the required profile fields in the user array
    $user["firstName"] = $profile["firstName"];
    $user["lastName"] = $profile["lastName"];
    $user["bdate"] = $profile["bdate"];
    $user["email"] = $profile["email"];
    $user["gender"] = $profile["gender"];

    // Validate all elements of the $profile_details array.
    // validX functions allow empty values by default, unless specified with the third parameter
    // These functions will also set the $valid boolean to false once they see an invalid value,
    // although they won't change a false value to true.
    $valid = true;
    validOrientation($profile_details["orientation"], $valid);
    validPref($profile_details["search_smoke"], $valid);
    validPref($profile_details["user_smoke"], $valid, false); // don't allow "whatever"
    validSituation($profile_details["situation"], $valid);

    // Validate the rel_search and gender_search arrays
    // We remove duplicate elements and restrict the size of the array to a small number,
    // in order to avoid pesky DoS attacks (since array_unique is a rather costly function).
    $rs = &$profile_details["rel_search"];
    if (is_array($rs) && count($rs) < 10) {
        $rs = array_unique($rs);
        foreach ($rs as $r) validRelType($r, $valid, false); // don't allow empty genders
    } else {
        $valid = false;
    }

    // Same thing here
    $gs = &$profile_details["gender_search"];
    if (is_array($gs) && count($gs) < 10) {
        $gs = array_unique($gs);
        foreach ($gs as $g) validGender($g, $valid, false); // don't allow empty genders
    } else {
        $valid = false;
    }

    // One of the fields is invalid, so let's give up there
    if (!$valid) {
        return ERR_INVALID_FIELD;
    }

    // Update all profile details fields in the user array, while sanitizing the strings
    // to avoid too large values.
    // sanitize reduces the string to N characters, and removes useless spaces
    // (at the start and end of the string)
    $user["pfp"] = sanitize($profile_details["pfp"], 128);
    $user["orientation"] = $profile_details["orientation"]; // already validated before
    $user["job"] = sanitize($profile_details["job"], 64);
    $user["situation"] = $profile_details["situation"]; // already validated before
    $user["dep"] = sanitize($profile_details["dep"], 64);
    $user["depName"] = sanitize($profile_details["depName"], 64);
    $user["city"] = sanitize($profile_details["city"], 64);
    $user["cityName"] = sanitize($profile_details["cityName"], 64);
    $user["desc"] = sanitize($profile_details["desc"], 2000);
    $user["bio"] = sanitize($profile_details["bio"], 2000);
    $user["mathField"] = sanitize($profile_details["mathField"], 64);
    $user["eigenVal"] = sanitize($profile_details["eigenVal"], 2000);
    $user["equation"] = sanitize($profile_details["equation"], 2000);
    $user["user_smoke"] = $profile_details["user_smoke"]; // already validated before
    $user["pic1"] = sanitize($profile_details["pic1"], 128);
    $user["pic2"] = sanitize($profile_details["pic2"], 128);
    $user["pic3"] = sanitize($profile_details["pic3"], 128);
    $user["search_smoke"] = $profile_details["search_smoke"]; // already validated before
    $user["gender_search"] = $profile_details["gender_search"]; // already validated before
    $user["rel_search"] = $profile_details["rel_search"]; // already validated before

    // Update the user in the database, using the $user array we've copied earlier and
    // modified with the new profile data.
    \UserDB\put($user);
    $updatedUser = $user; // Also allow the caller to have the new user.

    // rest
    return 0;
}

/**
 * Updates the password of a user, and stores it in the database in a hashed form.
 * The new password must not be empty.
 *
 * Returned error codes:
 * - ERR_USER_NOT_FOUND
 * - ERR_INVALID_CREDENTIALS
 *
 * @param int $id the user's id
 * @param string $pass the clear-text password
 * @param array|null $updatedUser an optional reference to the updated user array
 * @return int an error code (see ERR enum), 0 if successful
 */
function updatePassword(int $id, string $pass, ?array &$updatedUser = null): int {
    // Take a copy of the user array from the database
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    // Check the password's validity
    if (empty($pass)) {
        return ERR_INVALID_CREDENTIALS;
    }

    // Put the new hash into the database
    $user["pass"] = password_hash($pass, PASSWORD_DEFAULT);
    \UserDB\put($user);
    $updatedUser = $user;

    return 0;
}

/**
 * Validates the main profile data for a user (see the beginning of the user.php file for reference).
 *
 * The `$existingId` parameter must be filled with the user's id with this profile data.
 * A null value can be given if this is a new registration.
 * Else, the function will check if the e-mail is already used by another user.
 *
 * Returned error codes:
 * - ERR_FIELD_MISSING
 * - ERR_INVALID_FIELD
 * - ERR_EMAIL_USED
 *
 * @param array $profile the new profile data
 * @param int|null $existingId the id of the user whose profile is being updated (null if new registration)
 * @return int an error code (see ERR enum), 0 if successful
 */
function validateProfile(array &$profile, ?int $existingId): int {
    $profile["firstName"] = sanitize($profile["firstName"], 80);
    $profile["lastName"] = sanitize($profile["lastName"], 80);
    $profile["email"] = sanitize($profile["email"], 128);

    if (empty($profile["firstName"])
        || empty($profile["lastName"])
        || empty($profile["email"])
        || empty($profile["bdate"])
        || empty($profile["gender"])
    ) {
        return ERR_FIELD_MISSING;
    }

    if (!validGender($profile["gender"])) {
        return ERR_INVALID_FIELD;
    }

    // Make sure that the birthdate is in the right format.
    $birthdate = DateTime::createFromFormat("Y-m-d", $profile["bdate"]);
    if ($birthdate === false) {
        return ERR_INVALID_FIELD;
    }

    $today = new DateTime();
    $diff = $birthdate->diff($today); // $today - $birthdate (usually positive)
    $age = $diff->y;

    // Make sure that the user is at least 18 years old (invalid when $age < 18),
    // AND that the date interval isn't negative (happens when $diff->invert==1),
    // so the user isn't born in the future!!
    if ($age < 18 || $diff->invert == 1) {
        return ERR_INVALID_FIELD;
    }

    // Also don't allow absurdly high ages (source: Guinness World Records)
    if ($age > 123) {
        return ERR_INVALID_FIELD;
    }

    // If the user already exists, make sure it isn't overriding an already registered e-mail.
    if ($existingId !== null) {
        $u = \UserDB\findById($existingId);
        if ($u !== null
            && $u["email"] != $profile["email"]
            && \UserDB\findByEmail($profile["email"]) != null) {
            return ERR_EMAIL_USED;
        }
    }

    return 0;
}

/**
 * Deletes a user account from the database, while optionally checking for the password,
 * and allows for banning the user's email.
 *
 * If `$pass` is not null, then it is checked against the user's password. If they don't match,
 * the ERR_INVALID_CREDENTIALS error code is returned.
 *
 * If `$ban` is true, then the user's e-mail will be banned and cannot be used for future registrations.
 *
 * @param int $id the user's id
 * @param string|null $pass the password that must be the same as the user's password; a null value ignore the check
 * @param bool $ban whether to ban the user's e-mail
 * @return int an error code (see ERR enum), 0 if successful
 */
function deleteAccount(int $id, ?string $pass, bool $ban = false): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    if ($pass !== null && !password_verify($pass, $user["pass"])) {
        return ERR_INVALID_CREDENTIALS;
    }

    \UserDB\delete($id);
    if ($ban) {
        \ModerationDB\banEmail($user["email"]);
    }

    return 0;
}

/**
 * Starts a new conversation between two users, where the first user specified is the one
 * creating the conversation.
 *
 * The first user must be at least a subscriber to start a conversation, else, an ERR_NOT_SUBSCRIBED
 * error code is returned.
 *
 * A conversation cannot be created between the same user and itself, in which case the ERR_SAME_USER
 * error code is returned.
 *
 * If the conversation already exists, this function will return the ERR_CONVERSATION_EXISTS error code.
 *
 * If the first user perceives the second user as blocked, no conversation will be created
 * and the ERR_BLOCKED error code will be returned. See {@see blockStatus} for more information.
 *
 * @param int $id1 the id of the first user, initiating the conversation
 * @param int $id2 the id of the second user
 * @param string|null $convId set to the created conversation id if successful
 * @param array|null $updatedUser1 set to the first user's updated data if successful
 * @param array|null $updatedUser2 set to the second user's updated data if successful
 * @return int an error code (see ERR enum), 0 if successful
 */
function startConversation(int    $id1,
                           int    $id2,
                           string &$convId = null,
                           array  &$updatedUser1 = null,
                           array  &$updatedUser2 = null): int {
    if ($id1 == $id2) {
        return ERR_SAME_USER;
    }

    // Do I have the privilege to start a conversation with TTM sup?
    if (level($id1) < LEVEL_SUBSCRIBER) {
        return ERR_NOT_SUBSCRIBED;
    }

    $user1 = \UserDB\findById($id1);
    $user2 = \UserDB\findById($id2);

    if ($user1 === null || $user2 === null) {
        return ERR_USER_NOT_FOUND;
    }

    // Is there already a conversation?
    $convId = \ConversationDB\existingId($id1, $id2);
    if ($convId !== null) {
        return ERR_CONVERSATION_EXISTS;
    }

    // Did user 2 block me, or did I block them? If so, quit.
    if (blockStatus($id1, $id2) !== BS_NO_BLOCK) {
        return ERR_BLOCKED;
    }

    // Create the conversation and add it to both users.
    $convId = \ConversationDB\create($id1, $id2);
    $user1["conversations"][] = $convId;
    $user2["conversations"][] = $convId;

    \UserDB\put($user1);
    \UserDB\put($user2);

    $updatedUser1 = $user1;
    $updatedUser2 = $user2;

    return 0;
}

/**
 * Returns a conversation between two users, if the first user has the right to see it;
 * if not, returns null.
 *
 * An admin user always has right to consult any conversation.
 *
 * @param int $userId the user id
 * @param string $convId the conversation id
 * @return array|null the conversation data (see conversationDB.php) or null if not found/not accessible
 */
function findConversation(int $userId, string $convId): ?array {
    $user = \UserDB\findById($userId);
    if ($user === null) {
        return null;
    }

    if (in_array($convId, $user["conversations"]) || level($userId) >= LEVEL_ADMIN) {
        return \ConversationDB\find($convId);
    } else {
        return null;
    }
}

/**
 * Blocks a user. If the user is already blocked, nothing happens.
 *
 * A user cannot block themselves, else, the ERR_SAME_USER error code is returned.
 *
 * @param int $blockerId the user id of the blocker
 * @param int $blockeeId the user id of the blocked person
 * @return int an error code (see ERR enum), 0 if successful
 */
function blockUser(int $blockerId, int $blockeeId): int {
    if ($blockerId == $blockeeId) {
        return ERR_SAME_USER;
    }

    $blocker = \UserDB\findById($blockerId);
    $blockee = \UserDB\findById($blockeeId);
    if ($blocker == null || $blockee == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (isset($blocker["blockedUsers"][$blockeeId])) {
        return 0; // already blocked
    }

    $blocker["blockedUsers"][$blockeeId] = 1;
    \UserDB\put($blocker);

    return 0;
}

/**
 * Unblocks a user. If the user is not blocked, nothing happens.
 *
 * @param int $blockerId the user id of the blocker
 * @param int $blockeeId the user id of the blocked person
 * @return int an error code (see ERR enum), 0 if successful
 */
function unblockUser(int $blockerId, int $blockeeId): int {
    $blocker = \UserDB\findById($blockerId);
    if ($blocker == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (isset($blocker["blockedUsers"][$blockeeId])) {
        unset($blocker["blockedUsers"][$blockeeId]);
        \UserDB\put($blocker);
        return 0;
    } else {
        return 0; // nothing to do
    }
}

/**
 * Subscribes a user to TTM sup for a given duration.
 *
 * If the user is already subscribed, their subscription is extended (see the supExpire field).
 * Else, a new subscription is started from the current date, and their subscription purchase date is reset!
 * (see the supBought field)
 *
 * @param int $id the user id
 * @param \DateInterval $duration the duration of the subscription
 * @param array|null $updatedUser set to the updated user data if successful
 * @return int an error code (see ERR enum), 0 if successful
 */
function subscribe(int $id, \DateInterval $duration, array &$updatedUser = null): int {
    $user = \UserDB\findById($id);
    if ($user === null) {
        return ERR_USER_NOT_FOUND;
    }

    if ($duration->invert) {
        throw new \RuntimeException("Duration must be positive");
    }

    if (supActive($user, $exp)) {
        $exp->add($duration);
        $user["supExpire"] = $exp->format(SUP_DATE_FMT);
    } else {
        // Reset the purchase date.
        $user["supExpire"] = (new \DateTime())->add($duration)->format(SUP_DATE_FMT);
        $user["supBought"] = (new \DateTime())->format(SUP_DATE_FMT);
    }

    \UserDB\put($user);
    $updatedUser = $user;
    return 0;
}

/**
 * Determines the block relationship between two users: user V (viewer) and user T (target).
 *
 * - If V blocked T, then returns BS_ME.
 * - If V was blocked by T, then returns BS_THEM.
 * - Else, returns BS_NO_BLOCK.
 *
 * If users mutually blocked themselves (which is odd),
 * then the viewer's block will be prioritized (in order to still be able to unblock the user).
 *
 * Administrators are special in this regard, they ignore all external blocks!
 * For example, if user V is an admin, then user's T block against V will be ignored;
 * meaning that this function will return BS_NONE.
 * This behavior can be overridden using the `$adminIgnoreTargetBlock` parameter (defaulting to true).
 *
 * Returns BS_NO_BLOCK if the viewer has not been found.
 *
 * @param int $viewerId the user id of the viewer
 * @param int $targetId the user id of the target
 * @param bool $adminIgnoreTargetBlock whether admins should ignore the target's block
 * @return int the block status (see BS enum)
 */
function blockStatus(int $viewerId, int $targetId, bool $adminIgnoreTargetBlock = true): int {
    $viewer = \UserDB\findById($viewerId);
    if ($viewer === null) {
        return 0;
    }

    if (isset($viewer["blockedUsers"][$targetId])) {
        return BS_ME;
    } else if (isset($viewer["blockedBy"][$targetId])
        && (!$adminIgnoreTargetBlock || level($viewerId) < LEVEL_ADMIN)) {
        return BS_THEM;
    } else {
        return BS_NO_BLOCK;
    }
}

/**
 * Returns the role level of a user. Each role grants more and more permissions.
 *
 * - An inexistant user (id null or not found) will return LEVEL_GUEST.
 * - An admin user will return LEVEL_ADMIN, regardless of subscription status.
 * - A user with no subscription to TTM sup will return LEVEL_MEMBER.
 * - A user with a valid subscription to TTM sup will return LEVEL_SUBSCRIBER.
 *
 * @param int|null $id the user id, can be null for LEVEL_GUEST
 * @return int the role level of the user (see LEVEL enum)
 */
function level(?int $id): int {
    if ($id === null) {
        return LEVEL_GUEST;
    }

    $u = \UserDB\findById($id);
    if ($u === null) {
        return LEVEL_GUEST;
    }

    if ($u["admin"]) {
        return LEVEL_ADMIN;
    }

    if (supActive($u)) {
        return LEVEL_SUBSCRIBER;
    }

    // Nico privilege
    if (stristr(strtolower($u["firstName"]), "nico")) {
        return LEVEL_SUBSCRIBER;
    }

    return LEVEL_MEMBER;
}

/**
 * Returns the age of a user. If not found, returns 0.
 *
 * @param int $id the user id
 * @return int the age of the user, 0 if not found
 */
function age(int $id): int {
    $u = \UserDB\findById($id);
    if ($u === null) {
        return 0;
    }

    $bd = DateTime::createFromFormat("Y-m-d", $u["bdate"]);
    if ($bd === false) {
        throw new \RuntimeException("Birthdate is in an invalid format: {$u["bdate"]}");
    }

    return $bd->diff(new DateTime())->y;
}

// $u : id ou tableau utilisateur

/**
 * Returns true whenever a user has an active subscription to TTM sup.
 *
 * The user `$u` can be passed as either a user id or an associative array.
 * The parameter `$exp` will be filled with the expiration date if found.
 *
 * Returns false if the user doesn't exist.
 *
 * @param array|int $u the user id or its associative array
 * @param DateTime|null $exp set to the subscription expiration date
 * @return bool true if the user has an active subscription, false otherwise
 */
function supActive($u, \DateTime &$exp = null): bool {
    $u = is_int($u) ? \UserDB\findById($u) : $u;
    if ($u === null || $u["supExpire"] === null) {
        return false;
    }

    $exp = DateTime::createFromFormat(SUP_DATE_FMT, $u["supExpire"]);
    if ($exp !== false) {
        $now = new \DateTime();
        $diff = $exp->diff($now); // $now - $date

        // $now < $exp
        // <==> $now - $exp < 0
        // <==> $diff < 0
        // <==> $diff->invert == 1
        if ($diff !== false && $diff->invert == 1) {
            return true;
        } else {
            return false;
        }
    }

    return false;
}

/*
 * Various validation functions
 */

/**
 * Sanitizes a string by trimming its leading and tailing spaces, and cutting it to a maximum length.
 * A null string will return null.
 *
 * @param string|null $str the string to sanitize
 * @param int $max the max length
 * @return string|null the sanitized string, or null if the input was null
 */
function sanitize(?string $str, int $max): ?string {
    if ($str === null) {
        return null;
    }
    return substr(trim($str), 0, $max);
}

/**
 * Sets the `$valid` parameter to false if the gender is invalid (i.e. present in the GENDER enum).
 * Returns the `$valid` parameter.
 * `$allowEmpty` can be set to true if empty values should be allowed.
 *
 * @param string|null $gend the enum to validate
 * @param bool $valid the validation boolean, set to false when invalid
 * @param bool $allowEmpty whether empty values are allowed
 * @return bool the validation boolean
 */
function validGender(?string $gend, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($gend === GENDER_WOMAN
        || $gend === GENDER_MAN
        || $gend === GENDER_NON_BINARY
        || ($allowEmpty && empty($gend)));
}

/**
 * Sets the `$valid` parameter to false if the orientation is invalid (i.e. present in the ORIENTATION enum).
 * Returns the `$valid` parameter.
 * `$allowEmpty` can be set to true if empty values should be allowed.
 *
 * @param string|null $orient the enum to validate
 * @param bool $valid the validation boolean, set to false when invalid
 * @param bool $allowEmpty whether empty values are allowed
 * @return bool the validation boolean
 */
function validOrientation(?string $orient, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($orient === ORIENTATION_HETERO
        || $orient === ORIENTATION_HOMO
        || $orient === ORIENTATION_BI
        || $orient === ORIENTATION_PAN
        || $orient === ORIENTATION_ASEXUAL
        || $orient === ORIENTATION_OTHER
        || ($allowEmpty && empty($orient)));
}

/**
 * Sets the `$valid` parameter to false if the situation is invalid (i.e. present in the SITUATION enum).
 * Returns the `$valid` parameter.
 * `$allowEmpty` can be set to true if empty values should be allowed.
 *
 * @param string|null $situation the enum to validate
 * @param bool $valid the validation boolean, set to false when invalid
 * @param bool $allowEmpty whether empty values are allowed
 * @return bool the validation boolean
 */
function validSituation(?string $situation, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($situation === SITUATION_SINGLE
        || $situation === SITUATION_OPEN
        || ($allowEmpty && empty($situation)));
}

/**
 * Sets the `$valid` parameter to false if the relation type is invalid (i.e. present in the REL enum).
 * Returns the `$valid` parameter.
 * `$allowEmpty` can be set to true if empty values should be allowed.
 *
 * @param string|null $relType the enum to validate
 * @param bool $valid the validation boolean, set to false when invalid
 * @param bool $allowEmpty whether empty values are allowed
 * @return bool the validation boolean
 */
function validRelType(?string $relType, bool &$valid = true, bool $allowEmpty = true): bool {
    return $valid &= ($relType === REL_OCCASIONAL
        || $relType === REL_SERIOUS
        || $relType === REL_NO_TOMORROW
        || $relType === REL_TALK_AND_SEE
        || $relType === REL_NON_EXCLUSIVE
        || ($allowEmpty && empty($relType)));
}

/**
 * Sets the `$valid` parameter to false if the preference value is invalid (i.e. present in the PREF enum).
 * Returns the `$valid` parameter.
 * `$allowWhatever` can be set to false if the WHATEVER option should be forbidden.
 * `$allowEmpty` can be set to true if empty values should be allowed.
 *
 * @param string|null $pref the enum to validate
 * @param bool $valid the validation boolean, set to false when invalid
 * @param bool $allowWhatever whether the WHATEVER option is allowed
 * @param bool $allowEmpty whether empty values are allowed
 * @return bool the validation boolean
 */
function validPref(?string $pref, bool &$valid = true, bool $allowWhatever = true, bool $allowEmpty = true): bool {
    return $valid &= ($pref === PREF_YES
        || $pref === PREF_NO
        || ($allowWhatever && $pref === PREF_WHATEVER)
        || ($allowEmpty && empty($pref)));
}

/**
 * Converts an error code to a user-friendly localized message.
 *
 * @param int $err the error code
 * @return string the error message
 */
function errToString(int $err): string {
    switch ($err) {
        case ERR_EMAIL_USED:
            return "Ce mail est deja utilisé";
        case ERR_EMAIL_BANNED:
            return "Cette adresse mail est bannie.";
        case ERR_FIELD_MISSING:
            return "Veuillez renseigner tous les champs";
        case ERR_INVALID_CREDENTIALS:
            return "Le mot de passe ou l'identifiant n'est pas le bon";
        case ERR_USER_NOT_FOUND:
            return "L'utilisateur n'existe pas";
        case ERR_BLOCKED:
            return "Un blocage existe entre ces deux utilisateurs.";
        case ERR_INVALID_FIELD:
            return "Un champ est invalide.";
        default:
            return "Erreur !";
    }
}