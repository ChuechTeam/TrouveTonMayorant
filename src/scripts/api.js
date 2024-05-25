const root = new URL(window.location.origin);

// Convert a Deleted-Messages header (1,8,15) to an array of numbers
function parseDelMsgHeader(header) {
    if (header == null) {
        return [];
    }

    return header.split(",").map(Number);
}

export const api = {
    /**
     * Sends a message to a conversation
     * @param {string} convId the conversation id
     * @param {string} content contents of the message
     * @param {number | null} since the id of the last received message, to filter out old messages
     *
     * @return {Promise<{firstMsgId: number, lastMsgId: number, deletedMessages: number[], html: string}>}
     *  a promise that, when completed, has the html of all messages posted after the "since" parameter,
     *  along with the first and last message ids, and the ids of deleted messages
     */
    async sendMessage(convId, content, since) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        // Fill URL parameters: ?id and ?since
        endpoint.searchParams.set("id", convId);
        if (since != null) {
            endpoint.searchParams.set("since", since);
        }

        const res = await fetch(endpoint, {
            method: "POST",
            body: JSON.stringify({content}),
            headers: {
                "Content-Type": "application/json"
            }
        });

        if (res.status !== 200) {
            throw new Error("Failed to send message! Error code: " + res.status);
        }

        // TODO: make use of the Is-Blocked header
        return {
            firstMsgId: parseInt(res.headers.get("First-Message-Id")),
            lastMsgId: parseInt(res.headers.get("Last-Message-Id")),
            deletedMessages: parseDelMsgHeader(res.headers.get("Deleted-Messages")),
            html: await res.text()
        };
    },

    /**
     * Fetches all messages from a conversation posted after a specified message id (`since`).
     * If not specified, fetches all messages.
     * @param {string} convId the conversation id
     * @param {since} since the id of the last received message, to filter out old messages
     * @return {Promise<{firstMsgId: (number|null), lastMsgId: (number|null), hasContent: boolean, html: (string|null), deletedMessages: number[]}>}
     * a promise with an object, with a list of deleted messages after `since`, that has two "forms":
     * - When `hasContent` is true:
     *   - `firstMsgId`, `lastMsgId` contain respectively the first and last message ids
     *   - `html` contains the HTML of all messages posted after the `since` parameter
     * - When `hasContent` is false:
     *   - There are no messages posted after `since`!
     *   - `firstMsgId`, `lastMsgId`, and `html` are null
     *   - `deletedMessages` might still have some information
     */
    async getMessages(convId, since) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        endpoint.searchParams.set("id", convId);
        if (since != null) {
            endpoint.searchParams.set("since", since);
        }

        const res = await fetch(endpoint);
        if (!res.ok) {
            throw new Error("Failed to fetch messages! Error code: " + res.status);
        }

        const hasContent = res.status === 200; // 204
        return {
            // Content variables
            hasContent,
            firstMsgId: hasContent ? parseInt(res.headers.get("First-Message-Id")) : null,
            lastMsgId: hasContent ? parseInt(res.headers.get("Last-Message-Id")) : null,
            html: hasContent ? await res.text() : null,
            // Deleted messages
            deletedMessages: parseDelMsgHeader(res.headers.get("Deleted-Messages"))
        };
    },

    async deleteMessage(convId, msgId) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        endpoint.searchParams.set("id", convId);
        endpoint.searchParams.set("msgId", msgId);

        const res = await fetch(endpoint, {
            method: "DELETE"
        });

        if (res.status !== 200) {
            throw new Error("Failed to delete message! Error code: " + res.status);
        }
    },

    async getConversation(convId) {
        const endpoint = new URL("member-area/api/conversations.php", root);
        endpoint.searchParams.set("id", convId);

        const res = await fetch(endpoint);
        return await res.text();
    },

    async reportMessage(convId, msgId, reason) {
        const endpoint = new URL("member-area/api/reports.php", root);

        const res = await fetch(endpoint, {
            method: "POST",
            body: JSON.stringify({convId, msgId, reason}),
            headers: {
                "Content-Type": "application/json"
            }
        });

        if (res.status !== 200) {
            throw new Error("Failed to report message! Error code: " + res.status);
        }
    },

    async deleteReport(reportId) {
        const url = new URL(`/member-area/api/reports.php?id=${reportId}`, root);
        const response = await fetch(url, {
            method: 'DELETE',
        });
        if (!response.ok) {
            throw new Error('Failed to delete report');
        }
    }
}