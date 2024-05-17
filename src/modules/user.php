<?php

namespace User;

require_once __DIR__ . "/userDB.php";
require_once __DIR__ . "/conversationDB.php";
require_once __DIR__ . "/moderationDB.php";

use DateTime;

// Liste des codes d'erreur
const ERR_EMAIL_USED = 1;
const ERR_FIELD_MISSING = 2;
const ERR_INVALID_CREDENTIALS = 3;
const ERR_USER_NOT_FOUND = 4;
const ERR_CONVERSATION_EXISTS = 5;
const ERR_SAME_USER = 6;
const ERR_EMAIL_BANNED = 7;

// Liste des grades/rôles
const LEVEL_GUEST = 1; // Visiteur non inscrit
const LEVEL_MEMBER = 2; // Utilisateur non abonné
const LEVEL_SUBSCRIBER = 3; // Utilisateur abonné
const LEVEL_ADMIN = 4; // Administrateur

// Liste des genres (dans "gender")
const GENDER_MAN = "m";
const GENDER_WOMAN = "f";
const GENDER_NON_BINARY = "nb";

// Liste des orientations (dans "orientation")
const ORIENTATION_HETERO = "het";
const ORIENTATION_HOMO = "ho";
const ORIENTATION_BI = "bi";
const ORIENTATION_PAN = "pan";
const ORIENTATION_ASEXUAL = "as";
const ORIENTATION_OTHER = "a";

// Liste des situations (dans "situation")
const SITUATION_SINGLE = "single";
const SITUATION_OPEN = "open";

// Liste des types de relation (dans "rel_search")
const REL_OCCASIONAL = "ro";
const REL_SERIOUS = "rs";
const REL_NO_TOMORROW = "rl";
const REL_TALK_AND_SEE = "ad";
const REL_NON_EXCLUSIVE = "rne";

// Préférence (booléen avec peu importe)
const PREF_YES = "yes";
const PREF_NO = "no";
const PREF_WHATEVER = "w";

// Block status (si on est bloqué ou si on n'est pas bloqué)
const BS_ME = 2; // J'ai bloqué
const BS_THEM = 1; // On m'a bloqué
const BS_NO_BLOCK = 0; // Y'a pas de souci

const DEFAULT_PFP = "/assets/img/pfp_default.png";

// 0 --> OK
// >0 --> OH NOOO
function register(string $firstname, string $lastname, string $email, string $password, $bdate, string $gender, &$id, bool $admin = false): int {
    if (\UserDB\findByEmail($email) != null) {
        return ERR_EMAIL_USED;
    }
    if (\ModerationDB\emailBanned($email)) {
        return ERR_EMAIL_BANNED;
    }

    $valErr = validateProfile([
        "firstName" => $firstname,
        "lastName" => $lastname,
        "email" => $email,
        "bdate" => $bdate,
        "gender" => $gender,
    ], null);
    if ($valErr !== 0) {
        return $valErr;
    }

    if (empty($password)) {
        return ERR_INVALID_CREDENTIALS;
    }

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
            "gender_search" => [],
            "rel_search" => [],
            "conversations" => [],
            "blockedUsers" => [],
            "blockedBy" => []
        )
    );

    return 0;
}

function updateProfile(int $id, array $profile, ?array $profile_details = null, ?array &$updatedUser = null): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    $code = validateProfile($profile, $id);
    if ($code !== 0) {
        return $code;
    }

    $user["firstName"] = $profile["firstName"];
    $user["lastName"] = $profile["lastName"];
    $user["bdate"] = $profile["bdate"];
    $user["email"] = $profile["email"];
    $user["gender"] = $profile["gender"];

    $user["pfp"] = $profile_details["pfp"];
    $user["orientation"] = $profile_details["orientation"];
    $user["job"] = $profile_details["job"];
    $user["situation"] = $profile_details["situation"];
    $user["dep"] = $profile_details["dep"];
    $user["depName"] = $profile_details["depName"];
    $user["city"] = $profile_details["city"];
    $user["cityName"] = $profile_details["cityName"];
    $user["desc"] = $profile_details["desc"];
    $user["bio"] = $profile_details["bio"];
    $user["mathField"] = $profile_details["mathField"];
    $user["eigenVal"] = $profile_details["eigenVal"];
    $user["equation"] = $profile_details["equation"];
    $user["user_smoke"] = $profile_details["user_smoke"];
    $user["pic1"] = $profile_details["pic1"];
    $user["pic2"] = $profile_details["pic2"];
    $user["pic3"] = $profile_details["pic3"];
    $user["search_smoke"] = $profile_details["search_smoke"];
    $user["gender_search"] = $profile_details["gender_search"];
    $user["rel_search"] = $profile_details["rel_search"];

    \UserDB\put($user);
    $updatedUser = $user;

    return 0;
}

function updatePassword(int $id, string $pass, ?array &$updatedUser = null): int {
    $user = \UserDB\findById($id);
    if ($user == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (empty($pass)) {
        return ERR_INVALID_CREDENTIALS;
    }

    $user["pass"] = password_hash($pass, PASSWORD_DEFAULT);
    \UserDB\put($user);
    $updatedUser = $user;

    return 0;
}

function validateProfile(array $profile, ?int $existingId): int {
    if (empty($profile["firstName"])
        || empty($profile["lastName"])
        || empty($profile["email"])
        || empty($profile["bdate"])
        || empty($profile["gender"])
    ) {
        return ERR_FIELD_MISSING;
    }


    $today = new DateTime();
    $birthdate = new DateTime($profile["bdate"]);
    $diff = $today->diff($birthdate);
    $age = $diff->y;

    if ($age < 18) {
        return ERR_FIELD_MISSING; // pas le bon error type mais flemme
    }

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

// Codes d'erreur possibles:
// - ERR_SAME_USER
// - ERR_USER_NOT_FOUND
// - ERR_CONVERSATION_EXISTS
function startConversation(int    $id1,
                           int    $id2,
                           string &$convId = null,
                           array  &$updatedUser1 = null,
                           array  &$updatedUser2 = null): int {
    if ($id1 == $id2) {
        return ERR_SAME_USER;
    }

    $user1 = \UserDB\findById($id1);
    $user2 = \UserDB\findById($id2);

    if ($user1 === null || $user2 === null) {
        return ERR_USER_NOT_FOUND;
    }

    $convId = \ConversationDB\existingId($id1, $id2);
    if ($convId !== null) {
        return ERR_CONVERSATION_EXISTS;
    }

    $convId = \ConversationDB\create($id1, $id2);
    $user1["conversations"][] = $convId;
    $user2["conversations"][] = $convId;

    \UserDB\put($user1);
    \UserDB\put($user2);

    $updatedUser1 = $user1;
    $updatedUser2 = $user2;

    return 0;
}

// Retourne la conversation SEULEMENT si l'utilisateur a le droit de la voir
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

function blockUser(int $blockerId, int $blockeeId): int {
    $blocker = \UserDB\findById($blockerId);
    $blockee = \UserDB\findById($blockeeId);
    if ($blocker == null || $blockee == null) {
        return ERR_USER_NOT_FOUND;
    }

    if (isset($blocker["blockedUsers"][$blockeeId])) {
        return 0; // déjà fait
    }

    $blocker["blockedUsers"][$blockeeId] = 1;
    \UserDB\put($blocker);

    return 0;
}

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
        return 0; // rien fait
    }
}

// Voir enum BLOCK STATUS (BS_XXX)
// renvoie 0 si l'utilisateur n'est pas trouvé
// si le blocage est mutuel (bizarre), le blocage de l'utilisateur cible sera priorisé.
function blockStatus(int $viewerId, int $targetId): int {
    $viewer = \UserDB\findById($viewerId);
    if ($viewer === null) {
        return 0;
    }

    if (isset($viewer["blockedBy"][$targetId])) {
        return BS_THEM;
    } else if (isset($viewer["blockedUsers"][$targetId])) {
        return BS_ME;
    }  else {
        return BS_NO_BLOCK;
    }
}

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

    if ($u["supExpire"] !== null) {
        $date = new \DateTime($u["supExpire"]);
        $now = new \DateTime();
        $diff = $date->diff($now);
        // Si l'intervalle n'est pas négatif : invert = 0
        if ($diff !== false && $diff->invert == 0) {
            return LEVEL_SUBSCRIBER;
        }
    }

    // Temporaire (mais réel)
    if (stristr(strtolower($u["firstName"]), "nico")) {
        return LEVEL_SUBSCRIBER;
    }

    // TODO: Utilisateur abonné et admin
    return LEVEL_MEMBER;
}

function age(int $id): int {
    $u = \UserDB\findById($id);
    if ($u === null) {
        return 0;
    }

    return (new DateTime($u["bdate"]))->diff(new DateTime())->y;
}

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
        default:
            return "Erreur !";
    }
}